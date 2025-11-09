-- Ana Monolith (Legacy Core) Veritabanı Şeması v3.2 (Temizlenmiş)
-- Ana Monolith (Legacy Core) Veritabanı Şeması v4.2 (Temizlenmiş)

CREATE TABLE `bannerlar` ( /* ... */ );
CREATE TABLE `destek_talepleri` ( /* ... */ );
CREATE TABLE `destek_mesajlari` ( /* ... */ );

-- Olay günlüğü, merkezi yapıda olduğu için burada kalır.
CREATE TABLE `olay_gunlugu` (
  `olay_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `olay_tipi` VARCHAR(100) NOT NULL,
  `veri` JSON NOT NULL,
  `islendi` BOOLEAN NOT NULL DEFAULT FALSE,
  `islendi_raporlama` BOOLEAN NOT NULL DEFAULT FALSE,
  `islendi_kupon` BOOLEAN NOT NULL DEFAULT FALSE,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
