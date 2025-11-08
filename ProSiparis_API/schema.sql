-- ProSiparis_API Veritabanı Şeması v2.9 - Tam Sürüm
DROP TABLE IF EXISTS `envanter_hareketleri`;
DROP TABLE IF EXISTS `iade_urunleri`;
DROP TABLE IF EXISTS `iade_talepleri`;
DROP TABLE IF EXISTS `satin_alma_siparis_urunleri`;
DROP TABLE IF EXISTS `satin_alma_siparisleri`;
DROP TABLE IF EXISTS `tedarikciler`;
-- (v2.8'den gelen diğer tüm DROP TABLE ifadeleri)
-- ...

-- --------------------------------------------------------
-- TEMEL YAPILAR
-- --------------------------------------------------------
CREATE TABLE `fiyat_listeleri` ( `liste_id` INT AUTO_INCREMENT PRIMARY KEY, `liste_adi` VARCHAR(100) NOT NULL UNIQUE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `roller` ( `rol_id` INT AUTO_INCREMENT PRIMARY KEY, `rol_adi` VARCHAR(100) NOT NULL UNIQUE, `fiyat_listesi_id` INT NULL, FOREIGN KEY (`fiyat_listesi_id`) REFERENCES `fiyat_listeleri`(`liste_id`) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `yetkiler` ( `yetki_id` INT AUTO_INCREMENT PRIMARY KEY, `yetki_kodu` VARCHAR(100) NOT NULL UNIQUE, `aciklama` VARCHAR(255) NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `rol_yetki_iliskisi` ( `rol_id` INT NOT NULL, `yetki_id` INT NOT NULL, PRIMARY KEY (`rol_id`, `yetki_id`), FOREIGN KEY (`rol_id`) REFERENCES `roller`(`rol_id`) ON DELETE CASCADE, FOREIGN KEY (`yetki_id`) REFERENCES `yetkiler`(`yetki_id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `kullanicilar` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `rol_id` INT NOT NULL, `ad_soyad` VARCHAR(100) NOT NULL, `eposta` VARCHAR(100) NOT NULL UNIQUE, `parola` VARCHAR(255) NOT NULL, FOREIGN KEY (`rol_id`) REFERENCES `roller`(`rol_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- KATALOG, ENVANTER, TEDARİK VE KÂRLILIK
-- --------------------------------------------------------
CREATE TABLE `kategoriler` ( `kategori_id` INT AUTO_INCREMENT PRIMARY KEY, `kategori_adi` VARCHAR(100) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `urunler` ( `urun_id` INT AUTO_INCREMENT PRIMARY KEY, `kategori_id` INT NULL, `urun_adi` VARCHAR(255) NOT NULL, FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler`(`kategori_id`) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `urun_varyantlari` ( `varyant_id` INT AUTO_INCREMENT PRIMARY KEY, `urun_id` INT NOT NULL, `varyant_sku` VARCHAR(100) NOT NULL UNIQUE, `stok_adedi` INT NOT NULL DEFAULT 0, `agirlikli_ortalama_maliyet` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000, `raf_kodu` VARCHAR(50) NULL, FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`urun_id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `varyant_fiyatlari` ( `fiyat_id` INT AUTO_INCREMENT PRIMARY KEY, `varyant_id` INT NOT NULL, `fiyat_listesi_id` INT NOT NULL, `fiyat` DECIMAL(10, 2) NOT NULL, UNIQUE(`varyant_id`, `fiyat_listesi_id`), FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`), FOREIGN KEY (`fiyat_listesi_id`) REFERENCES `fiyat_listeleri`(`liste_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `envanter_hareketleri` ( `hareket_id` BIGINT AUTO_INCREMENT PRIMARY KEY, `varyant_id` INT NOT NULL, `kullanici_id` INT, `hareket_tipi` ENUM('satin_alma', 'satis', 'iade_giris', 'sayim_duzeltme_giris', 'sayim_duzeltme_cikis') NOT NULL, `referans_id` INT, `degisim_miktari` INT NOT NULL, `onceki_stok` INT NOT NULL, `sonraki_stok` INT NOT NULL, `maliyet` DECIMAL(10, 4), `tarih` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`), FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `tedarikciler` ( `tedarikci_id` INT AUTO_INCREMENT PRIMARY KEY, `firma_adi` VARCHAR(255) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `satin_alma_siparisleri` ( `po_id` INT AUTO_INCREMENT PRIMARY KEY, `tedarikci_id` INT NOT NULL, `siparis_tarihi` DATE NOT NULL, `beklenen_teslim_tarihi` DATE, `durum` ENUM('Taslak', 'Gönderildi', 'Kısmen Teslim Alındı', 'Tamamlandı') NOT NULL, FOREIGN KEY (`tedarikci_id`) REFERENCES `tedarikciler`(`tedarikci_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `satin_alma_siparis_urunleri` ( `po_urun_id` INT AUTO_INCREMENT PRIMARY KEY, `po_id` INT NOT NULL, `varyant_id` INT NOT NULL, `siparis_edilen_adet` INT NOT NULL, `teslim_alinan_adet` INT NOT NULL DEFAULT 0, `maliyet_fiyati` DECIMAL(10, 2) NOT NULL, FOREIGN KEY (`po_id`) REFERENCES `satin_alma_siparisleri`(`po_id`), FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- SATIŞ, İADE (RMA) VE DİĞERLERİ
-- --------------------------------------------------------
CREATE TABLE `siparisler` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `kullanici_id` INT NOT NULL, `siparis_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `toplam_tutar` DECIMAL(10, 2) NOT NULL, `durum` ENUM('Odeme Bekleniyor', 'Odendi', 'Hazirlaniyor', 'Kargoya Verildi', 'Teslim Edildi', 'Iptal Edildi') NOT NULL, FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `siparis_detaylari` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `siparis_id` INT NOT NULL, `varyant_id` INT NOT NULL, `adet` INT NOT NULL, `birim_fiyat` DECIMAL(10, 2) NOT NULL, `maliyet_fiyati` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000, FOREIGN KEY (`siparis_id`) REFERENCES `siparisler`(`id`), FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `iade_talepleri` ( `iade_id` INT AUTO_INCREMENT PRIMARY KEY, `siparis_id` INT NOT NULL, `kullanici_id` INT NOT NULL, `durum` ENUM('Talep Edildi', 'Onaylandı', 'Reddedildi', 'Depoya Ulaştı', 'İade Tamamlandı') NOT NULL, FOREIGN KEY (`siparis_id`) REFERENCES `siparisler`(`id`), FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `iade_urunleri` ( `iade_urun_id` INT AUTO_INCREMENT PRIMARY KEY, `iade_id` INT NOT NULL, `varyant_id` INT NOT NULL, `adet` INT NOT NULL, `durum` ENUM('Satılabilir', 'Kusurlu') NOT NULL, FOREIGN KEY (`iade_id`) REFERENCES `iade_talepleri`(`iade_id`), FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- BAŞLANGIÇ VERİLERİ (SEED DATA)
-- --------------------------------------------------------
INSERT INTO `fiyat_listeleri` (`liste_id`, `liste_adi`) VALUES (1, 'Perakende'), (2, 'Bayi');
INSERT INTO `roller` (`rol_id`, `rol_adi`, `fiyat_listesi_id`) VALUES (1, 'super_admin', 1), (2, 'kullanici', 1), (3, 'bayi', 2), (4, 'depo_gorevlisi', NULL);
INSERT INTO `yetkiler` (`yetki_id`, `yetki_kodu`) VALUES (1,'tedarikci_yonet'),(2,'satin_alma_yonet'),(3,'satin_alma_teslim_al'),(4,'iade_yonet'),(5,'iade_teslim_al'),(6,'envanter_duzelt'),(7,'rapor_olustur');
INSERT INTO `rol_yetki_iliskisi` (`rol_id`, `yetki_id`) VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(4,3),(4,5),(4,6);
