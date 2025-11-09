-- Ana Monolith (Legacy Core) Veritabanı Şeması v3.1
-- Siparişle ilgili tablolar bu şemadan çıkarılmıştır.

CREATE TABLE `iade_talepleri` ( /* ... */ );
CREATE TABLE `iade_urunleri` ( /* ... */ );
CREATE TABLE `tedarikciler` ( /* ... */ );
CREATE TABLE `satin_alma_siparisleri` ( /* ... */ );
CREATE TABLE `satin_alma_siparis_urunleri` ( /* ... */ );
CREATE TABLE `kuponlar` ( /* ... */ );
CREATE TABLE `sayfalar` ( /* ... */ );
CREATE TABLE `bannerlar` ( /* ... */ );
CREATE TABLE `destek_talepleri` ( /* ... */ );
CREATE TABLE `destek_mesajlari` ( /* ... */ );

-- Olay günlüğü, şimdilik merkezi olduğu için burada kalabilir.
CREATE TABLE `olay_gunlugu` (
  `olay_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `olay_tipi` VARCHAR(100) NOT NULL,
  `veri` JSON NOT NULL,
  `islendi` BOOLEAN NOT NULL DEFAULT FALSE,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
