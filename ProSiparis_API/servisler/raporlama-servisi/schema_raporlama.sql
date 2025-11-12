-- Raporlama-Servisi Veritabanı Şeması v4.1 (OLAP Veri Ambarı)

-- Bu tablo, siparişler kargolandıkça doldurulur ve satış analizleri için kullanılır.
-- Veri tekrarı (denormalizasyon) kasıtlıdır ve sorgu performansını artırmak içindir.
CREATE TABLE `rapor_satis_ozetleri` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `siparis_id` INT NOT NULL,
  `siparis_tarihi` DATETIME NOT NULL,
  `depo_id` INT NOT NULL,
  `depo_adi` VARCHAR(255),
  `urun_adi` VARCHAR(255),
  `varyant_sku` VARCHAR(100),
  `kategori_adi` VARCHAR(100),
  `musteri_id` INT,
  `musteri_adi` VARCHAR(255), -- Gerçekte bu bilgi Auth-Servisi'nden gelir.
  `adet` INT NOT NULL,
  `birim_fiyat` DECIMAL(10, 2) NOT NULL,
  `birim_maliyet` DECIMAL(10, 4) NOT NULL,
  `toplam_ciro` DECIMAL(12, 2) GENERATED ALWAYS AS (adet * birim_fiyat) STORED,
  `toplam_maliyet` DECIMAL(12, 4) GENERATED ALWAYS AS (adet * birim_maliyet) STORED,
  `toplam_kar` DECIMAL(12, 4) GENERATED ALWAYS AS ((adet * birim_fiyat) - (adet * birim_maliyet)) STORED,
  `islenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bu tablo, envanter hareketlerini yansıtır ve stok analizleri için kullanılır.
CREATE TABLE `rapor_stok_hareketleri` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `hareket_id` BIGINT NOT NULL, -- Orijinal envanter_hareketleri tablosundaki ID
  `hareket_tarihi` TIMESTAMP NOT NULL,
  `depo_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `varyant_sku` VARCHAR(100),
  `hareket_tipi` VARCHAR(50) NOT NULL,
  `adet_degisimi` INT NOT NULL,
  `son_stok` INT NOT NULL,
  `referans_id` INT,
  `islenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
