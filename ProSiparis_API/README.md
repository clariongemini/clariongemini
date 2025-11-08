# ProSiparis API v2.6 - Akıllı E-Ticaret API Motoru

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş, modern PHP standartlarına uygun, proaktif ve tam teşekküllü bir e-ticaret backend (API) sunucusudur.

**v2.6 Yenilikleri:** Bu sürüm, API'yi reaktif bir veri sağlayıcıdan proaktif bir iş ortağına dönüştürür. Pazarlama otomasyonu, headless CMS ve entegre müşteri desteği gibi özelliklerle donatılmıştır.

---

## Kurulum

1.  **Gerekli Araçlar:** PHP >= 7.4, Composer, MySQL, Apache (veya `.htaccess` destekli başka bir web sunucusu).
2.  **Projeyi Kurma:** Projenin ana dizininde `composer install` komutunu çalıştırın.
3.  **Veritabanı Oluşturma:** `schema.sql` dosyasını (**v2.6**) veritabanınıza içe aktarın. Bu şema, yeni destek, cms ve kalıcı sepet tablolarını içerir.
4.  **Yapılandırma:** `config/ayarlar.php` dosyasında veritabanı, JWT, SMTP gibi ayarların yanı sıra, otomasyon motorunu tetiklemek için kullanılacak olan **`CRON_SECRET_KEY`** sabitini de güvenli bir değerle güncelleyin.
5.  **Sunucu Ayarları:**
    *   Web sunucunuzun "Document Root" olarak projenin `public/` klasörünü göstermesi tavsiye edilir.
    *   Otomasyon motorunun çalışması için, sunucunuza saat başı `/api/cron/run` endpoint'ine `Authorization: Bearer <CRON_SECRET_KEY>` başlığı ile `POST` isteği atacak bir **cron job** (zamanlanmış görev) kurun.

---

## Temel Konseptler

### Yetki Bazlı Erişim Kontrolü (ACL)
(v2.5'ten devam ediyor) Sistem, granüler **yetki bazlı** bir güvenlik modeline sahiptir. Admin endpoint'leri, belirli eylemleri gerçekleştirme hakkı tanıyan yetkilerle (`urun_yonet`, `destek_yonet` vb.) korunur.

### Proaktif Otomasyon Motoru (YENİ)
v2.6 ile API, zamanlanmış görevlerle (cron job) tetiklenen bir otomasyon motoruna sahiptir. Bu motor, `AutomationService` aracılığıyla şu anda iki senaryoyu yönetir:
1.  **Terk Edilmiş Sepet:** 24 saattir bekleyen ve siparişe dönüşmemiş sepetleri tespit edip kullanıcılara hatırlatma e-postası gönderir.
2.  **Pasif Kullanıcı:** 60 gündür sipariş vermemiş kullanıcılara, onları geri kazanmak için kişiye özel indirim kuponları oluşturur ve e-posta ile bilgilendirir.

---

## API Endpoint'leri (v2.6)

### Herkese Açık Endpoint'ler (YENİ)
-   **`GET /api/sayfa/{slug}`**: "hakkimizda", "kvkk" gibi statik sayfaların içeriğini döndürür.
-   **`GET /api/bannerlar?konum=anasayfa_ust`**: Mobil uygulamanın ana sayfasında gösterilecek banner'ları listeler.

### Kullanıcı Korumalı Endpoint'ler (YENİ)
-   **`GET /api/sepet`**: Kullanıcının sunucuda saklanan sepetini ve içindeki ürünleri döndürür. Cihazlar arası senkronizasyon sağlar.
-   **`POST /api/sepet/guncelle`**: Kullanıcının sepetini günceller. Mobil uygulama, sepete her ürün eklendiğinde/çıkarıldığında bu endpoint'i çağırmalıdır.
    -   **Body:** `{ "urunler": { "varyant_id_1": adet, "varyant_id_2": adet } }`
-   **`GET /api/kullanici/destek-talepleri`**: Kullanıcının tüm destek taleplerini listeler.
-   **`POST /api/kullanici/destek-talepleri`**: Yeni bir destek talebi oluşturur.
-   **`GET /api/kullanici/destek-talepleri/{id}`**: Bir talebin tüm mesajlaşma geçmişini getirir.
-   **`POST /api/kullanici/destek-talepleri/{id}/mesaj`**: Mevcut bir talebe yeni bir mesaj ekler.

### Admin Korumalı Endpoint'ler (YENİ)
-   **Headless CMS Yönetimi (Yetki: `cms_yonet`)**
    -   `GET, POST /api/admin/sayfalar`
    -   `PUT, DELETE /api/admin/sayfalar/{id}`
    -   `GET, POST /api/admin/bannerlar`
    -   `PUT, DELETE /api/admin/bannerlar/{id}`
-   **Müşteri Destek Yönetimi (Yetki: `destek_yonet`)**
    -   `GET /api/admin/destek-talepleri?durum=acik`: Talepleri duruma göre filtreleyerek listeler.
    -   `POST /api/admin/destek-talepleri/{id}/mesaj`: Bir talebe yönetici olarak yanıt verir.

### Cron Job Endpoint'i (YENİ - Özel Güvenlikli)
-   **`POST /api/cron/run`**: `AutomationService` içindeki tüm görevleri (terk edilmiş sepet, pasif kullanıcı vb.) tetikler.
    -   **Güvenlik:** Bu endpoint, `Authorization: Bearer <CRON_SECRET_KEY>` başlığı ile gönderilen isteklere yanıt verir. Anahtar, `config/ayarlar.php` dosyasında tanımlanmalıdır.
