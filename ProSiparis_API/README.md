# ProSiparis API v2.0 - Profesyonel Sipariş Yönetim Sistemi

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş, modern PHP standartlarına uygun, güvenli ve ölçeklenebilir bir backend (API) sunucusudur.

---

## v2.0 Mimarisi ve Özellikleri

Bu sürüm, API'yi "Front Controller" mimarisine geçirerek, her isteğin tek bir `public/index.php` dosyası üzerinden yönetilmesini sağlar. Bu, bakımı kolaylaştırır ve kod tekrarını önler.

-   **Modern Mimari:** Controller, Service, Middleware ve Core katmanları ile organize edilmiş kod yapısı.
-   **Composer ile Bağımlılık Yönetimi:** `firebase/php-jwt` gibi kütüphaneler artık Composer ile yönetilmektedir.
-   **RESTful Yönlendirme (Routing):** `/api/urunler`, `/api/siparisler/{id}` gibi temiz URL'ler.
-   **JWT ile Güvenlik:** Tüm hassas endpoint'ler, `Authorization: Bearer <token>` başlığı gerektiren JWT kimlik doğrulaması ile korunmaktadır.
-   **Rol Bazlı Erişim Kontrolü (RBAC):** `kullanici` ve `admin` rolleri tanımlanmıştır. Belirli endpoint'ler sadece admin yetkisine sahip kullanıcılar tarafından erişilebilir.
-   **Tam Kapsamlı Yönetim:** Adminler için ürün (CRUD) ve sipariş yönetimi API'leri.
-   **Kullanıcı Profili ve Tercihleri:** Kullanıcılar kendi profillerini ve uygulama tercihlerini (dil, tema) yönetebilirler.

---

## Kurulum

1.  **Gerekli Araçlar:**
    *   PHP >= 7.4
    *   Composer
    *   MySQL Veritabanı Sunucusu
    *   Apache (veya `.htaccess` destekli başka bir web sunucusu)

2.  **Projeyi Kurma:**
    *   Projeyi sunucunuza klonlayın veya indirin.
    *   Projenin ana dizininde (`ProSiparis_API/`) terminali açın ve bağımlılıkları yükleyin:
        ```bash
        composer install
        ```

3.  **Veritabanı Oluşturma:**
    *   Bir MySQL veritabanı sunucusunda `prosiparis_db` adında bir veritabanı oluşturun.
    *   Proje ana dizininde bulunan `schema.sql` dosyasını bu veritabanına içe aktararak tabloları oluşturun.

4.  **Yapılandırma:**
    *   `config/ayarlar.php` dosyasını açın ve `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` sabitlerini kendi veritabanı bilgilerinize göre düzenleyin.
    *   Aynı dosyada, `JWT_SECRET_KEY` için kendi ürettiğiniz, güvenli ve rastgele bir anahtar girin.

5.  **Web Sunucusu Yapılandırması:**
    *   Web sunucunuzun `mod_rewrite` modülünün aktif olduğundan emin olun.
    *   Güvenlik açısından, sunucunuzun "Document Root" (Ana Dizin) olarak projenin `public/` klasörünü göstermesi şiddetle tavsiye edilir.

---

## API Endpoint'leri (v2.0)

### Kimlik Doğrulama

-   **`POST /api/kullanici/kayit`**: Yeni kullanıcı oluşturur.
    -   **Body:** `{ "ad_soyad": "...", "eposta": "...", "parola": "..." }`
-   **`POST /api/kullanici/giris`**: Kullanıcı girişi yapar ve token döndürür.
    -   **Body:** `{ "eposta": "...", "parola": "..." }`
    -   **Başarılı Yanıt:** `{ "durum": "basarili", "veri": { "token": "...", "kullanici_tercihleri": { "dil": "tr-TR", "tema": "system" } } }`

### Herkese Açık Endpoint'ler

-   **`GET /api/urunler`**: Tüm ürünleri listeler.
-   **`GET /api/urunler/{id}`**: Belirtilen ID'ye sahip ürünü getirir.

### Kullanıcı Korumalı Endpoint'ler (`Authorization: Bearer <token>` Gerekli)

-   **`GET /api/kullanici/profil`**: Mevcut kullanıcının profil bilgilerini getirir.
-   **`PUT /api/kullanici/profil`**: Mevcut kullanıcının profilini günceller.
    -   **Body:** `{ "ad_soyad": "...", "tercih_dil": "en-US", "tercih_tema": "dark" }`
-   **`GET /api/siparisler`**: Mevcut kullanıcının sipariş geçmişini listeler.
-   **`POST /api/siparisler`**: Mevcut kullanıcı için yeni bir sipariş oluşturur.
    -   **Body:** `{ "toplam_tutar": ..., "sepet": [...] }`

### Admin Korumalı Endpoint'ler (Admin Rolü ve Token Gerekli)

-   **`POST /api/admin/urunler`**: Yeni bir ürün ekler.
    -   **Body:** `{ "urun_adi": "...", "fiyat": ..., "aciklama": "...", "resim_url": "..." }`
-   **`PUT /api/admin/urunler/{id}`**: Bir ürünü günceller.
    -   **Body:** `{ "urun_adi": "...", "fiyat": ... }`
-   **`DELETE /api/admin/urunler/{id}`**: Bir ürünü siler.
-   **`GET /api/admin/siparisler`**: **Tüm** kullanıcıların siparişlerini listeler.
-   **`PUT /api/admin/siparisler/{id}`**: Bir siparişin durumunu günceller.
    -   **Body:** `{ "durum": "Kargoya Verildi" }`
