-- Kupon-Servisi Veritabanı Şeması v4.2

CREATE TABLE `kuponlar` (
  `kupon_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kupon_kodu` VARCHAR(50) NOT NULL UNIQUE,
  `indirim_tipi` ENUM('yuzde', 'sabit') NOT NULL,
  `indirim_degeri` DECIMAL(10, 2) NOT NULL,
  `son_kullanim_tarihi` DATE DEFAULT NULL,
  `kullanim_limiti` INT DEFAULT 1,
  `kac_kez_kullanildi` INT DEFAULT 0,
  `aktif` BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `kupon_kullanim_loglari` (
  `log_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `kupon_id` INT NOT NULL,
  `siparis_id` INT NOT NULL,
  `kullanici_id` INT NOT NULL,
  `kullanim_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kupon_id`) REFERENCES `kuponlar`(`kupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
