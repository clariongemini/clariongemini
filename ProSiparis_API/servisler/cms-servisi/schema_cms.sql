-- CMS-Servisi Veritabanı Şeması v5.2

CREATE TABLE `sayfalar` (
  `sayfa_id` INT AUTO_INCREMENT PRIMARY KEY,
  `baslik` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `icerik` TEXT,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `bannerlar` (
  `banner_id` INT AUTO_INCREMENT PRIMARY KEY,
  `banner_adi` VARCHAR(255) NOT NULL,
  `resim_url` VARCHAR(255) NOT NULL,
  `hedef_url` VARCHAR(255) DEFAULT NULL,
  `aktif` BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- v5.2 Site Ayarları Tablosu
CREATE TABLE `site_ayarlari` (
  `ayar_id` INT AUTO_INCREMENT PRIMARY KEY,
  `ayar_anahtari` VARCHAR(100) NOT NULL UNIQUE,
  `ayar_degeri` TEXT,
  `aciklama` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- robots.txt için varsayılan bir değer ekleyelim.
INSERT INTO `site_ayarlari` (`ayar_anahtari`, `ayar_degeri`, `aciklama`) VALUES
('robots_txt_icerigi', 'User-agent: *\nAllow: /', 'Arama motorları için robots.txt dosyasının içeriği.');
