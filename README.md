# ProSiparis API v4.2 - Kupon Servisi'nin Ayrıştırılması

Bu sürüm, `Siparis-Servisi`'nin Ana Monolith'e olan son kritik senkron bağımlılığını kırarak, platformun ana ticaret akışının dayanıklılığını (resilience) en üst seviyeye çıkarır.

## v4.2 Yenilikleri:

1.  **Sipariş Akışının Bağımsızlaştırılması:** `Siparis-Servisi`'nin kupon doğrulamak için Ana Monolith'e yaptığı tehlikeli senkron çağrı kaldırıldı. Bu, Ana Monolith'te yaşanacak bir sorunun, kuponlu siparişlerin oluşturulmasını engelleme riskini tamamen ortadan kaldırır.
2.  **Kupon Modülü Ayrıştırıldı:** Kupon yönetimi, kendine ait bir veritabanı ve iş mantığına sahip olan yeni ve bağımsız bir `kupon-servisi`'ne taşındı.

---

## Mimari Konseptler (v4.2 Güncellemeleri)

### Yeni Servis: `servisler/kupon-servisi/`
-   **Sorumluluk:** Kuponların oluşturulması, yönetilmesi, doğrulanması ve kullanım sayılarının takibinden sorumludur.
-   **Ana Monolith'ten Ayrılan Modüller:** Kuponlar (v2.3) modülü Ana Monolith'ten tamamen ayrılmıştır.
-   **Ana Monolith'in Kalan Son Görevleri:** CMS, Destek Talepleri (Ticketing) ve Pazarlama Otomasyonu (Cron Job).

---

## Servisler Arası Yeni İletişim Akışları

### Senkron Akış (Kupon Doğrulama)
-   **`Siparis-Servisi` -> `Kupon-Servisi` Çağrısı:**
    -   `Siparis-Servisi`, bir ödeme başlatmadan önce, artık kupon kodunu doğrulamak için `Kupon-Servisi`'ne güvenli bir dahili API çağrısı yapar: `POST /internal/kupon/dogrula`.

### Asenkron Akış (Kupon Kullanımını Kaydetme)
-   **`Kupon-Servisi`'nin Olay Aboneliği:**
    -   `Kupon-Servisi`, `siparis.basarili` olayını dinler.
    -   Olayın içinde bir `kullanilan_kupon_kodu` varsa, ilgili kuponun `kac_kez_kullanildi` sayacını kendi veritabanında asenkron olarak +1 artırır ve bir kullanım logu oluşturur. Bu yapı, sipariş anındaki doğrulamayı hızlı tutar ve kullanım kaydını dayanıklı hale getirir.

---

## API Endpoint'leri (v4.2)

### Yeni/Taşınan Endpoint'ler
-   `POST /api/sepet/kupon-dogrula`: Sepet ekranında bir kuponun geçerliliğini kontrol etmek için kullanılır. Artık `Kupon-Servisi` tarafından yönetilmektedir.
-   `GET, POST, PUT, DELETE /api/admin/kuponlar`: Admin panelinin kuponları yönetmesi için gereken tüm CRUD endpoint'leri. Artık `Kupon-Servisi` tarafından yönetilmektedir.
-   `POST /internal/kupon/dogrula`: `Siparis-Servisi`'nin kupon doğrulamak için kullandığı yeni dahili endpoint.

### Kaldırılan Endpoint'ler
-   `POST /internal/legacy/kupon-dogrula`: Ana Monolith'teki eski kupon doğrulama endpoint'i tamamen kaldırılmıştır.
-   Ana Monolith'in public API'sindeki tüm `/api/sepet/kupon-dogrula` ve `/api/admin/kuponlar` endpoint'leri kaldırılmıştır.
