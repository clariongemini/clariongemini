-- ProSiparis_API Veritabanı Şeması v2.4
-- Sosyal Kanıt (Değerlendirme/Puan) ve Kullanıcı Etkileşimi (Favoriler) için güncellenmiştir.

-- Tabloları oluşturmadan önce, varsa eskilerini (ilişki sırasına göre) sil.
DROP TABLE IF EXISTS `urun_degerlendirmeleri`;
DROP TABLE IF EXISTS `kullanici_favorileri`;
DROP TABLE IF EXISTS `varyant_deger_iliskisi`;
DROP TABLE IF EXISTS `urun_varyantlari`;
DROP TABLE IF EXISTS `urun_nitelik_degerleri`;
DROP TABLE IF EXISTS `urun_nitelikleri`;
DROP TABLE IF EXISTS `siparis_detaylari`;
DROP TABLE IF EXISTS `siparisler`;
DROP TABLE IF EXISTS `kullanici_adresleri`;
DROP TABLE IF EXISTS `kargo_secenekleri`;
DROP TABLE IF EXISTS `odeme_seanslari`;
DROP TABLE IF EXISTS `kuponlar`;
DROP TABLE IF EXISTS `urunler`;
DROP TABLE IF EXISTS `kategoriler`;
DROP TABLE IF EXISTS `kullanicilar`;

-- --------------------------------------------------------

CREATE TABLE `kullanicilar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_soyad` VARCHAR(100) NOT NULL,
  `eposta` VARCHAR(100) NOT NULL UNIQUE,
  `parola` VARCHAR(255) NOT NULL,
  `rol` VARCHAR(50) NOT NULL DEFAULT 'kullanici',
  `tercih_dil` VARCHAR(10) NOT NULL DEFAULT 'tr-TR',
  `tercih_tema` VARCHAR(10) NOT NULL DEFAULT 'system',
  `kayit_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `kategoriler` (
  `kategori_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kategori_adi` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

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

-- --------------------------------------------------------

CREATE TABLE `urun_degerlendirmeleri` (
  `degerlendirme_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `urun_id` INT NOT NULL,
  `puan` INT NOT NULL CHECK (puan >= 1 AND puan <= 5),
  `yorum` TEXT NULL,
  `tarih` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`urun_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `kullanici_favorileri` (
  `favori_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `urun_id` INT NOT NULL,
  UNIQUE (`kullanici_id`, `urun_id`),
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`urun_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- --------------------------------------------------------

CREATE TABLE `kullanici_adresleri` (
  `adres_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `adres_basligi` VARCHAR(100) NOT NULL,
  `ad_soyad` VARCHAR(100) NOT NULL,
  `telefon` VARCHAR(20) NOT NULL,
  `adres_satiri` TEXT NOT NULL,
  `il` VARCHAR(100) NOT NULL,
  `ilce` VARCHAR(100) NOT NULL,
  `posta_kodu` VARCHAR(10) NULL,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `kargo_secenekleri` (
  `kargo_id` INT AUTO_INCREMENT PRIMARY KEY,
  `firma_adi` VARCHAR(100) NOT NULL,
  `aciklama` VARCHAR(255) NULL,
  `ucret` DECIMAL(10, 2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `kuponlar` (
  `kupon_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kupon_kodu` VARCHAR(50) NOT NULL UNIQUE,
  `indirim_tipi` ENUM('yuzde', 'sabit_tutar') NOT NULL,
  `indirim_degeri` DECIMAL(10, 2) NOT NULL,
  `son_kullanma_tarihi` DATETIME NULL,
  `minimum_sepet_tutari` DECIMAL(10, 2) DEFAULT 0.00,
  `kullanim_limiti` INT NULL,
  `kac_kez_kullanildi` INT DEFAULT 0,
  `aktif_mi` BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `odeme_seanslari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `conversation_id` VARCHAR(255) NOT NULL UNIQUE,
  `kullanici_id` INT NOT NULL,
  `sepet_verisi` JSON NOT NULL,
  `adres_verisi` JSON NOT NULL,
  `kargo_id` INT NOT NULL,
  `kullanilan_kupon_kodu` VARCHAR(50) NULL,
  `indirim_tutari` DECIMAL(10, 2) DEFAULT 0.00,
  `durum` VARCHAR(50) DEFAULT 'baslatildi',
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- --------------------------------------------------------

CREATE TABLE `urun_nitelikleri` (
  `nitelik_id` INT AUTO_INCREMENT PRIMARY KEY,
  `nitelik_adi` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `urun_nitelik_degerleri` (
  `deger_id` INT AUTO_INCREMENT PRIMARY KEY,
  `nitelik_id` INT NOT NULL,
  `deger_adi` VARCHAR(100) NOT NULL,
  FOREIGN KEY (`nitelik_id`) REFERENCES `urun_nitelikleri`(`nitelik_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `urun_varyantlari` (
  `varyant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `urun_id` INT NOT NULL,
  `varyant_sku` VARCHAR(100) NOT NULL UNIQUE,
  `fiyat` DECIMAL(10, 2) NOT NULL,
  `stok_adedi` INT NOT NULL DEFAULT 0,
  `resim_url` VARCHAR(255) NULL,
  FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`urun_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `varyant_deger_iliskisi` (
  `varyant_id` INT NOT NULL,
  `deger_id` INT NOT NULL,
  PRIMARY KEY (`varyant_id`, `deger_id`),
  FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`) ON DELETE CASCADE,
  FOREIGN KEY (`deger_id`) REFERENCES `urun_nitelik_degerleri`(`deger_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `siparisler` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `teslimat_adresi_id` INT NOT NULL,
  `kargo_id` INT NOT NULL,
  `siparis_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `toplam_tutar` DECIMAL(10, 2) NOT NULL,
  `indirim_tutari` DECIMAL(10, 2) DEFAULT 0.00,
  `kullanilan_kupon_kodu` VARCHAR(50) NULL,
  `durum` VARCHAR(50) DEFAULT 'Odeme Bekleniyor',
  `kargo_firmasi` VARCHAR(100) NULL,
  `kargo_takip_kodu` VARCHAR(100) NULL,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teslimat_adresi_id`) REFERENCES `kullanici_adresleri`(`adres_id`),
  FOREIGN KEY (`kargo_id`) REFERENCES `kargo_secenekleri`(`kargo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `siparis_detaylari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `siparis_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `adet` INT NOT NULL,
  `birim_fiyat` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`siparis_id`) REFERENCES `siparisler`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
