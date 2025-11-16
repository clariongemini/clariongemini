-- Organizasyon-Servisi Veritabanı Şeması v5.2

CREATE TABLE `depolar` (
  `depo_id` INT AUTO_INCREMENT PRIMARY KEY,
  `depo_adi` VARCHAR(255) NOT NULL,
  `depo_kodu` VARCHAR(50) NOT NULL UNIQUE,
  `adres` TEXT,
  `aktif` BOOLEAN NOT NULL DEFAULT TRUE,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- v5.2 Entegrasyon API Anahtar Kasası
CREATE TABLE `entegrasyon_anahtarlari` (
  `anahtar_id` INT AUTO_INCREMENT PRIMARY KEY,
  `servis_adi` VARCHAR(100) NOT NULL,
  `anahtar_adi` VARCHAR(100) NOT NULL,
  `anahtar_degeri` VARBINARY(255) NOT NULL, -- Şifrelenmiş anahtar değeri
  `iv` VARBINARY(16) NOT NULL, -- Şifreleme için Initialization Vector
  `aciklama` TEXT,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `servis_anahtar_unique` (`servis_adi`, `anahtar_adi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
