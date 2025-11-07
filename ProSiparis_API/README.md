# ProSiparis API v2.3 - Profesyonel E-Ticaret API

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş, modern PHP standartlarına uygun, güvenli ve tam teşekküllü bir e-ticaret backend (API) sunucusudur.

---

## Kurulum

1.  **Gerekli Araçlar:** PHP >= 7.4, Composer, MySQL, Apache (veya `.htaccess` destekli başka bir web sunucusu).
2.  **Projeyi Kurma:** Projenin ana dizininde `composer install` komutunu çalıştırın.
3.  **Veritabanı Oluşturma:** `schema.sql` dosyasını (v2.3) veritabanınıza içe aktarın.
4.  **Yapılandırma:**
    *   `config/ayarlar.php` dosyasında veritabanı, JWT ve SMTP bilgilerinizi düzenleyin.
    *   `config/iyzico_ayarlar.php` dosyasında Iyzico API anahtarlarınızı girin.
5.  **Web Sunucusu:** Sunucunuzun "Document Root" olarak projenin `public/` klasörünü göstermesi tavsiye edilir.

---

## Versiyon Geçmişi

### v2.3: Lojistik, Bildirimler ve Promosyon Motoru

Bu sürüm, sipariş sonrası süreçleri yönetir, müşteriyle iletişimi otomatikleştirir ve pazarlama yetenekleri ekler.

-   **Promosyon ve Kupon Motoru:** Yüzdesel veya sabit tutarlı indirimler sağlayan, son kullanma tarihi, minimum sepet tutarı gibi kurallara sahip kupon sistemi eklendi. `POST /api/sepet/kupon-dogrula` ile kuponlar ödeme öncesi doğrulanabilir.
-   **Otomatik E-posta Bildirimleri:** `symfony/mailer` kullanılarak, "Sipariş Onayı" ve "Kargoya Verildi" durumlarında müşterilere otomatik bilgilendirme e-postaları gönderen altyapı kuruldu.
-   **Sipariş Lojistiği ve Takibi:** Adminler artık siparişleri "Kargoya Verildi" olarak işaretleyip, kargo firması ve takip kodu ekleyebilir. Bu işlem, müşteriye otomatik bir bildirim e-postası tetikler.
-   **Sipariş Detay Görüntüleme:** Kullanıcılar artık `GET /api/siparisler/{id}` endpoint'i ile siparişlerinin tüm detaylarını (ürünler, kargo durumu, ödeme özeti vb.) görüntüleyebilir.

### v2.2: Tam Kapsamlı Ödeme (Checkout) ve Lojistik

-   **Kullanıcı Adres Yönetimi (CRUD), Kargo Seçenekleri API'si.**
-   **Iyzico Entegrasyonu:** Güvenli ödeme akışı ve webhook ile sipariş oluşturma.

### v2.1: Gelişmiş Katalog ve Envanter Yönetimi

-   **Ürün Varyantları, Kategoriler ve Gerçek Zamanlı Stok Kontrolü.**

### v2.0: Mimari Modernizasyon

-   **Front Controller Mimarisi, Composer, JWT/RBAC Güvenliği.**

---

## API Endpoint'leri (v2.3)

_v2.2'ye eklenenler ve güncellenenler aşağıdadır._

### Kullanıcı Korumalı Endpoint'ler (`Authorization: Bearer <token>` Gerekli)

-   **`POST /api/sepet/kupon-dogrula` (YENİ)**: Bir kupon kodunun geçerliliğini ve uygulanacak indirimi kontrol eder.
    -   **Body:** `{ "kupon_kodu": "YAZ25", "mevcut_sepet_tutari": 450.50 }`
-   **`POST /api/odeme/baslat` (GÜNCELLENDİ)**: Ödeme sürecini başlatır. Artık opsiyonel bir `kupon_kodu` alanı kabul eder.
    -   **Body:** `{ "kupon_kodu": "YAZ25", ... }`
-   **`GET /api/siparisler/{id}` (YENİ)**: Tek bir siparişin tüm detaylarını getirir.
    -   **Örnek Yanıt:**
        ```json
        {
          "siparis_id": 102,
          "durum": "Kargoya Verildi",
          "kargo_bilgisi": { "firma": "Yurtiçi Kargo", "takip_kodu": "123XYZ" },
          "urunler": [ ... ],
          "odeme_ozeti": { ... }
        }
        ```

### Admin Korumalı Endpoint'ler (Admin Rolü ve Token Gerekli)

-   **`PUT /api/admin/siparisler/{id}` (GÜNCELLENDİ)**: Bir siparişin durumunu ve kargo bilgilerini günceller.
    -   **Body:**
        ```json
        {
          "durum": "Kargoya Verildi",
          "kargo_firmasi": "Yurtiçi Kargo",
          "kargo_takip_kodu": "1234567890XYZ"
        }
        ```
