-- Medya-Servisi Veritabanı Şeması
CREATE TABLE `medya_dosyalari` (
  `dosya_id` INT AUTO_INCREMENT PRIMARY KEY,
  `dosya_adi` VARCHAR(255) NOT NULL,
  `yol` VARCHAR(512) NOT NULL,
  `dosya_tipi` VARCHAR(100) NOT NULL, -- mime_type
  `boyut` INT NOT NULL, -- bytes
  `yukleyen_kullanici_id` INT NULL,
  `depo_id` INT NULL, -- Opsiyonel, gelecekteki WMS entegrasyonu için
  `yuklenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
