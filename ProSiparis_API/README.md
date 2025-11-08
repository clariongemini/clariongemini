# ProSiparis API v2.8 - Tam Kapsamlı E-Ticaret Operasyonları API'si

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş, B2C/B2B satıştan tedarik zinciri ve iade yönetimine kadar tüm e-ticaret operasyonlarını yöneten, modern ve proaktif bir backend (API) sunucusudur.

**v2.8 Yenilikleri:** Bu sürüm, envanterin kaynağını (Tedarik Zinciri) ve müşteriden geri dönüşünü (İade Yönetimi - RMA) yöneten iki kritik kurumsal altyapıyı ekleyerek API'yi tam bir operasyonel döngüye kavuşturur.

---

## Kurulum
1.  **Veritabanı Oluşturma:** `schema.sql` dosyasını (**v2.8**) veritabanınıza içe aktarın. Bu şema, yeni tedarik zinciri ve RMA tablolarını içerir.
2.  **Yapılandırma & Kurulum:** `composer install` komutunu çalıştırın ve `config/ayarlar.php` dosyasını düzenleyin.
3.  **Cron Job:** `/api/cron/run` endpoint'ini tetikleyecek bir zamanlanmış görev kurun.

---

## Temel Konseptler (v2.8 Güncellemeleri)

### Tedarik Zinciri ve Stok Yönetimi (YENİ)
Sistem artık envanterin stoğa nasıl girdiğini profesyonel bir şekilde yönetir:
1.  **Tedarikçiler:** Ürünlerin satın alındığı firmalar sisteme kaydedilir.
2.  **Satın Alma Siparişleri (PO):** Bir tedarikçiden hangi ürünün, ne kadar ve ne maliyetle alınacağını belirten siparişler oluşturulur.
3.  **Mal Kabul:** `depo_gorevlisi` rolü, tedarikçiden gelen fiziksel ürünleri Depo API'sini kullanarak teslim alır. Bu işlem, `stok_adedi`'ni otomatik olarak artırır ve PO'nun durumunu günceller.

### İade Yönetimi Döngüsü (RMA - YENİ)
Müşteri iadeleri artık tam bir iş akışıyla yönetilmektedir:
1.  **Talep Oluşturma:** Müşteri, "Teslim Edildi" statüsündeki bir siparişi için API üzerinden iade talebi oluşturur.
2.  **Admin Onayı:** `iade_yonet` yetkisine sahip admin, talebi inceler ve onaylar veya reddeder.
3.  **Depo Kabulü:** Onaylanan iade, müşteri tarafından depoya gönderilir. `depo_gorevlisi`, `iade_teslim_al` yetkisiyle paketi teslim alır, ürünün durumunu ("Satılabilir" veya "Kusurlu") belirler. "Satılabilir" ürünler otomatik olarak stoğa geri eklenir.
4.  **Para İadesi:** Depodan "Depoya Ulaştı" onayı gelen iadeler için admin, para iadesi işlemini tetikler ve süreç tamamlanır.

---

## API Endpoint'leri (v2.8)

### Tedarik Zinciri ve Stok Girişi API'si (YENİ)

#### **Admin Endpoint'leri (Yetki Korumalı)**
-   **Tedarikçi Yönetimi (Yetki: `tedarikci_yonet`)**
    -   `GET, POST /api/admin/tedarikciler`
    -   `PUT, DELETE /api/admin/tedarikciler/{id}`
-   **Satın Alma Siparişi Yönetimi (Yetki: `satin_alma_yonet`)**
    -   `GET, POST /api/admin/satin-alma-siparisleri`
    -   `PUT /api/admin/satin-alma-siparisleri/{id}`

#### **Depo Endpoint'leri (Yetki Korumalı)**
-   **`GET /api/depo/beklenen-teslimatlar` (Yetki: `satin_alma_teslim_al`)**: Tedarikçilerden gelmesi beklenen PO'ları listeler.
-   **`POST /api/depo/teslimat-al/{po_id}` (Yetki: `satin_alma_teslim_al`)**: Bir PO'ya ait ürünlerin mal kabulünü yapar ve stokları artırır.
    -   **Body:** `{ "urunler": [{"varyant_id": 12, "gelen_adet": 50}] }`

### İade Yönetimi (RMA) API'si (YENİ)

#### **Kullanıcı Endpoint'leri (Kullanıcı Korumalı)**
-   **`POST /api/kullanici/iade-talebi-olustur`**: Bir sipariş için iade talebi başlatır.
    -   **Body:** `{ "siparis_id": 105, "sebep": "...", "urunler": [{"varyant_id": 12, "adet": 1}] }`
-   **`GET /api/kullanici/iade-talepleri`**: Kullanıcının tüm iade taleplerini ve durumlarını listeler.

#### **Admin Endpoint'leri (Yetki: `iade_yonet`)**
-   **`GET /api/admin/iade-talepleri`**: Tüm iade taleplerini listeler (filtrelenebilir).
-   **`PUT /api/admin/iade-talepleri/{id}/durum-guncelle`**: Bir iade talebini "Onaylandı" veya "Reddedildi" olarak günceller.
    -   **Body:** `{ "durum": "Onaylandı" }`
-   **`POST /api/admin/iade-talepleri/{id}/odeme-yap`**: Depodan onayı gelmiş bir iade için müşteriye para iadesi işlemini tetikler.

#### **Depo Endpoint'leri (Yetki: `iade_teslim_al`)**
-   **`POST /api/depo/iade-teslim-al/{iade_id}`**: Müşteriden gelen iade paketini teslim alır, ürün durumunu belirler ve satılabilir ürünleri stoğa geri ekler.
    -   **Body:** `{ "urunler": [{"varyant_id": 12, "adet": 1, "durum": "Satılabilir"}] }`

_(Diğer tüm endpoint'ler v2.7 ile aynıdır.)_
