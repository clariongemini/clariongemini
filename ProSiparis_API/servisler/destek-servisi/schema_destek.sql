-- Destek-Servisi Veritabanı Şeması v4.3

CREATE TABLE `destek_talepleri` (
  `talep_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `konu` VARCHAR(255) NOT NULL,
  `durum` ENUM('Açık', 'Yanıtlandı', 'Kapandı') NOT NULL DEFAULT 'Açık',
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `destek_mesajlari` (
  `mesaj_id` INT AUTO_INCREMENT PRIMARY KEY,
  `talep_id` INT NOT NULL,
  `gonderen_id` INT NOT NULL, -- Kullanıcı veya Admin ID
  `mesaj` TEXT NOT NULL,
  `gonderme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`talep_id`) REFERENCES `destek_talepleri`(`talep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
