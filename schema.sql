-- ProSiparis_API Veritabanı Şeması v2.1
-- Ürün Varyantları, Kategoriler ve Envanter Yönetimi için güncellenmiştir.

-- Tabloları oluşturmadan önce, varsa eskilerini (ilişki sırasına göre) sil.
DROP TABLE IF EXISTS `varyant_deger_iliskisi`;
DROP TABLE IF EXISTS `urun_varyantlari`;
DROP TABLE IF EXISTS `urun_nitelik_degerleri`;
DROP TABLE IF EXISTS `urun_nitelikleri`;
DROP TABLE IF EXISTS `siparis_detaylari`;
DROP TABLE IF EXISTS `siparisler`;
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
  `kategori_adi` VARCHAR(100) NOT NULL,
  `ust_kategori_id` INT NULL,
  FOREIGN KEY (`ust_kategori_id`) REFERENCES `kategoriler`(`kategori_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `urunler` (
  `urun_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kategori_id` INT NULL,
  `urun_adi` VARCHAR(255) NOT NULL,
  `aciklama` TEXT,
  `stok_kodu` VARCHAR(100) NULL UNIQUE COMMENT 'Ana ürün SKU',
  `resim_url` VARCHAR(255) NULL COMMENT 'Ana ürün resmi',
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler`(`kategori_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `urun_nitelikleri` (
  `nitelik_id` INT AUTO_INCREMENT PRIMARY KEY,
  `nitelik_adi` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Örn: Renk, Beden'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `urun_nitelik_degerleri` (
  `deger_id` INT AUTO_INCREMENT PRIMARY KEY,
  `nitelik_id` INT NOT NULL,
  `deger_adi` VARCHAR(100) NOT NULL COMMENT 'Örn: Kırmızı, Large',
  FOREIGN KEY (`nitelik_id`) REFERENCES `urun_nitelikleri`(`nitelik_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `urun_varyantlari` (
  `varyant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `urun_id` INT NOT NULL,
  `varyant_sku` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Benzersiz Varyant SKU',
  `fiyat` DECIMAL(10, 2) NOT NULL,
  `stok_adedi` INT NOT NULL DEFAULT 0,
  `resim_url` VARCHAR(255) NULL COMMENT 'Varyanta özel resim',
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
  `siparis_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `toplam_tutar` DECIMAL(10, 2) NOT NULL,
  `durum` VARCHAR(50) DEFAULT 'Hazırlanıyor',
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

CREATE TABLE `siparis_detaylari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `siparis_id` INT NOT NULL,
  `varyant_id` INT NOT NULL, -- urun_id yerine varyant_id
  `adet` INT NOT NULL,
  `birim_fiyat` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`siparis_id`) REFERENCES `siparisler`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
