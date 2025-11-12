-- Katalog-Servisi Veritabanı Şeması v5.2

CREATE TABLE `kategoriler` (
  `kategori_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kategori_adi` VARCHAR(100) NOT NULL,
  -- v5.2 SEO Alanları
  `meta_baslik` VARCHAR(255) NULL,
  `meta_aciklama` TEXT NULL,
  `og_resim_url` VARCHAR(255) NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `canonical_url` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `urunler` (
  `urun_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kategori_id` INT NULL,
  `urun_adi` VARCHAR(255) NOT NULL,
  `takip_yontemi` ENUM('adet', 'seri_no') NOT NULL DEFAULT 'adet',
  -- v5.2 SEO Alanları
  `meta_baslik` VARCHAR(255) NULL,
  `meta_aciklama` TEXT NULL,
  `og_resim_url` VARCHAR(255) NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `canonical_url` VARCHAR(255) NULL,
  -- v5.2 Merchant Alanları
  `marka` VARCHAR(100) NULL,
  FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler`(`kategori_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `urun_varyantlari` (
  `varyant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `urun_id` INT NOT NULL,
  `varyant_sku` VARCHAR(100) NOT NULL UNIQUE,
  `raf_kodu` VARCHAR(50) NULL,
  -- v5.2 Merchant Alanları
  `gtin` VARCHAR(20) NULL,
  `mpn` VARCHAR(100) NULL,
  FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`urun_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `fiyat_listeleri` (
  `liste_id` INT AUTO_INCREMENT PRIMARY KEY,
  `liste_adi` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `varyant_fiyatlari` (
  `fiyat_id` INT AUTO_INCREMENT PRIMARY KEY,
  `varyant_id` INT NOT NULL,
  `fiyat_listesi_id` INT NOT NULL,
  `fiyat` DECIMAL(10, 2) NOT NULL,
  UNIQUE(`varyant_id`, `fiyat_listesi_id`),
  FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`),
  FOREIGN KEY (`fiyat_listesi_id`) REFERENCES `fiyat_listeleri`(`liste_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
