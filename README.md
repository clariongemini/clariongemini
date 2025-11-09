# ProSiparis API v4.3 - Monolith'in Feshedilmesi

Bu sürüm, ProSiparis API'sinin mikroservis dönüşüm projesini tamamlar. Ana Monolith'te kalan son üç destek modülü (CMS, Destek, Otomasyon) de kendi bağımsız servislerine ayrıştırılmış ve platform **%100 mikroservis mimarisine** geçmiştir.

## v4.3 Yenilikleri:

1.  **Ana Monolith Feshedildi:** Ana Monolith'in kod tabanı (`/src`), veritabanı şeması (`schema_core.sql` içindeki tablolar) ve legacy giriş noktası (`index.legacy.php`) tamamen silinmiştir. Platform artık daha yalın, daha modüler ve bakımı daha kolay bir yapıdadır.
2.  **Son Modüller Ayrıştırıldı:** CMS, Destek Talepleri (Ticketing) ve Pazarlama Otomasyonu (Kalıcı Sepet, Cron Job'lar) artık kendi kendine yeten, bağımsız mikroservisler olarak çalışmaktadır.

---

## Mimari Konseptler (v4.3 Güncellemeleri)

### Yeni Eklenen Servisler
-   `servisler/cms-servisi/`: Sayfalar, bannerlar gibi statik içerikleri yönetir.
-   `servisler/destek-servisi/`: Kullanıcıların destek taleplerini ve mesajlarını yönetir.
-   `servisler/otomasyon-servisi/`: Kalıcı sepet gibi kullanıcıya yönelik otomasyonları ve `cron/run` gibi zamanlanmış görevleri yönetir.

### Ana Monolith'in Durumu
-   **Feshedildi (Decommissioned):** Ana Monolith (Legacy Core) artık platformda mevcut değildir. Tüm sorumlulukları, ilgili odaklanmış mikroservislere dağıtılmıştır. `schema_core.sql` dosyası artık sadece tüm servisler tarafından paylaşılan merkezi `olay_gunlugu` tablosunu içermektedir.

---

## Servisler Arası Yeni İletişim Akışları

### Otomasyon Servisi'nin Gelecekteki Rolü
-   `Otomasyon-Servisi`, zamanlanmış görevleri (`cron/run`) yürüten merkezi birim olarak, gelecekte diğer servislerle haberleşebilir. Örneğin, bir "terk edilmiş sepet" otomasyonu, `Kupon-Servisi`'ne dahili bir API çağrısı yaparak kullanıcıya özel bir "Seni Özledik" kuponu oluşturulmasını tetikleyebilir ve bu bilgiyi `Bildirim-Servisi`'ne bir olay (event) olarak göndererek kullanıcıya e-posta atılmasını sağlayabilir.

---

## API Endpoint'leri (v4.3)

### Yeni/Taşınan Endpoint'ler
-   **CMS:**
    -   `GET /api/sayfa/{slug}`
    -   `GET /api/bannerlar`
    -   `/api/admin/sayfalar` (Tüm CRUD operasyonları)
    -   `/api/admin/bannerlar` (Tüm CRUD operasyonları)
-   **Destek:**
    -   `/api/kullanici/destek-talepleri` (Tüm CRUD operasyonları)
    -   `/api/admin/destek-talepleri` (Tüm CRUD operasyonları)
-   **Otomasyon:**
    -   `GET /api/sepet`
    -   `POST /api/sepet/guncelle`
    -   `POST /api/cron/run`

### Kaldırılan Endpoint'ler
-   Ana Monolith'in API'sinden yukarıda listelenen tüm yollar ve `index.legacy.php` tarafından yönetilen diğer tüm endpoint'ler tamamen kaldırılmıştır. API Gateway artık Monolith'e hiçbir istek yönlendirmemektedir.
