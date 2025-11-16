-- AI-Asistan-Servisi Veritabanı Şeması v6.0

CREATE TABLE `urun_vektorleri` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `varyant_id` INT NOT NULL UNIQUE,
  `urun_adi` TEXT NOT NULL,
  `aciklama_vektoru` TEXT, -- JSON olarak saklanan vektör
  `kategori_vektoru` TEXT, -- JSON olarak saklanan vektör
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
