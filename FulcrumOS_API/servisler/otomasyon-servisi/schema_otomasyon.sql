-- Otomasyon-Servisi Veritabanı Şeması v4.3 (Kalıcı Sepet)

CREATE TABLE `sepetler` (
  `sepet_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL UNIQUE,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sepet_urunleri` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sepet_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `adet` INT NOT NULL,
  FOREIGN KEY (`sepet_id`) REFERENCES `sepetler`(`sepet_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
