-- ProSiparis_API Veritabanı Şeması v2.7
-- B2B Fiyatlandırma ve Depo Operasyonları (Fulfillment) için güncellenmiştir.

-- Tabloları oluşturmadan önce, varsa eskilerini (ilişki sırasına göre) sil.
DROP TABLE IF EXISTS `destek_mesajlari`;
DROP TABLE IF EXISTS `destek_talepleri`;
DROP TABLE IF EXISTS `sepet_urunleri`;
DROP TABLE IF EXISTS `sepetler`;
DROP TABLE IF EXISTS `bannerlar`;
DROP TABLE IF EXISTS `sayfalar`;
DROP TABLE IF EXISTS `urun_degerlendirmeleri`;
DROP TABLE IF EXISTS `kullanici_favorileri`;
DROP TABLE IF EXISTS `siparis_detaylari`;
DROP TABLE IF EXISTS `siparisler`;
DROP TABLE IF EXISTS `kullanici_adresleri`;
DROP TABLE IF EXISTS `odeme_seanslari`;
DROP TABLE IF EXISTS `rol_yetki_iliskisi`;
DROP TABLE IF EXISTS `kullanicilar`;
DROP TABLE IF EXISTS `roller`;
DROP TABLE IF EXISTS `yetkiler`;
DROP TABLE IF EXISTS `varyant_fiyatlari`;
DROP TABLE IF EXISTS `fiyat_listeleri`;
DROP TABLE IF EXISTS `varyant_deger_iliskisi`;
DROP TABLE IF EXISTS `urun_varyantlari`;
DROP TABLE IF EXISTS `urun_nitelik_degerleri`;
DROP TABLE IF EXISTS `urun_nitelikleri`;
DROP TABLE IF EXISTS `kuponlar`;
DROP TABLE IF EXISTS `urunler`;
DROP TABLE IF EXISTS `kategoriler`;
DROP TABLE IF EXISTS `kargo_secenekleri`;

-- --------------------------------------------------------
-- B2B FİYATLANDIRMA ve ACL
-- --------------------------------------------------------

