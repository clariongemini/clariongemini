-- Organizasyon-Servisi Veritabanı Şeması v4.0

CREATE TABLE `depolar` (
  `depo_id` INT AUTO_INCREMENT PRIMARY KEY,
  `depo_adi` VARCHAR(255) NOT NULL,
  `depo_kodu` VARCHAR(50) NOT NULL UNIQUE,
  `adres` TEXT,
  `aktif` BOOLEAN NOT NULL DEFAULT TRUE,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
