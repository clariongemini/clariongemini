CREATE TABLE `tedarikciler` (
  `tedarikci_id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_adi` varchar(255) NOT NULL,
  `yetkili_kisi` varchar(255) DEFAULT NULL,
  `eposta` varchar(255) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`tedarikci_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tedarik_siparisleri` (
  `po_id` int(11) NOT NULL AUTO_INCREMENT,
  `tedarikci_id` int(11) NOT NULL,
  `hedef_depo_id` int(11) NOT NULL,
  `durum` enum('taslak','gonderildi','kismen_teslim_alindi','tamamlandi') NOT NULL DEFAULT 'taslak',
  `beklenen_teslim_tarihi` datetime DEFAULT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`po_id`),
  KEY `tedarikci_id` (`tedarikci_id`),
  CONSTRAINT `tedarik_siparisleri_ibfk_1` FOREIGN KEY (`tedarikci_id`) REFERENCES `tedarikciler` (`tedarikci_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tedarik_siparis_urunleri` (
  `po_urun_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `varyant_id` int(11) NOT NULL,
  `siparis_edilen_adet` int(11) NOT NULL,
  `teslim_alinan_adet` int(11) NOT NULL DEFAULT 0,
  `maliyet_fiyati` decimal(10,2) NOT NULL,
  PRIMARY KEY (`po_urun_id`),
  KEY `po_id` (`po_id`),
  CONSTRAINT `tedarik_siparis_urunleri_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `tedarik_siparisleri` (`po_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tedarik_gecmisi_loglari` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `yapan_kullanici_id` int(11) NOT NULL,
  `eylem` varchar(255) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `tarih` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `po_id` (`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