CREATE TABLE `fiyat_listeleri` (
  `liste_id` INT AUTO_INCREMENT PRIMARY KEY,
  `liste_adi` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `roller` (
  `rol_id` INT AUTO_INCREMENT PRIMARY KEY,
  `rol_adi` VARCHAR(100) NOT NULL UNIQUE,
  `fiyat_listesi_id` INT NULL,
  FOREIGN KEY (`fiyat_listesi_id`) REFERENCES `fiyat_listeleri`(`liste_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `yetkiler` (
  `yetki_id` INT AUTO_INCREMENT PRIMARY KEY,
  `yetki_kodu` VARCHAR(100) NOT NULL UNIQUE,
  `aciklama` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rol_yetki_iliskisi` (
  `rol_id` INT NOT NULL,
  `yetki_id` INT NOT NULL,
  PRIMARY KEY (`rol_id`, `yetki_id`),
  FOREIGN KEY (`rol_id`) REFERENCES `roller`(`rol_id`) ON DELETE CASCADE,
  FOREIGN KEY (`yetki_id`) REFERENCES `yetkiler`(`yetki_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `kullanicilar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rol_id` INT NOT NULL,
  `ad_soyad` VARCHAR(100) NOT NULL,
  `eposta` VARCHAR(100) NOT NULL UNIQUE,
  `parola` VARCHAR(255) NOT NULL,
  `kayit_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`rol_id`) REFERENCES `roller`(`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------
-- KATALOG VE DEPO YÖNETİMİ
-- --------------------------------------------------------

CREATE TABLE `kategoriler` (
  `kategori_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kategori_adi` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `urunler` (
  `urun_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kategori_id` INT NULL,
  `urun_adi` VARCHAR(255) NOT NULL,
  `aciklama` TEXT,
  `ortalama_puan` DECIMAL(3, 2) DEFAULT 0.00,
  `degerlendirme_sayisi` INT DEFAULT 0,
  FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler`(`kategori_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `urun_varyantlari` (
  `varyant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `urun_id` INT NOT NULL,
  `varyant_sku` VARCHAR(100) NOT NULL UNIQUE,
  `stok_adedi` INT NOT NULL DEFAULT 0,
  `raf_kodu` VARCHAR(50) NULL, -- Depo operasyonları için
  FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`urun_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `varyant_fiyatlari` (
  `fiyat_id` INT AUTO_INCREMENT PRIMARY KEY,
  `varyant_id` INT NOT NULL,
  `fiyat_listesi_id` INT NOT NULL,
  `fiyat` DECIMAL(10, 2) NOT NULL,
  UNIQUE(`varyant_id`, `fiyat_listesi_id`),
  FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`) ON DELETE CASCADE,
  FOREIGN KEY (`fiyat_listesi_id`) REFERENCES `fiyat_listeleri`(`liste_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------
-- SİPARİŞ VE LOJİSTİK
-- --------------------------------------------------------

CREATE TABLE `siparisler` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `siparis_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `toplam_tutar` DECIMAL(10, 2) NOT NULL,
  `durum` ENUM('Odeme Bekleniyor', 'Odendi', 'Hazirlaniyor', 'Kargoya Verildi', 'Teslim Edildi', 'Iptal Edildi') NOT NULL DEFAULT 'Odeme Bekleniyor',
  `kargo_firmasi` VARCHAR(100) NULL,
  `kargo_takip_kodu` VARCHAR(100) NULL,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `siparis_detaylari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `siparis_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `adet` INT NOT NULL,
  `birim_fiyat` DECIMAL(10, 2) NOT NULL, -- Sipariş anındaki fiyatı sakla
  FOREIGN KEY (`siparis_id`) REFERENCES `siparisler`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- (Diğer tüm tablolar buraya eklenecek...)
-- --------------------------------------------------------
-- DİĞER TABLOLAR (v2.6'dan)
-- --------------------------------------------------------
CREATE TABLE `destek_talepleri` ( `talep_id` INT AUTO_INCREMENT PRIMARY KEY, `kullanici_id` INT NOT NULL, `siparis_id` INT NULL, `konu` VARCHAR(255) NOT NULL, `durum` ENUM('acik', 'cevaplandi', 'kapandi') NOT NULL DEFAULT 'acik', `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `destek_mesajlari` ( `mesaj_id` INT AUTO_INCREMENT PRIMARY KEY, `talep_id` INT NOT NULL, `gonderen_kullanici_id` INT NULL, `gonderen_admin_id` INT NULL, `mesaj_icerigi` TEXT NOT NULL, FOREIGN KEY (`talep_id`) REFERENCES `destek_talepleri`(`talep_id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `sayfalar` ( `sayfa_id` INT AUTO_INCREMENT PRIMARY KEY, `baslik` VARCHAR(255) NOT NULL, `slug` VARCHAR(255) NOT NULL UNIQUE, `icerik` MEDIUMTEXT ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `bannerlar` ( `banner_id` INT AUTO_INCREMENT PRIMARY KEY, `resim_url_mobil` VARCHAR(255) NOT NULL, `hedef_url` VARCHAR(255), `sira` INT DEFAULT 0, `konum` ENUM('anasayfa_ust', 'anasayfa_orta') NOT NULL DEFAULT 'anasayfa_ust', `aktif_mi` BOOLEAN DEFAULT TRUE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `sepetler` ( `sepet_id` INT AUTO_INCREMENT PRIMARY KEY, `kullanici_id` INT NOT NULL UNIQUE, `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `sepet_urunleri` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `sepet_id` INT NOT NULL, `varyant_id` INT NOT NULL, `adet` INT NOT NULL, UNIQUE(`sepet_id`, `varyant_id`), FOREIGN KEY (`sepet_id`) REFERENCES `sepetler`(`sepet_id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `kuponlar` ( `kupon_id` INT AUTO_INCREMENT PRIMARY KEY, `kullanici_id` INT NULL, `kupon_kodu` VARCHAR(50) NOT NULL UNIQUE, `indirim_tipi` ENUM('yuzde', 'sabit_tutar') NOT NULL, `indirim_degeri` DECIMAL(10, 2) NOT NULL, `aktif_mi` BOOLEAN DEFAULT TRUE, FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------
-- BAŞLANGIÇ VERİLERİ (SEED DATA)
-- --------------------------------------------------------

-- Fiyat Listeleri
INSERT INTO `fiyat_listeleri` (`liste_id`, `liste_adi`) VALUES (1, 'Perakende Fiyat Listesi'), (2, 'Bayi Fiyat Listesi');

-- Roller
INSERT INTO `roller` (`rol_id`, `rol_adi`, `fiyat_listesi_id`) VALUES
(1, 'super_admin', 1),
(2, 'magaza_yoneticisi', 1),
(3, 'siparis_yoneticisi', 1),
(4, 'kullanici', 1),
(5, 'destek_ekibi', 1),
(6, 'bayi', 2),
(7, 'depo_gorevlisi', NULL);

-- Yetkiler
INSERT INTO `yetkiler` (`yetki_id`, `yetki_kodu`, `aciklama`) VALUES
(1, 'urun_yonet', 'Ürün, kategori ve fiyat yönetimi'),
(2, 'siparis_yonet', 'Siparişlerin nihai durumlarını (teslim/iptal) yönetme'),
(3, 'kupon_yonet', 'Kupon yönetimi'),
(4, 'dashboard_goruntule', 'Admin paneli ana gösterge verilerini görme'),
(5, 'destek_yonet', 'Müşteri destek taleplerini yönetme'),
(6, 'cms_yonet', 'Sayfa ve banner gibi içerikleri yönetme'),
(7, 'siparis_toplama_listesi_gor', 'Depo için hazırlanacak siparişleri ve toplama listelerini görme'),
(8, 'siparis_kargola', 'Depoda hazırlanan bir siparişi kargoya verme');

-- Rol - Yetki İlişkileri
INSERT INTO `rol_yetki_iliskisi` (`rol_id`, `yetki_id`) VALUES
-- super_admin (her şeye erişir)
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8),
-- magaza_yoneticisi (ürün, kupon, cms)
(2, 1), (2, 3), (2, 6),
-- siparis_yoneticisi (nihai sipariş yönetimi)
(3, 2),
-- destek_ekibi
(5, 5),
-- depo_gorevlisi (sadece depo yetkileri)
(7, 7), (7, 8);

-- Test Kullanıcıları
INSERT INTO `kullanicilar` (`id`, `rol_id`, `ad_soyad`, `eposta`, `parola`) VALUES
(1, 1, 'Super Admin', 'admin@prosiparis.com', '$2y$10$E/g0j4Ug.g.y0J14TzKZ3.UDE4wsU.S2aP0/5Pz2.h.bE5NUD4NlG'),
(2, 4, 'Ali Veli', 'kullanici@prosiparis.com', '$2y$10$wT3/p0k.Xq9dce68Bsf6CeU0I0b2h1J2lK2.ZJ2.Xz0bJ5L1n8xJq'),
(3, 6, 'Bayi A.Ş.', 'bayi@prosiparis.com', '$2y$10$wT3/p0k.Xq9dce68Bsf6CeU0I0b2h1J2lK2.ZJ2.Xz0bJ5L1n8xJq'),
(4, 7, 'Depo Görevlisi', 'depo@prosiparis.com', '$2y$10$wT3/p0k.Xq9dce68Bsf6CeU0I0b2h1J2lK2.ZJ2.Xz0bJ5L1n8xJq');

-- Örnek Ürün ve Fiyatlar
INSERT INTO `urunler` (`urun_id`, `urun_adi`) VALUES (1, 'Örnek Tişört');
INSERT INTO `urun_varyantlari` (`varyant_id`, `urun_id`, `varyant_sku`, `stok_adedi`, `raf_kodu`) VALUES (1, 1, 'TSHIRT-RED-L', 50, 'A-01-C');
INSERT INTO `varyant_fiyatlari` (`varyant_id`, `fiyat_listesi_id`, `fiyat`) VALUES (1, 1, 150.00), (1, 2, 110.50);

-- --------------------------------------------------------
-- ÖDEME SEANSI (v2.7'ye göre güncellendi)
-- --------------------------------------------------------
CREATE TABLE `odeme_seanslari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `conversation_id` VARCHAR(255) NOT NULL UNIQUE,
  `kullanici_id` INT NOT NULL,
  `fiyat_listesi_id` INT NOT NULL,
  `sepet_verisi` JSON NOT NULL,
  `adres_verisi` JSON NOT NULL,
  `kargo_id` INT NOT NULL,
  `kullanilan_kupon_kodu` VARCHAR(50) NULL,
  `indirim_tutari` DECIMAL(10, 2) DEFAULT 0.00,
  `durum` VARCHAR(50) DEFAULT 'baslatildi',
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`fiyat_listesi_id`) REFERENCES `fiyat_listeleri`(`liste_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
