# FulcrumOS API v10.1 - Gelişmiş Medya Yönetimi (Watermark, Video, PDF)

Bu sürüm, v10.x "İyileştirme ve Eklentiler" fazının en önemli adımlarından biridir. Bu sürümle birlikte, platformun temel içerik yönetimi yetenekleri, kurumsal düzeyde bir çözüme yakışır şekilde "tam yığın" (full-stack) olarak geliştirilmiştir.

## v10.1 Yenilikleri

Platformun en kritik işlevsel eksiklerinden biri çözülmüştür: **`Medya-Servisi`'ne otomatik 'Watermark' (Filigran) ekleme, 'Video' (.mp4) ve 'PDF' (.pdf) (Kullanım Kılavuzu vb.) yükleme yetenekleri eklenmiştir.** Bu "yatay" iyileştirme, Ürün Yönetimi, CMS, Destek Sistemi gibi platformun birçok modülünü doğrudan zenginleştirmektedir.

## Mimari Konseptler (v10.1 Güncellemeleri)

- **Watermark İş Akışı:** Filigran ekleme süreci, üç servis arasında gerçekleşen kusursuz bir iş akışıdır:
    1.  **Admin-UI (Ayar):** Yönetici, `/admin/ayarlar/watermark` sayfasından filigranı aktifleştirir ve filigran olarak kullanılacak resmi (`watermark.png`) yükler.
    2.  **Organizasyon-Servisi (Depolama):** Bu ayarlar (aktif_mi, resim_url), yeni eklenen `.../organizasyon/ayarlar/watermark` endpoint'leri aracılığıyla `organizasyon-servisi` veritabanında saklanır.
    3.  **Medya-Servisi (Uygulama):** Bir kullanıcı herhangi bir resim yüklediğinde (`POST .../medya/yukle`), `medya-servisi` anlık olarak `organizasyon-servisi`'ne bir iç API çağrısı yaparak filigran ayarlarını kontrol eder. Eğer ayar aktifse, `ImageMagick/GD` kütüphanesini kullanarak filigranı resmin üzerine basar ve resmi öyle kaydeder.

- **Admin-UI'de Gelişmiş Medya Yönetimi:**
    - **Yeniden Kullanılabilir `MediaSelector.jsx`:** v7.4'te oluşturulan bu bileşen, artık `acceptedMimeTypes` adında bir prop alarak "jenerik" hale getirilmiştir. Bu sayede aynı bileşen, filigran ayarları için sadece resim (`['image/jpeg']`), ürün yönetimi için ise resim, video ve PDF (`['image/*', 'video/mp4', 'application/pdf']`) kabul edecek şekilde dinamik olarak kullanılabilmektedir.
    - **Ürün Yönetiminde Sekmeli Medya:** v7.1'deki Ürün Düzenleme sayfası, artan medya türlerini yönetmek için artık "Görseller", "Videolar" ve "Belgeler" adında ayrı sekmelere sahiptir. Bu, kullanıcı deneyimini temiz ve organize tutar.

## Servisler Arası Yeni İletişim Akışları

- **`Medya-Servisi` -> `Organizasyon-Servisi`:** Bu sürümle birlikte, servisler arasında yeni, senkron ve iç ağa özel (internal) bir iletişim kanalı kurulmuştur.
    - `medya-servisi`, bir resim yükleme işlemi sırasında, filigran ayarlarını öğrenmek için `organizasyon-servisi`'ne anlık bir `GET http://organizasyon-servisi/internal/organizasyon/ayarlar/watermark` çağrısı yapar. Bu çağrı, sadece Docker ağının içinden erişilebilir olup, dış dünyaya kapalıdır, bu da maksimum güvenlik ve hız sağlar.

## API Endpoint'leri (v10.1)

### YENİ Backend API Endpoint'leri
- **Servis:** `organizasyon-servisi`
    - `GET /api/admin/organizasyon/ayarlar/watermark`: Mevcut filigran ayarlarını getirir.
    - `PUT /api/admin/organizasyon/ayarlar/watermark`: Filigran ayarlarını (aktif/pasif durumu vb.) günceller.

### GÜNCELLENMİŞ Backend API
- **Servis:** `medya-servisi`
    - `POST /api/admin/medya/yukle`: Bu endpoint artık `image/jpeg`, `image/png` MIME türlerine ek olarak `video/mp4` ve `application/pdf` türlerini de kabul etmektedir.
