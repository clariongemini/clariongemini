# ProSiparis API v5.2 - Pazarlanabilirlik & SEO Motoru

## v5.2 Yenilikleri

Bu sürüm, platformun sağlam altyapısı üzerine ilk büyük "iş değeri" katmanını eklemektedir. v5.2, platformu Google/Bing gibi arama motorlarında **"bulunabilir"** (teknik SEO, sitemap, `robots.txt`) ve dijital pazarlama kanallarında **"reklamlanabilir"** (Google Merchant, Bing Shopping XML feed'leri) hale getiren kapsamlı bir "Pazarlanabilirlik ve SEO Motoru" entegrasyonu sunmaktadır. Ayrıca, üçüncü parti entegrasyonların API anahtarlarını yönetmek için merkezi ve güvenli bir "Anahtar Kasası" sunar.

## Mimari Konseptler (v5.2 Güncellemeleri)

### Katalog-Servisi: Pazarlama Verisi ile Zenginleştirme

-   `urunler`, `kategoriler` ve `urun_varyantlari` tabloları, modern pazarlama ihtiyaçlarını karşılamak üzere ciddi şekilde zenginleştirilmiştir.
-   **SEO & Sosyal Medya:** `meta_baslik`, `meta_aciklama`, `slug`, `canonical_url` ve `og_resim_url` gibi sütunlar, arama motoru optimizasyonu ve sosyal medya paylaşım standartları için eklenmiştir.
-   **Alışveriş Reklamları (Merchant Feeds):** Google Shopping ve Bing Shopping için zorunlu olan `gtin` (barkod), `mpn` (üretici parça no) ve `marka` gibi sütunlar eklenerek, platformun ürün reklamı yayınlama yeteneği kazandırılmıştır.

### Organizasyon-Servisi: API Anahtar Kasası

-   Üçüncü parti API anahtarlarının (`iyzico`, `google_analytics` vb.) kod içinde veya yapılandırma dosyalarında güvenli olmayan bir şekilde saklanmasını önlemek için `Organizasyon-Servisi`'ne merkezi bir "API Anahtar Kasası" eklenmiştir.
-   `entegrasyon_anahtarlari` adlı yeni bir tablo, anahtar değerlerini `openssl` ile şifreleyerek veritabanında saklar.
-   Diğer servisler artık bu anahtarlara ihtiyaç duyduğunda, `Organizasyon-Servisi`'ne güvenli bir dahili API çağrısı (`/internal/organizasyon/anahtar-al`) yaparak ihtiyaç duydukları anahtarı anlık olarak alırlar.

## Servisler Arası Yeni İletişim Akışları

### Dinamik Site Haritası Oluşturma (`GET /sitemap.xml`)

1.  İstek, `Gateway-Servisi` tarafından `Katalog-Servisi`'ne yönlendirilir.
2.  `Katalog-Servisi`, kendi veritabanından tüm `urunler` ve `kategoriler` için `slug` (URL) listesini çeker.
3.  `Katalog-Servisi`, `CMS-Servisi`'ne `/internal/cms/sayfa-sluglari` gibi bir dahili API çağrısı yaparak tüm statik `sayfalar`ın `slug` listesini alır.
4.  Topladığı tüm URL'leri standart bir XML site haritası formatında birleştirir ve kullanıcıya sunar.

### Google Merchant Feed Oluşturma (`GET /api/feeds/google-merchant.xml`)

1.  İstek, `Gateway-Servisi` tarafından `Katalog-Servisi`'ne yönlendirilir.
2.  `Katalog-Servisi`, kendi veritabanından ürünlerin `gtin`, `marka`, `fiyat` gibi tüm pazarlama ve temel bilgilerini çeker.
3.  `Katalog-Servisi`, her bir ürünün stok durumunu öğrenmek için `Envanter-Servisi`'ne `/internal/envanter/stok-durumu` gibi bir dahili API çağrısı yapar.
4.  Topladığı tüm zenginleştirilmiş veriyi, Google Merchant standartlarına uygun bir XML formatında birleştirir ve sunar.

## API Endpoint'leri (v5.2)

### Yeni PUBLIC Endpoint'ler

-   `GET /sitemap.xml`: Dinamik olarak oluşturulan site haritası.
-   `GET /robots.txt`: Yönetici panelinden güncellenebilen `robots.txt` içeriği.
-   `GET /api/feeds/google-merchant.xml`: Google Alışveriş reklamları için ürün veri feed'i.
-   `GET /api/feeds/bing-shopping.xml`: Bing Alışveriş reklamları için ürün veri feed'i.

### Yeni ADMIN Endpoint'ler

-   `PUT /api/admin/site-ayarlari/robots`: `robots.txt` içeriğini günceller. (Yetki: `cms_yonet`)
-   `GET /api/admin/entegrasyonlar`: Kayıtlı tüm entegrasyon anahtarlarını listeler. (Yetki: `organizasyon_yonet`)
-   `POST /api/admin/entegrasyonlar`: Yeni bir entegrasyon anahtarı ekler. (Yetki: `organizasyon_yonet`)
-   `DELETE /api/admin/entegrasyonlar/{id}`: Bir entegrasyon anahtarını siler. (Yetki: `organizasyon_yonet`)
