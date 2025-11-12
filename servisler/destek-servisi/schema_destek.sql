CREATE TABLE `destek_talepleri` (
  `talep_id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `konu` varchar(255) NOT NULL,
  `durum` enum('acik','cevaplandi','kapandi') NOT NULL DEFAULT 'acik',
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `son_guncelleme_tarihi` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`talep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `destek_talep_mesajlari` (
  `mesaj_id` int(11) NOT NULL AUTO_INCREMENT,
  `talep_id` int(11) NOT NULL,
  `gonderen_id` int(11) NOT NULL, -- 0 admin/sistem, diğerleri kullanıcı ID'si
  `mesaj` text NOT NULL,
  `gonderilme_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`mesaj_id`),
  KEY `talep_id` (`talep_id`),
  CONSTRAINT `destek_talep_mesajlari_ibfk_1` FOREIGN KEY (`talep_id`) REFERENCES `destek_talepleri` (`talep_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
