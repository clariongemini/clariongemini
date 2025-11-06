-- ProSiparis_API Veritabanı Şeması
-- Bu dosya, uygulamanın gerektirdiği tüm tabloları oluşturur.

-- --------------------------------------------------------

--
-- Tablo: `kullanicilar`
-- Kullanıcıların temel bilgilerini saklar.
--
CREATE TABLE `kullanicilar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_soyad` VARCHAR(100) NOT NULL,
  `eposta` VARCHAR(100) NOT NULL UNIQUE,
  `parola` VARCHAR(255) NOT NULL,
  `kayit_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tablo: `urunler`
-- Satışa sunulan ürünlerin bilgilerini içerir.
--
CREATE TABLE `urunler` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `urun_adi` VARCHAR(255) NOT NULL,
  `aciklama` TEXT,
  `fiyat` DECIMAL(10, 2) NOT NULL,
  `resim_url` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tablo: `siparisler`
-- Müşterilerin verdiği siparişlerin ana kayıtlarını tutar.
--
CREATE TABLE `siparisler` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `siparis_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `toplam_tutar` DECIMAL(10, 2) NOT NULL,
  `durum` VARCHAR(50) DEFAULT 'Hazırlanıyor',
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tablo: `siparis_detaylari`
-- Her bir siparişin içeriğindeki ürünleri detaylandırır.
--
CREATE TABLE `siparis_detaylari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `siparis_id` INT NOT NULL,
  `urun_id` INT NOT NULL,
  `adet` INT NOT NULL,
  `birim_fiyat` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`siparis_id`) REFERENCES `siparisler`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
