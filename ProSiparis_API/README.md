# ProSiparis API v2.1 - Profesyonel Sipariş Yönetim Sistemi

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş, modern PHP standartlarına uygun, güvenli ve ölçeklenebilir bir backend (API) sunucusudur.

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
    *   Proje ana dizininde bulunan `schema.sql` dosyasını bu veritabanına içe aktararak tabloları oluşturun. (Bu dosya v2.1 için güncellenmiştir).

4.  **Yapılandırma:**
    *   `config/ayarlar.php` dosyasını açın ve `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` sabitlerini kendi veritabanı bilgilerinize göre düzenleyin.
    *   Aynı dosyada, `JWT_SECRET_KEY` için kendi ürettiğiniz, güvenli ve rastgele bir anahtar girin.

5.  **Web Sunucusu Yapılandırması:**
    *   Web sunucunuzun `mod_rewrite` modülünün aktif olduğundan emin olun.
    *   Güvenlik açısından, sunucunuzun "Document Root" (Ana Dizin) olarak projenin `public/` klasörünü göstermesi şiddetle tavsiye edilir.

---

## Versiyon Geçmişi

### v2.1: Gelişmiş Katalog ve Envanter Yönetimi

Bu sürüm, API'yi basit bir ürün listesinden, profesyonel bir e-ticaret kataloğuna dönüştürür.

-   **Ürün Varyantları:** Ürünler artık "Beden", "Renk" gibi niteliklere ve bu niteliklere bağlı, her birinin kendi SKU'su, fiyatı ve stoğu olan varyantlara sahip olabilir.
-   **Gerçek Zamanlı Stok Kontrolü:** Sipariş oluşturma işlemi artık bir veritabanı işlemi (transaction) içinde stok yeterliliğini kontrol eder. Stok yetersizse sipariş iptal edilir, yeterliyse stoktan otomatik olarak düşülür.
-   **Kategori Yönetimi:** Ürünler artık kategorilere atanabilir. Adminler için tam CRUD (Oluştur, Oku, Güncelle, Sil) kategori yönetimi API'leri eklenmiştir.
-   **Resim Yükleme:** Adminler artık `multipart/form-data` isteği ile ürün oluştururken/güncellerken doğrudan resim dosyası yükleyebilir.
-   **Zengin Ürün Detayı:** `GET /api/urunler/{id}` endpoint'i, mobil uygulamanın dinamik seçimler (renk kutucukları, beden dropdown'ı vb.) oluşturabilmesi için gereken tüm varyant, nitelik ve stok bilgilerini içeren zengin bir JSON yanıtı döndürür.

### v2.0: Mimari Modernizasyon

Bu sürüm, API'yi "Front Controller" mimarisine geçirerek, her isteğin tek bir `public/index.php` dosyası üzerinden yönetilmesini sağlar.
-   **Modern Mimari:** Controller, Service, Middleware ve Core katmanları.
-   **Composer ile Bağımlılık Yönetimi.**
-   **RESTful Yönlendirme (Routing):** Temiz URL'ler.
-   **JWT ile Güvenlik ve Rol Bazlı Erişim Kontrolü (RBAC).**
-   **Kullanıcı Profili ve Tercihleri.**

---

## API Endpoint'leri (v2.1)

_Değişiklikler ve yeni eklenenler aşağıda belirtilmiştir. Diğerleri v2.0 ile aynıdır._

### Herkese Açık Endpoint'ler (Yeni ve Güncellenmiş)

-   **`GET /api/kategoriler`**: Tüm kategorileri listeler.
-   **`GET /api/kategoriler/{id}/urunler`**: Belirli bir kategoriye ait ürünleri listeler.
-   **`GET /api/urunler/{id}` (GÜNCELLENDİ)**: Bir ürünün tüm varyant, nitelik, stok ve kategori bilgilerini içeren zengin detayını getirir.
    -   **Örnek Yanıt:**
        ```json
        {
          "urun_id": 1,
          "urun_adi": "Erkek Tişört",
          "nitelikler": [
            { "nitelik_adi": "Renk", "degerler": ["Kırmızı", "Mavi"] },
            { "nitelik_adi": "Beden", "degerler": ["M", "L"] }
          ],
          "varyantlar": [
            {
              "varyant_id": 12,
              "fiyat": 150.00,
              "stok_adedi": 15,
              "secili_nitelikler": [
                { "nitelik_adi": "Renk", "deger_adi": "Kırmızı" },
                { "nitelik_adi": "Beden", "deger_adi": "L" }
              ]
            }
          ]
        }
        ```

### Kullanıcı Korumalı Endpoint'ler (Güncellenmiş)

-   **`POST /api/siparisler` (GÜNCELLENDİ)**: Yeni bir sipariş oluşturur ve stoktan düşer.
    -   **Body (Artık `varyant_id` kullanılıyor):**
        ```json
        {
          "sepet": [
            { "varyant_id": 12, "adet": 1 },
            { "varyant_id": 15, "adet": 2 }
          ]
        }
        ```

### Admin Korumalı Endpoint'ler (Yeni ve Güncellenmiş)

-   **`POST /api/admin/urunler` (GÜNCELLENDİ)**: `multipart/form-data` kullanarak resim ve varyant bilgileriyle yeni bir ürün ekler.
    -   **Form Verisi:** `urun_adi`, `kategori_id`, `aciklama`, `json_payload` (varyantları içeren JSON string'i)
    -   **Dosya:** `ana_resim`
-   **`POST /api/admin/kategoriler` (YENİ)**: Yeni bir kategori oluşturur.
-   **`PUT /api/admin/kategoriler/{id}` (YENİ)**: Bir kategoriyi günceller.
-   **`DELETE /api/admin/kategoriler/{id}` (YENİ)**: Bir kategoriyi siler.
