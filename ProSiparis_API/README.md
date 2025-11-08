# ProSiparis API v2.7 - B2B & Fulfillment Yetenekli E-Ticaret API'si

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş, hem B2C hem de B2B senaryolarını destekleyen, operasyonel verimliliği artırılmış, modern ve proaktif bir e-ticaret backend (API) sunucusudur.

**v2.7 Yenilikleri:** Bu sürüm, API'ye iki temel kurumsal yetenek kazandırır: farklı müşteri gruplarına farklı fiyatlar sunabilen dinamik bir B2B fiyatlandırma altyapısı ve sipariş hazırlama süreçlerini yöneten özel bir Depo Operasyonları (Fulfillment) API'si.

---

## Kurulum

1.  **Gerekli Araçlar:** PHP >= 7.4, Composer, MySQL, Apache (veya `.htaccess` destekli başka bir web sunucusu).
2.  **Projeyi Kurma:** Projenin ana dizininde `composer install` komutunu çalıştırın.
3.  **Veritabanı Oluşturma:** `schema.sql` dosyasını (**v2.7**) veritabanınıza içe aktarın. Bu şema, yeni B2B fiyatlandırma ve depo operasyonları için gerekli tüm tabloları ve ACL verilerini içerir.
4.  **Yapılandırma:** `config/ayarlar.php` dosyasında `CRON_SECRET_KEY` dahil tüm gerekli ayarları yapın.
5.  **Sunucu Ayarları:** `public/` klasörünü "Document Root" olarak ayarlayın ve `/api/cron/run` endpoint'ini tetikleyecek bir cron job kurun.

---

## Temel Konseptler (v2.7 Güncellemeleri)

### Dinamik B2B Fiyatlandırma (YENİ)
Sistem artık `fiyat_listeleri` ve `varyant_fiyatlari` tabloları sayesinde her bir ürün varyantına birden fazla fiyat atayabilmektedir.
-   Her `rol` (örn: "kullanici", "bayi"), bir `fiyat_listesi_id`'ye bağlanmıştır.
-   Kullanıcı giriş yaptığında, bu `fiyat_listesi_id`, JWT'nin içine gömülür.
-   Tüm ürün ve ödeme servisleri, fiyatları bu ID'ye göre dinamik ve güvenli bir şekilde sunar. Böylece bir "bayi", "kullanici" ile aynı ürüne bakarken otomatik olarak kendi özel fiyatını görür.

### Depo Operasyonları (Fulfillment) API'si (YENİ)
Sipariş hazırlama süreci, "admin" yetkilerinden tamamen ayrıştırılmıştır. Yeni `depo_gorevlisi` rolü, sadece sipariş toplama, paketleme ve kargolama işlemlerini yapabileceği kısıtlı bir API setine erişebilir.
-   **Yetkiler:** `siparis_toplama_listesi_gor` ve `siparis_kargola`.
-   **Görev Ayrımı:** "Kargoya Verildi" durumu artık sadece Depo API'si üzerinden atanabilir. Adminler, sadece "Teslim Edildi" veya "İptal Edildi" gibi nihai durumları yönetir.

---

## API Endpoint'leri (v2.7)

### Herkese Açık ve Kullanıcı Endpoint'leri (GÜNCELLENMİŞ)

-   **`GET /api/urunler` & `GET /api/urunler/{id}` (Davranış Güncellendi)**
    -   Bu endpoint'ler artık, giriş yapmış olan kullanıcının rolüne (B2C veya B2B) göre dinamik olarak doğru fiyatları gösterir. Giriş yapılmamışsa, varsayılan perakende fiyatları kullanılır.

### Depo Operasyonları API'si (YENİ)
_Bu endpoint'ler `AuthMiddleware` ve ilgili yetkilerle korunmaktadır._

-   **`GET /api/depo/hazirlanacak-siparisler` (Yetki: `siparis_toplama_listesi_gor`)**
    -   Depo görevlisinin hazırlaması gereken, durumu "Ödendi" veya "Hazırlanıyor" olan siparişleri listeler.
-   **`GET /api/depo/siparis/{id}/toplama-listesi` (Yetki: `siparis_toplama_listesi_gor`)**
    -   Bir siparişin, ürünlerin depo içindeki yerini (`raf_kodu`) de içeren ürün toplama listesini döndürür.
-   **`POST /api/depo/siparis/{id}/kargoya-ver` (Yetki: `siparis_kargola`)**
    -   Bir siparişi "Kargoya Verildi" olarak işaretler, kargo bilgilerini kaydeder ve müşteriye otomatik bildirim e-postası gönderir.
    -   **Body:** `{ "kargo_firmasi": "...", "kargo_takip_kodu": "..." }`

### Admin Korumalı Endpoint'ler (GÜNCELLENMİŞ)

-   **`PUT /api/admin/siparisler/{id}` (Yetki Daraltıldı - `siparis_yonet`)**
    -   Bu endpoint artık sadece bir siparişin nihai durumlarını (`Teslim Edildi`, `Iptal Edildi`) güncellemek için kullanılır. Kargo bilgileri girişi ve "Kargoya Verildi" olarak işaretleme işlemi Depo API'sine taşınmıştır.
    -   **Body:** `{ "durum": "Teslim Edildi" }`

_(Diğer tüm endpoint'ler v2.6 ile aynıdır.)_
