# ProSiparis API v2.2 - Profesyonel E-Ticaret API

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş, modern PHP standartlarına uygun, güvenli ve tam teşekküllü bir e-ticaret backend (API) sunucusudur.

---

## Kurulum

1.  **Gerekli Araçlar:** PHP >= 7.4, Composer, MySQL, Apache (veya `.htaccess` destekli bir web sunucusu).
2.  **Projeyi Kurma:** Projenin ana dizininde `composer install` komutunu çalıştırın.
3.  **Veritabanı Oluşturma:** `schema.sql` dosyasını (v2.2) veritabanınıza içe aktarın.
4.  **Yapılandırma:**
    *   `config/ayarlar.php` dosyasında veritabanı ve JWT bilgilerinizi düzenleyin.
    *   `config/iyzico_ayarlar.php` dosyasında Iyzico API anahtarlarınızı girin.
5.  **Web Sunucusu:** Sunucunuzun "Document Root" olarak projenin `public/` klasörünü göstermesi tavsiye edilir.

---

## Versiyon Geçmişi

### v2.2: Tam Kapsamlı Ödeme (Checkout) ve Lojistik

Bu sürüm, API'yi tam bir ödeme akışına sahip, profesyonel bir e-ticaret platformuna dönüştürür.

-   **Kullanıcı Adres Yönetimi:** Kullanıcılar artık kendilerine ait teslimat adreslerini tam CRUD (Oluştur, Oku, Güncelle, Sil) işlemleriyle yönetebilir.
-   **Kargo Seçenekleri:** Ödeme sırasında kullanıcıya sunulmak üzere dinamik kargo firmaları ve ücretleri altyapısı eklendi.
-   **Iyzico Ödeme Entegrasyonu:** Sistem artık harici bir ödeme ağ geçidi (Iyzico) ile entegredir. Ödeme süreci, `POST /api/odeme/baslat` ile başlatılır ve mobil uygulamanın bir WebView içinde açacağı güvenli bir ödeme formu döndürür.
-   **Webhook ile Sipariş Oluşturma:** Siparişler artık doğrudan oluşturulmaz. Sadece Iyzico'dan gelen başarılı bir ödeme onayı (callback/webhook) sonrası, `SiparisService` tetiklenerek sipariş veritabanına kaydedilir ve stoktan düşülür. Bu, ödeme yapılmadan sipariş oluşturulmasını engeller.

### v2.1: Gelişmiş Katalog ve Envanter Yönetimi

-   **Ürün Varyantları:** Ürünler artık "Beden", "Renk" gibi niteliklere ve her birinin kendi SKU'su, fiyatı ve stoğu olan varyantlara sahiptir.
-   **Gerçek Zamanlı Stok Kontrolü:** Sipariş anında stok yeterliliği kontrol edilir.
-   **Kategori Yönetimi ve Resim Yükleme.**

### v2.0: Mimari Modernizasyon

-   **Front Controller Mimarisi, Composer, JWT/RBAC Güvenliği.**

---

## API Endpoint'leri (v2.2)

_v2.1'e eklenenler ve güncellenenler aşağıdadır._

### Kullanıcı Korumalı Endpoint'ler (`Authorization: Bearer <token>` Gerekli)

-   **`GET /api/kullanici/adresler` (YENİ)**: Mevcut kullanıcının tüm adreslerini listeler.
-   **`POST /api/kullanici/adresler` (YENİ)**: Yeni bir adres ekler.
-   **`PUT /api/kullanici/adresler/{id}` (YENİ)**: Bir adresi günceller.
-   **`DELETE /api/kullanici/adresler/{id}` (YENİ)**: Bir adresi siler.
-   **`POST /api/odeme/baslat` (YENİ)**: Ödeme sürecini başlatır ve Iyzico ödeme formunu döndürür.
    -   **Body:**
        ```json
        {
          "teslimat_adresi_id": 1,
          "fatura_adresi_id": 1,
          "kargo_id": 1,
          "sepet": [
            { "varyant_id": 12, "adet": 1 }
          ]
        }
        ```
-   **`POST /api/siparisler` (KALDIRILDI)**: Siparişler artık sadece ödeme onayı ile oluşturulmaktadır.

### Herkese Açık Endpoint'ler

-   **`GET /api/kargo-secenekleri` (YENİ)**: Tüm kargo seçeneklerini ve ücretlerini listeler.
-   **`POST /api/odeme/callback/iyzico` (YENİ - Webhook)**: Iyzico tarafından ödeme onayı için kullanılır. Doğrudan çağrılmaz.

**Not:** v2.2 veritabanı şeması, ödeme başlatma ve onay adımları arasında sepet bilgilerini geçici olarak saklamak için bir `odeme_seanslari` tablosu da içerir.
