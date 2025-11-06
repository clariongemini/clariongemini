# ProSiparis_API - Profesyonel Sipariş Yönetim Sistemi Backend

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş PHP ve MySQL tabanlı bir backend (API) sunucusudur.

---

## Versiyon Geçmişi

### v1.0.0 (İlk Sürüm)

Bu ilk sürüm, bir sipariş uygulamasının temel işlevlerini desteklemek için gerekli olan tüm temel API endpoint'lerini içerir.

#### Kurulum

1.  **Veritabanı Oluşturma:**
    *   Bir MySQL veritabanı sunucusunda `prosiparis_db` adında (veya istediğiniz başka bir isimde) bir veritabanı oluşturun.
    *   Proje ana dizininde bulunan `schema.sql` dosyasını bu veritabanına içe aktararak (`import`) gerekli tabloları (`kullanicilar`, `urunler`, `siparisler`, `siparis_detaylari`) oluşturun.

2.  **Yapılandırma:**
    *   `ProSiparis_API/ayarlar.php` dosyasını açın.
    *   `DB_HOST`, `DB_NAME`, `DB_USER` ve `DB_PASS` sabitlerini kendi veritabanı bağlantı bilgilerinize göre düzenleyin.

3.  **Sunucuya Yükleme:**
    *   `ProSiparis_API` klasörünün içeriğini web sunucunuzun erişilebilir bir dizinine yükleyin.

#### API Endpoint'leri (v1.0.0)

Tüm istekler ve yanıtlar JSON formatındadır.

*   **Kullanıcı Kaydı**
    *   **Metot:** `POST`
    *   **Endpoint:** `/api/kullanici_kayit.php`
    *   **Gövde (Body):**
        ```json
        {
            "ad_soyad": "Ahmet Yılmaz",
            "eposta": "ahmet@example.com",
            "parola": "güçlüparola123"
        }
        ```

*   **Kullanıcı Girişi**
    *   **Metot:** `POST`
    *   **Endpoint:** `/api/kullanici_giris.php`
    *   **Gövde (Body):**
        ```json
        {
            "eposta": "ahmet@example.com",
            "parola": "güçlüparola123"
        }
        ```

*   **Tüm Ürünleri Listele**
    *   **Metot:** `GET`
    *   **Endpoint:** `/api/urun_listesi_getir.php`

*   **Ürün Detayını Getir**
    *   **Metot:** `GET`
    *   **Endpoint:** `/api/urun_detay_getir.php?urun_id=1`

*   **Sipariş Oluştur**
    *   **Metot:** `POST`
    *   **Endpoint:** `/api/siparis_olustur.php`
    *   **Gövde (Body):**
        ```json
        {
            "kullanici_id": 1,
            "toplam_tutar": 250.75,
            "sepet": [
                {"urun_id": 3, "adet": 1, "birim_fiyat": 150.50},
                {"urun_id": 5, "adet": 2, "birim_fiyat": 50.125}
            ]
        }
        ```

*   **Sipariş Geçmişini Getir**
    *   **Metot:** `GET`
    *   **Endpoint:** `/api/siparis_gecmisi_getir.php?kullanici_id=1`

---

### v1.1.0 (Güvenlik ve İyileştirmeler)

Bu sürüm, API'ye JWT (JSON Web Token) tabanlı kimlik doğrulama ekleyerek güvenliği önemli ölçüde artırır ve gelecekteki özellikler için zemin hazırlar.

#### Yenilikler

1.  **JWT Kimlik Doğrulama:**
    *   `kullanici_giris.php` endpoint'i artık başarılı bir girişin ardından `kullanici_id` yerine bir `token` döndürür. Bu token, sonraki yetkili isteklerde kullanılmalıdır.
    *   `firebase/php-jwt` kütüphanesi (manuel olarak) projeye entegre edilmiştir.

2.  **Güvenli Endpoint'ler:**
    *   `siparis_gecmisi_getir.php` gibi hassas endpoint'ler artık `Authorization: Bearer <token>` başlığı olmadan erişilemez.
    *   Bu güncelleme, kullanıcıların sadece kendi verilerine erişebilmesini sağlayarak IDOR (Insecure Direct Object Reference) gibi güvenlik açıklarını kapatır.

#### Güncellenen API Kullanımı

*   **Kullanıcı Girişi (Yanıt Değişti)**
    *   **Endpoint:** `/api/kullanici_giris.php`
    *   **Başarılı Yanıt (Örnek):**
        ```json
        {
            "durum": "basarili",
            "mesaj": "Giriş başarılı.",
            "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
        }
        ```

*   **Sipariş Geçmişini Getir (Artık Korumalı)**
    *   **Metot:** `GET`
    *   **Endpoint:** `/api/siparis_gecmisi_getir.php`
    *   **Gerekli Başlık (Header):**
        ```
        Authorization: Bearer <Giriş Yaptıktan Sonra Alınan Token>
        ```
    *   **Not:** URL'den `?kullanici_id=` parametresi kaldırılmıştır. Kullanıcı kimliği artık doğrudan token içerisinden güvenli bir şekilde alınmaktadır.

*   **Sipariş Oluştur (Artık Korumalı)**
    *   **Metot:** `POST`
    *   **Endpoint:** `/api/siparis_olustur.php`
    *   **Gerekli Başlık (Header):**
        ```
        Authorization: Bearer <Giriş Yaptıktan Sonra Alınan Token>
        ```
    *   **Gövde (Body) (Artık `kullanici_id` içermiyor):**
        ```json
        {
            "toplam_tutar": 250.75,
            "sepet": [
                {"urun_id": 3, "adet": 1, "birim_fiyat": 150.50},
                {"urun_id": 5, "adet": 2, "birim_fiyat": 50.125}
            ]
        }
        ```
