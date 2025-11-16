-- FulcrumOS v10.3: Entegrasyon Servisi Veritabanı Şeması
-- Muhasebe Entegrasyon Logları Tablosu

CREATE TABLE `muhasebe_loglari` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `olay_tipi` ENUM('satis', 'iade', 'maliyet') NOT NULL COMMENT 'İşlemin tipini belirtir (Satış Faturası, İade Faturası, Alış/Maliyet Kaydı)',
  `referans_id` INT NOT NULL COMMENT 'İlgili sipariş, iade veya tedarik PO IDsi',
  `durum` ENUM('beklemede', 'basarili', 'hata') NOT NULL DEFAULT 'beklemede' COMMENT 'Entegrasyon işleminin mevcut durumu',
  `deneme_sayisi` TINYINT NOT NULL DEFAULT 0 COMMENT 'İşlemin kaç kez denendiği',
  `son_deneme_tarihi` DATETIME DEFAULT NULL COMMENT 'İşlemin son denendiği zaman',
  `hata_mesaji` TEXT DEFAULT NULL COMMENT 'Hata durumunda oluşan mesaj',
  `olusturulan_xml_veya_json` TEXT NOT NULL COMMENT 'Muhasebe sistemine gönderilecek jenerik formatlı veri',
  `olusturma_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
