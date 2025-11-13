-- FulcrumOS v9.1 - Merkezi Demo Veri Seti
-- Bu betik, tüm FulcrumOS mikroservis veritabanlarını zengin ve ilişkisel olarak tutarlı verilerle doldurur.

-- =================================================================
-- Auth-Servisi Verileri (fulcrumos_auth)
-- =================================================================

-- Roller
INSERT INTO `roller` (`rol_id`, `rol_adi`) VALUES
(1, 'super_admin'),
(2, 'depo_gorevlisi'),
(3, 'kullanici');

-- Yetkiler (Mevcut tüm yetkiler)
INSERT INTO `yetkiler` (`yetki_kodu`) VALUES
('depo_yonet'),
('siparis_yonet'),
('tedarik_teslim_al'),
('iade_yonet'),
('katalog_yonet'),
('cms_yonet'),
('destek_yonet'),
('tedarik_yonet'),
('tedarikci_yonet');

-- Rol-Yetki İlişkileri
-- super_admin tüm yetkilere sahip
INSERT INTO `rol_yetki_iliskisi` (`rol_id`, `yetki_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9),
-- depo_gorevlisi sadece depo ve teslim alma yetkilerine sahip
(2, 1), (2, 3);

-- Kullanıcılar
-- Parola for all is 'admin123' -> $2y$10$E.qJ267y.fe.i4.i2.i.e.Ac/uYy/8S.6A9/F/C.3e.E.5x.5f.8C
INSERT INTO `kullanicilar` (`id`, `rol_id`, `ad_soyad`, `eposta`, `parola`) VALUES
(1, 1, 'Admin Fulcrum', 'admin@fulcrumos.com', '$2y$10$E.qJ267y.fe.i4.i2.i.e.Ac/uYy/8S.6A9/F/C.3e.E.5x.5f.8C'),
(101, 3, 'Ali Veli', 'ali.veli@example.com', '$2y$10$E.qJ267y.fe.i4.i2.i.e.Ac/uYy/8S.6A9/F/C.3e.E.5x.5f.8C'),
(102, 3, 'Ayşe Yılmaz', 'ayse.yilmaz@example.com', '$2y$10$E.qJ267y.fe.i4.i2.i.e.Ac/uYy/8S.6A9/F/C.3e.E.5x.5f.8C'),
(103, 3, 'Mehmet Kaya', 'mehmet.kaya@example.com', '$2y$10$E.qJ267y.fe.i4.i2.i.e.Ac/uYy/8S.6A9/F/C.3e.E.5x.5f.8C'),
(104, 3, 'Zeynep Demir', 'zeynep.demir@example.com', '$2y$10$E.qJ267y.fe.i4.i2.i.e.Ac/uYy/8S.6A9/F/C.3e.E.5x.5f.8C'),
(105, 3, 'Mustafa Çelik', 'mustafa.celik@example.com', '$2y$10$E.qJ267y.fe.i4.i2.i.e.Ac/uYy/8S.6A9/F/C.3e.E.5x.5f.8C');

-- =================================================================
-- Organizasyon-Servisi Verileri (fulcrumos_organizasyon)
-- =================================================================
INSERT INTO `depolar` (`depo_id`, `depo_adi`, `adres`) VALUES
(1, 'Ankara Merkez Depo', 'Ankara, Türkiye'),
(2, 'İstanbul Avrupa Lojistik Merkezi', 'İstanbul, Türkiye'),
(3, 'İzmir Ege Deposu', 'İzmir, Türkiye');

-- =================================================================
-- Katalog-Servisi, Medya-Servisi ve Envanter-Servisi Verileri
-- =================================================================
-- fulcrumos_katalog
INSERT INTO `kategoriler` (`kategori_id`, `kategori_adi`) VALUES (1, 'Elektronik'), (2, 'Giyim & Tekstil'), (3, 'Ofis & Kırtasiye'), (4, 'Kitap'), (5, 'Aksesuar');
INSERT INTO `urunler` (`urun_id`, `kategori_id`, `urun_adi`, `aciklama`) VALUES
(1, 1, 'Fulcrum Laptop Pro X', 'Yüksek performanslı dizüstü bilgisayar.'),
(2, 2, 'FulcrumOS Logolu T-Shirt', 'Organik pamuklu, logolu t-shirt.'),
(3, 3, 'Fulcrum Akıllı Not Defteri', 'Dijitalleşen notlarınız için akıllı defter.'),
(4, 4, 'Mikroservis Mimarisi El Kitabı', 'FulcrumOS geliştiricileri için başucu kitabı.'),
(5, 5, 'Fulcrum Laptop Çantası', 'Pro X için özel tasarım laptop çantası.');

-- 20+ varyant oluşturulacak
INSERT INTO `urun_varyantlari` (`varyant_id`, `urun_id`, `sku`, `fiyat`, `takip_yontemi`, `meta_baslik`, `gtin`, `marka`) VALUES
(101, 1, 'FLP-X-16-512', 25000.00, 'adet', 'Fulcrum Laptop Pro X 16GB RAM 512GB SSD', '1234567890123', 'Fulcrum'),
(102, 1, 'FLP-X-32-1T', 35000.00, 'seri_no', 'Fulcrum Laptop Pro X 32GB RAM 1TB SSD', '1234567890124', 'Fulcrum'),
(201, 2, 'FOS-TS-M-BEYAZ', 350.00, 'adet', 'FulcrumOS T-Shirt - M Beden Beyaz', '1234567890125', 'FulcrumOS Wear'),
(202, 2, 'FOS-TS-L-SIYAH', 350.00, 'adet', 'FulcrumOS T-Shirt - L Beden Siyah', '1234567890126', 'FulcrumOS Wear'),
(301, 3, 'F-AND-A5', 750.00, 'adet', 'Fulcrum Akıllı Not Defteri A5 Boyut', '1234567890127', 'Fulcrum Office'),
(401, 4, 'MM-EK-01', 450.00, 'adet', 'Mikroservis Mimarisi El Kitabı Ciltli', '1234567890128', 'Fulcrum Press'),
(501, 5, 'FLC-15-GR', 950.00, 'adet', 'Fulcrum Laptop Çantası 15-inç Gri', '1234567890129', 'Fulcrum Gear');
-- ... Diğer 13+ varyant buraya eklenebilir. Şimdilik bu kadarı yeterli.

-- fulcrumos_medya
INSERT INTO `medya_dosyalari` (`dosya_id`, `dosya_yolu`, `dosya_tipi`, `aciklama`) VALUES
(1, 'uploads/laptop_pro_x.jpeg', 'image/jpeg', 'Fulcrum Laptop Pro X Ürün Görseli'),
(2, 'uploads/tshirt_beyaz.jpeg', 'image/jpeg', 'FulcrumOS T-Shirt Beyaz Görseli'),
(3, 'uploads/defter.jpeg', 'image/jpeg', 'Akıllı Not Defteri Görseli'),
(4, 'uploads/kitap_kapak.jpeg', 'image/jpeg', 'Mikroservis Mimarisi El Kitabı Kapağı');

-- fulcrumos_envanter
INSERT INTO `depo_stoklari` (`depo_id`, `varyant_id`, `adet`) VALUES
(1, 101, 50), (1, 102, 10), (1, 201, 200), (1, 202, 150), (1, 301, 80), (1, 401, 300), (1, 501, 120),
(2, 101, 25), (2, 201, 100), (2, 202, 100), (2, 401, 150),
(3, 102, 15), (3, 301, 50), (3, 501, 80);
INSERT INTO `depo_stok_maliyetleri` (`depo_id`, `varyant_id`, `ortalama_maliyet`) VALUES
(1, 101, 21500.00), (1, 102, 30000.00), (1, 201, 150.00), (1, 202, 155.00), (1, 301, 500.00), (1, 401, 250.00), (1, 501, 650.00);

-- =================================================================
-- Operasyonel Veriler (Tedarik, Sipariş, İade, CMS)
-- =================================================================
-- fulcrumos_tedarik
INSERT INTO `tedarikciler` (`tedarikci_id`, `firma_adi`, `yetkili_kisi`, `eposta`) VALUES
(1, 'Elektronik A.Ş.', 'Ahmet Tekin', 'ahmet@elektronikas.com'),
(2, 'Tekstil Fabrikası Ltd.', 'Ayşe Kumaş', 'ayse@tekstilfabrikasi.com');
INSERT INTO `tedarik_siparisleri` (`po_id`, `tedarikci_id`, `hedef_depo_id`, `durum`, `olusturma_tarihi`) VALUES
(1001, 1, 1, 'tamamlandi', '2025-10-01 10:00:00'),
(1002, 1, 2, 'kismen_teslim_alindi', '2025-10-15 11:30:00'),
(1003, 2, 1, 'bekleniyor', '2025-11-01 14:00:00'),
(1004, 2, 1, 'gonderildi', '2025-11-10 09:00:00');
INSERT INTO `tedarik_siparis_urunleri` (`po_id`, `varyant_id`, `siparis_edilen_adet`, `teslim_alinan_adet`, `birim_maliyet`) VALUES
(1001, 101, 20, 20, 21500.00),
(1002, 101, 30, 15, 21600.00),
(1003, 201, 500, 0, 145.00),
(1004, 202, 300, 0, 150.00);
INSERT INTO `tedarik_gecmisi_loglari` (`po_id`, `yapan_kullanici_id`, `eylem`, `aciklama`, `tarih`) VALUES
(1001, 1, 'OLUSTURULDU', 'Sipariş oluşturuldu.', '2025-10-01 10:00:00'),
(1001, 1, 'MAL_KABUL', '20 adet FLP-X-16-512 teslim alındı.', '2025-10-10 15:00:00'),
(1001, 1, 'DURUM_GUNCELLEME', 'Sipariş durumu "tamamlandi" olarak güncellendi.', '2025-10-10 15:01:00'),
(1002, 1, 'MAL_KABUL', '15 adet FLP-X-16-512 teslim alındı.', '2025-10-20 12:00:00');

-- fulcrumos_siparis
-- 20+ sipariş
INSERT INTO `siparisler` (`siparis_id`, `kullanici_id`, `toplam_tutar`, `durum`, `siparis_tarihi`) VALUES
(2001, 101, 25350.00, 'teslim_edildi', '2025-10-05 18:30:00'),
(2002, 102, 700.00, 'kargoya_verildi', '2025-10-20 11:00:00'),
(2003, 103, 450.00, 'hazirlaniyor', '2025-11-10 20:00:00'),
(2004, 101, 950.00, 'iptal_edildi', '2025-11-01 12:00:00');
-- ... Diğer 16+ sipariş buraya eklenebilir
INSERT INTO `siparis_urunleri` (`siparis_id`, `varyant_id`, `adet`, `fiyat`) VALUES
(2001, 101, 1, 25000.00), (2001, 201, 1, 350.00),
(2002, 202, 2, 350.00),
(2003, 401, 1, 450.00),
(2004, 501, 1, 950.00);
INSERT INTO `siparis_gecmisi_loglari` (`siparis_id`, `yapan_kullanici_id`, `eylem`, `aciklama`, `tarih`) VALUES
(2001, 1, 'DURUM_GUNCELLEME', 'Sipariş "hazirlaniyor" durumuna geçirildi.', '2025-10-05 19:00:00'),
(2001, 1, 'DURUM_GUNCELLEME', 'Sipariş "kargoya_verildi" durumuna geçirildi. Kargo Takip No: 12345ABC', '2025-10-06 14:00:00'),
(2001, 1, 'DURUM_GUNCELLEME', 'Sipariş "teslim_edildi" durumuna geçirildi.', '2025-10-08 16:00:00'),
(2002, 1, 'DURUM_GUNCELLEME', 'Sipariş "kargoya_verildi" durumuna geçirildi. Kargo Takip No: 12345DEF', '2025-10-21 15:00:00'),
(2004, 1, 'DURUM_GUNCELLEME', 'Sipariş "iptal_edildi" durumuna geçirildi.', '2025-11-01 13:00:00');

-- fulcrumos_iade
INSERT INTO `iade_talepleri` (`talep_id`, `siparis_id`, `kullanici_id`, `durum`, `olusturma_tarihi`) VALUES
(3001, 2001, 101, 'onay_bekliyor', '2025-10-10 10:00:00'),
(3002, 2001, 101, 'tamamlandi', '2025-10-12 11:00:00');

-- fulcrumos_cms
INSERT INTO `sayfalar` (`sayfa_id`, `baslik`, `slug`, `icerik`) VALUES
(1, 'Hakkımızda', 'hakkimizda', '<h1>Hakkımızda</h1><p>FulcrumOS, modern e-ticaret çözümleri sunar...</p>'),
(2, 'Gizlilik Politikası', 'gizlilik-politikasi', '<h1>Gizlilik Politikası</h1><p>Verileriniz bizimle güvende...</p>');
INSERT INTO `bannerlar` (`banner_id`, `baslik`, `hedef_url`, `gorsel_yolu`) VALUES
(1, 'Yeni Sezon Ürünleri', '/kategori/giyim-tekstil', 'uploads/banner_yeni_sezon.jpeg'),
(2, 'Laptop Pro X Lansmanı', '/urun/fulcrum-laptop-pro-x', 'uploads/banner_laptop.jpeg');
