-- ProSiparis_API Veritabanı Şeması v2.6
-- Müşteri Destek Sistemi, Headless CMS ve Otomasyon Motoru için güncellenmiştir.

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
DROP TABLE IF EXISTS `varyant_deger_iliskisi`;
DROP TABLE IF EXISTS `urun_varyantlari`;
DROP TABLE IF EXISTS `urun_nitelik_degerleri`;
DROP TABLE IF EXISTS `urun_nitelikleri`;
DROP TABLE IF EXISTS `kuponlar`;
DROP TABLE IF EXISTS `urunler`;
DROP TABLE IF EXISTS `kategoriler`;
DROP TABLE IF EXISTS `kargo_secenekleri`;

-- --------------------------------------------------------
-- TEMEL ACL TABLOLARI
-- --------------------------------------------------------

CREATE TABLE `roller` (
  `rol_id` INT AUTO_INCREMENT PRIMARY KEY,
  `rol_adi` VARCHAR(100) NOT NULL UNIQUE
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

-- --------------------------------------------------------
-- KULLANICI VE MÜŞTERİ DESTEK SİSTEMİ
-- --------------------------------------------------------

CREATE TABLE `kullanicilar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rol_id` INT NOT NULL,
  `ad_soyad` VARCHAR(100) NOT NULL,
  `eposta` VARCHAR(100) NOT NULL UNIQUE,
  `parola` VARCHAR(255) NOT NULL,
  `tercih_dil` VARCHAR(10) NOT NULL DEFAULT 'tr-TR',
  `tercih_tema` VARCHAR(10) NOT NULL DEFAULT 'system',
  `kayit_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`rol_id`) REFERENCES `roller`(`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `destek_talepleri` (
  `talep_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `siparis_id` INT NULL,
  `konu` VARCHAR(255) NOT NULL,
  `durum` ENUM('acik', 'cevaplandi', 'kapandi') NOT NULL DEFAULT 'acik',
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `destek_mesajlari` (
  `mesaj_id` INT AUTO_INCREMENT PRIMARY KEY,
  `talep_id` INT NOT NULL,
  `gonderen_kullanici_id` INT NULL,
  `gonderen_admin_id` INT NULL,
  `mesaj_icerigi` TEXT NOT NULL,
  `tarih` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`talep_id`) REFERENCES `destek_talepleri`(`talep_id`) ON DELETE CASCADE,
  FOREIGN KEY (`gonderen_kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`gonderen_admin_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------
-- HEADLESS CMS (İÇERİK YÖNETİM SİSTEMİ)
-- --------------------------------------------------------

CREATE TABLE `sayfalar` (
  `sayfa_id` INT AUTO_INCREMENT PRIMARY KEY,
  `baslik` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `icerik` MEDIUMTEXT,
  `meta_baslik` VARCHAR(255),
  `meta_aciklama` TEXT,
  `aktif_mi` BOOLEAN DEFAULT TRUE,
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `bannerlar` (
  `banner_id` INT AUTO_INCREMENT PRIMARY KEY,
  `baslik` VARCHAR(255),
  `resim_url_mobil` VARCHAR(255) NOT NULL,
  `hedef_url` VARCHAR(255),
  `sira` INT DEFAULT 0,
  `konum` ENUM('anasayfa_ust', 'anasayfa_orta') NOT NULL DEFAULT 'anasayfa_ust',
  `aktif_mi` BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------
-- KALICI SEPET VE OTOMASYON
-- --------------------------------------------------------

CREATE TABLE `sepetler` (
  `sepet_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL UNIQUE, -- Her kullanıcının sadece bir sepeti olabilir
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sepet_urunleri` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sepet_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `adet` INT NOT NULL,
  UNIQUE(`sepet_id`, `varyant_id`), -- Bir ürün sepete sadece bir kez eklenebilir
  FOREIGN KEY (`sepet_id`) REFERENCES `sepetler`(`sepet_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- E-TİCARET ÇEKİRDEK TABLOLARI
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
  `resim_url` VARCHAR(255) NULL,
  `ortalama_puan` DECIMAL(3, 2) DEFAULT 0.00,
  `degerlendirme_sayisi` INT DEFAULT 0,
  FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler`(`kategori_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `urun_varyantlari` (
  `varyant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `urun_id` INT NOT NULL,
  `varyant_sku` VARCHAR(100) NOT NULL UNIQUE,
  `fiyat` DECIMAL(10, 2) NOT NULL,
  `stok_adedi` INT NOT NULL DEFAULT 0,
  `resim_url` VARCHAR(255) NULL,
  FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`urun_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `kuponlar` (
  `kupon_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NULL, -- Kişiye özel kuponlar için
  `kupon_kodu` VARCHAR(50) NOT NULL UNIQUE,
  `indirim_tipi` ENUM('yuzde', 'sabit_tutar') NOT NULL,
  `indirim_degeri` DECIMAL(10, 2) NOT NULL,
  `son_kullanma_tarihi` DATETIME NULL,
  `minimum_sepet_tutari` DECIMAL(10, 2) DEFAULT 0.00,
  `kullanim_limiti` INT NULL,
  `kac_kez_kullanildi` INT DEFAULT 0,
  `aktif_mi` BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `siparisler` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `teslimat_adresi_id` INT NOT NULL,
  `kargo_id` INT NOT NULL,
  `siparis_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `toplam_tutar` DECIMAL(10, 2) NOT NULL,
  `durum` VARCHAR(50) DEFAULT 'Odeme Bekleniyor',
  -- Diğer alanlar...
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `siparis_detaylari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `siparis_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `adet` INT NOT NULL,
  `birim_fiyat` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`siparis_id`) REFERENCES `siparisler`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (Diğer tablolar: urun_degerlendirmeleri, kullanici_favorileri, kargo_secenekleri vb. buraya eklenebilir)
-- Tamlık için kalan tabloları ekleyelim:
CREATE TABLE `kullanici_adresleri` (
  `adres_id` INT AUTO_INCREMENT PRIMARY KEY, `kullanici_id` INT NOT NULL, `adres_basligi` VARCHAR(100) NOT NULL, `ad_soyad` VARCHAR(100) NOT NULL, `telefon` VARCHAR(20) NOT NULL, `adres_satiri` TEXT NOT NULL, `il` VARCHAR(100) NOT NULL, `ilce` VARCHAR(100) NOT NULL, `posta_kodu` VARCHAR(10) NULL, FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `kargo_secenekleri` ( `kargo_id` INT AUTO_INCREMENT PRIMARY KEY, `firma_adi` VARCHAR(100) NOT NULL, `aciklama` VARCHAR(255) NULL, `ucret` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `urun_degerlendirmeleri` ( `degerlendirme_id` INT AUTO_INCREMENT PRIMARY KEY, `kullanici_id` INT NOT NULL, `urun_id` INT NOT NULL, `puan` INT NOT NULL, `yorum` TEXT NULL, `tarih` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE, FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`urun_id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `kullanici_favorileri` ( `favori_id` INT AUTO_INCREMENT PRIMARY KEY, `kullanici_id` INT NOT NULL, `urun_id` INT NOT NULL, UNIQUE (`kullanici_id`, `urun_id`), FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE, FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`urun_id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `odeme_seanslari` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `conversation_id` VARCHAR(255) NOT NULL UNIQUE, `kullanici_id` INT NOT NULL, `sepet_verisi` JSON NOT NULL, `adres_verisi` JSON NOT NULL, `kargo_id` INT NOT NULL, `durum` VARCHAR(50) DEFAULT 'baslatildi', FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- BAŞLANGIÇ VERİLERİ (SEED DATA)
-- --------------------------------------------------------

INSERT INTO `roller` (`rol_id`, `rol_adi`) VALUES (1, 'super_admin'), (2, 'magaza_yoneticisi'), (3, 'siparis_yoneticisi'), (4, 'kullanici'), (5, 'destek_ekibi');
INSERT INTO `yetkiler` (`yetki_id`, `yetki_kodu`, `aciklama`) VALUES
(1, 'urun_yonet', 'Ürün ve kategori yönetimi'),
(2, 'siparis_yonet', 'Sipariş yönetimi'),
(3, 'kupon_yonet', 'Kupon yönetimi'),
(4, 'degerlendirme_yonet', 'Değerlendirme yönetimi'),
(5, 'dashboard_goruntule', 'Admin paneli ana gösterge verilerini görme'),
(6, 'destek_yonet', 'Müşteri destek taleplerini yönetme'),
(7, 'cms_yonet', 'Sayfa ve banner gibi içerikleri yönetme');

-- Rol - Yetki İlişkileri
INSERT INTO `rol_yetki_iliskisi` (`rol_id`, `yetki_id`) VALUES
-- super_admin (her şeye erişir)
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7),
-- magaza_yoneticisi (ürün, kupon, cms)
(2, 1), (2, 3), (2, 7),
-- siparis_yoneticisi
(3, 2),
-- destek_ekibi
(5, 6);

-- Test Kullanıcıları
-- Parola: 'SuperAdmin123!'
INSERT INTO `kullanicilar` (`id`, `rol_id`, `ad_soyad`, `eposta`, `parola`) VALUES (1, 1, 'Super Admin', 'admin@prosiparis.com', '$2y$10$E/g0j4Ug.g.y0J14TzKZ3.UDE4wsU.S2aP0/5Pz2.h.bE5NUD4NlG');
-- Parola: 'Kullanici123!'
INSERT INTO `kullanicilar` (`id`, `rol_id`, `ad_soyad`, `eposta`, `parola`) VALUES (2, 4, 'Ali Veli', 'kullanici@prosiparis.com', '$2y$10$wT3/p0k.Xq9dce68Bsf6CeU0I0b2h1J2lK2.ZJ2.Xz0bJ5L1n8xJq');

-- Örnek Statik Sayfa
INSERT INTO `sayfalar` (`baslik`, `slug`, `icerik`) VALUES ('Hakkımızda', 'hakkimizda', '<h1>Hakkımızda</h1><p>Burası hakkımızda sayfası içeriğidir.</p>');
