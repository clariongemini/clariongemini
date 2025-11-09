-- Iade-Servisi Veritabanı Şeması v3.2

CREATE TABLE `iade_talepleri` (
  `iade_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `siparis_id` INT NOT NULL,
  `neden` TEXT,
  `durum` VARCHAR(50) NOT NULL DEFAULT 'Talep Alındı',
  `odeme_referans` VARCHAR(255) DEFAULT NULL,
  `talep_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `iade_urunleri` (
  `iade_urun_id` INT AUTO_INCREMENT PRIMARY KEY,
  `iade_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `adet` INT NOT NULL,
  `durum` VARCHAR(50) NOT NULL DEFAULT 'Bekleniyor',
  FOREIGN KEY (`iade_id`) REFERENCES `iade_talepleri`(`iade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
