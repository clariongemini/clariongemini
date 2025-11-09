-- Siparis-Servisi Veritabanı Şeması v3.1
DROP TABLE IF EXISTS `siparis_detaylari`;
DROP TABLE IF EXISTS `siparisler`;
DROP TABLE IF EXISTS `odeme_seanslari`;
DROP TABLE IF EXISTS `kullanici_adresleri`;
DROP TABLE IF EXISTS `kargo_secenekleri`;

CREATE TABLE `kargo_secenekleri` ( `kargo_id` INT AUTO_INCREMENT PRIMARY KEY, `firma_adi` VARCHAR(100) NOT NULL, `ucret` DECIMAL(10, 2) NOT NULL DEFAULT 0.00) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `kullanici_adresleri` ( `adres_id` INT AUTO_INCREMENT PRIMARY KEY, `kullanici_id` INT NOT NULL, `adres_basligi` VARCHAR(100) NOT NULL, `adres_satiri` TEXT NOT NULL, `il` VARCHAR(100) NOT NULL, `ilce` VARCHAR(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `odeme_seanslari` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `conversation_id` VARCHAR(255) NOT NULL UNIQUE, `kullanici_id` INT NOT NULL, `fiyat_listesi_id` INT NOT NULL, `sepet_verisi` JSON NOT NULL, `adres_verisi` JSON NOT NULL, `kargo_id` INT NOT NULL, `kullanilan_kupon_kodu` VARCHAR(50) NULL, `indirim_tutari` DECIMAL(10, 2) DEFAULT 0.00, `durum` VARCHAR(50) DEFAULT 'baslatildi') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `siparisler` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `kullanici_id` INT NOT NULL, `toplam_tutar` DECIMAL(10, 2) NOT NULL, `durum` ENUM('Odeme Bekleniyor', 'Odendi', 'Hazirlaniyor', 'Kargoya Verildi', 'Teslim Edildi', 'Iptal Edildi') NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `siparis_detaylari` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `siparis_id` INT NOT NULL, `varyant_id` INT NOT NULL, `adet` INT NOT NULL, `birim_fiyat` DECIMAL(10, 2) NOT NULL, `maliyet_fiyati` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000, FOREIGN KEY (`siparis_id`) REFERENCES `siparisler`(`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
