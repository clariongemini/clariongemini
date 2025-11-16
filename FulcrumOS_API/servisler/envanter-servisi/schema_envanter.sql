-- Envanter-Servisi Veritabanı Şeması v4.0 (WMS Refaktörü)

-- NOT: Bu şema, Katalog-Servisi'ndeki urun_varyantlari tablosuna bir "foreign key" içermez.
-- Servisler arası veri bütünlüğü, uygulama katmanında veya asenkron olaylarla sağlanır.

-- KALDIRILACAK: urun_varyantlari.stok_adedi, urun_varyantlari.agirlikli_ortalama_maliyet
-- Bu sütunlar artık Katalog-Servisi'nde de bulunmamalıdır. (Bu refaktörün bir parçası olarak varsayılmıştır)

-- YENİ: Adet bazlı ürünlerin depo bazında stoklarını tutar.
CREATE TABLE `depo_stoklari` (
  `depo_stok_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `depo_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `adet` INT NOT NULL DEFAULT 0,
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_depo_varyant` (`depo_id`, `varyant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- YENİ: Ağırlıklı Ortalama Maliyet (AOM/WAC) artık depo bazlı hesaplanır.
CREATE TABLE `depo_stok_maliyetleri` (
  `maliyet_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `depo_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `agirlikli_ortalama_maliyet` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
  UNIQUE KEY `idx_depo_varyant_maliyet` (`depo_id`, `varyant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- YENİ: Seri numarası ile takip edilen her bir fiziksel ürün için bir kayıt tutar.
CREATE TABLE `envanter_seri_numaralari` (
  `seri_no_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `seri_no` VARCHAR(255) NOT NULL UNIQUE,
  `varyant_id` INT NOT NULL,
  `depo_id` INT NOT NULL,
  `durum` ENUM('stokta', 'satildi', 'iade_kusurlu', 'iade_satilabilir') NOT NULL DEFAULT 'stokta',
  `po_id` INT DEFAULT NULL, -- Hangi PO ile girdiği
  `siparis_id` INT DEFAULT NULL, -- Hangi sipariş ile satıldığı
  `iade_id` INT DEFAULT NULL, -- Hangi iade ile döndüğü
  `giris_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- GÜNCELLENDİ: Envanter hareket (ledger) tablosuna depo bilgisi eklendi.
CREATE TABLE `envanter_hareketleri` (
  `hareket_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `varyant_id` INT NOT NULL,
  `depo_id` INT NOT NULL, -- YENİ SÜTUN
  `hareket_tipi` VARCHAR(50) NOT NULL, -- 'satis', 'satin_alma', 'iade_giris', 'stok_sayim_duzeltme', 'depo_transferi'
  `adet_degisimi` INT NOT NULL, -- Pozitif (giriş) veya negatif (çıkış)
  `son_stok` INT NOT NULL,
  `referans_id` INT DEFAULT NULL, -- Sipariş ID, PO ID, İade ID etc.
  `maliyet` DECIMAL(10, 2) DEFAULT NULL,
  `kullanici_id` INT DEFAULT NULL,
  `hareket_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
