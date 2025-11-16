-- Tedarik-Servisi Veritabanı Şeması v3.2

CREATE TABLE `tedarikciler` (
  `tedarikci_id` INT AUTO_INCREMENT PRIMARY KEY,
  `firma_adi` VARCHAR(255) NOT NULL,
  `yetkili_kisi` VARCHAR(255) DEFAULT NULL,
  `eposta` VARCHAR(255) DEFAULT NULL,
  `telefon` VARCHAR(20) DEFAULT NULL,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `satin_alma_siparisleri` (
  `po_id` INT AUTO_INCREMENT PRIMARY KEY,
  `tedarikci_id` INT NOT NULL,
  `siparis_tarihi` DATE NOT NULL,
  `beklenen_teslim_tarihi` DATE DEFAULT NULL,
  `teslim_alinma_tarihi` DATETIME DEFAULT NULL,
  `durum` VARCHAR(50) NOT NULL DEFAULT 'Taslak',
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tedarikci_id`) REFERENCES `tedarikciler`(`tedarikci_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `satin_alma_siparis_urunleri` (
  `po_urun_id` INT AUTO_INCREMENT PRIMARY KEY,
  `po_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `siparis_edilen_adet` INT NOT NULL,
  `teslim_alınan_adet` INT DEFAULT 0,
  `maliyet_fiyati` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`po_id`) REFERENCES `satin_alma_siparisleri`(`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
