# ProSiparis API v2.4 - Profesyonel E-Ticaret API

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş, modern PHP standartlarına uygun, güvenli ve tam teşekküllü bir e-ticaret backend (API) sunucusudur.

---

## Kurulum

1.  **Gerekli Araçlar:** PHP >= 7.4, Composer, MySQL, Apache (veya `.htaccess` destekli başka bir web sunucusu).
2.  **Projeyi Kurma:** Projenin ana dizininde `composer install` komutunu çalıştırın.
3.  **Veritabanı Oluşturma:** `schema.sql` dosyasını (v2.4) veritabanınıza içe aktarın.
4.  **Yapılandırma:** Gerekli veritabanı, JWT, SMTP ve Iyzico ayarlarını `config/` klasöründeki dosyalarda düzenleyin.
5.  **Web Sunucusu:** Sunucunuzun "Document Root" olarak projenin `public/` klasörünü göstermesi tavsiye edilir.

---

## Versiyon Geçmişi

### v2.4: Sosyal Kanıt ve Kullanıcı Etkileşimi

Bu sürüm, kullanıcıların platformla etkileşimini artırır ve sosyal kanıt mekanizmaları ekler.

-   **Ürün Değerlendirme ve Puanlama:** Kullanıcılar artık durumu "Teslim Edildi" olan siparişlerindeki ürünlere 1-5 arası puan verebilir ve yorum yazabilir.
-   **Denormalize Edilmiş Puanlar:** Ürün listeleme performansını artırmak için `urunler` tablosu artık `ortalama_puan` ve `degerlendirme_sayisi`'nı doğrudan tutar ve her yeni değerlendirmede otomatik güncellenir.
-   **Favori / İstek Listesi:** Kullanıcılar ilgilendikleri ürünleri favori listelerine ekleyebilir, çıkarabilir ve listeleyebilir.
-   **Zenginleştirilmiş API Yanıtları:** Ürün listeleme ve detay endpoint'leri artık ortalama puan ve (giriş yapmış kullanıcı için) favori durumunu da döndürür.

### v2.3: Lojistik, Bildirimler ve Promosyon Motoru
-   **Promosyon/Kupon Sistemi, Otomatik E-posta Bildirimleri, Sipariş Lojistiği Takibi.**

### v2.2: Tam Kapsamlı Ödeme (Checkout) ve Lojistik
-   **Kullanıcı Adres Yönetimi (CRUD), Kargo Seçenekleri, Iyzico Entegrasyonu ve Webhook.**

### v2.1: Gelişmiş Katalog ve Envanter Yönetimi
-   **Ürün Varyantları, Kategoriler ve Gerçek Zamanlı Stok Kontrolü.**

### v2.0: Mimari Modernizasyon
-   **Front Controller Mimarisi, Composer, JWT/RBAC Güvenliği.**

---

## API Endpoint'leri (v2.4)

_v2.3'e eklenenler ve güncellenenler aşağıdadır._

### Herkese Açık Endpoint'ler (Güncellenmiş)

-   **`GET /api/urunler` (GÜNCELLENDİ)**: Dönen her ürün objesi artık `ortalama_puan` ve `degerlendirme_sayisi` alanlarını içerir.
-   **`GET /api/urunler/{id}` (GÜNCELLENDİ)**: Dönen ürün objesi artık `ortalama_puan`, `degerlendirme_sayisi` ve (giriş yapılmışsa) `kullanicinin_favorisi_mi` alanlarını içerir.
-   **`GET /api/urunler/{id}/degerlendirmeler` (YENİ)**: Bir ürüne ait tüm değerlendirmeleri (yorum, puan, kullanıcı adı) listeler.

### Kullanıcı Korumalı Endpoint'ler (`Authorization: Bearer <token>` Gerekli)

-   **`POST /api/urunler/{id}/degerlendirme` (YENİ)**: Bir ürüne puan ve yorum ekler. Kullanıcının ürünü satın almış ve teslim almış olması gerekir.
    -   **Body:** `{ "puan": 5, "yorum": "Harika ürün!" }`
-   **`DELETE /api/degerlendirmeler/{id}` (YENİ)**: Kullanıcının kendi değerlendirmesini silmesini sağlar.
-   **`GET /api/kullanici/favoriler` (YENİ)**: Kullanıcının favori ürünlerini listeler.
-   **`POST /api/kullanici/favoriler` (YENİ)**: Bir ürünü favorilere ekler.
    -   **Body:** `{ "urun_id": 123 }`
-   **`DELETE /api/kullanici/favoriler/{urun_id}` (YENİ)**: Bir ürünü favorilerden kaldırır.

### Admin Korumalı Endpoint'ler (Güncellenmiş)

-   **`PUT /api/admin/siparisler/{id}` (GÜNCELLENDİ)**: Sipariş durumu artık "Teslim Edildi" olarak da güncellenebilir.
-   **`DELETE /api/degerlendirmeler/{id}` (YETKİ GÜNCELLEMESİ)**: Adminler de bu endpoint ile herhangi bir değerlendirmeyi silebilir.
