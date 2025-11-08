-- Ana Monolith (Legacy Core) Veritabanı Şeması
CREATE TABLE `siparisler` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `kullanici_id` INT NOT NULL, `toplam_tutar` DECIMAL(10, 2) NOT NULL, `durum` ENUM('Odeme Bekleniyor', 'Odendi', 'Hazirlaniyor', 'Kargoya Verildi', 'Teslim Edildi', 'Iptal Edildi') NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `siparis_detaylari` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `siparis_id` INT NOT NULL, `varyant_id` INT NOT NULL, `adet` INT NOT NULL, `birim_fiyat` DECIMAL(10, 2) NOT NULL, `maliyet_fiyati` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000, FOREIGN KEY (`siparis_id`) REFERENCES `siparisler`(`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `iade_talepleri` ( `iade_id` INT AUTO_INCREMENT PRIMARY KEY, `siparis_id` INT NOT NULL, `kullanici_id` INT NOT NULL, `durum` ENUM('Talep Edildi', 'Onaylandı', 'Reddedildi', 'Depoya Ulaştı', 'İade Tamamlandı') NOT NULL, FOREIGN KEY (`siparis_id`) REFERENCES `siparisler`(`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `iade_urunleri` ( `iade_urun_id` INT AUTO_INCREMENT PRIMARY KEY, `iade_id` INT NOT NULL, `varyant_id` INT NOT NULL, `adet` INT NOT NULL, `durum` ENUM('Satılabilir', 'Kusurlu') NOT NULL, FOREIGN KEY (`iade_id`) REFERENCES `iade_talepleri`(`iade_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `tedarikciler` ( `tedarikci_id` INT AUTO_INCREMENT PRIMARY KEY, `firma_adi` VARCHAR(255) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `satin_alma_siparisleri` ( `po_id` INT AUTO_INCREMENT PRIMARY KEY, `tedarikci_id` INT NOT NULL, `siparis_tarihi` DATE NOT NULL, `durum` ENUM('Taslak', 'Gönderildi', 'Kısmen Teslim Alındı', 'Tamamlandı') NOT NULL, FOREIGN KEY (`tedarikci_id`) REFERENCES `tedarikciler`(`tedarikci_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `satin_alma_siparis_urunleri` ( `po_urun_id` INT AUTO_INCREMENT PRIMARY KEY, `po_id` INT NOT NULL, `varyant_id` INT NOT NULL, `siparis_edilen_adet` INT NOT NULL, `teslim_alinan_adet` INT NOT NULL DEFAULT 0, `maliyet_fiyati` DECIMAL(10, 2) NOT NULL, FOREIGN KEY (`po_id`) REFERENCES `satin_alma_siparisleri`(`po_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- ... (ve diğer tüm monolith'e ait tablolar)

-- --------------------------------------------------------
-- EVENT BUS SİMÜLASYONU
-- --------------------------------------------------------
CREATE TABLE `olay_gunlugu` (
  `olay_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `olay_tipi` VARCHAR(100) NOT NULL,
  `veri` JSON NOT NULL,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
