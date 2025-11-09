# ProSiparis API v3.1 - Mikroservis Mimarisi (Faz-2): Sipariş Servisi

Bu proje, ProSiparis e-ticaret platformunun dağıtık bir mimariye geçişinin ikinci ve en kritik fazını temsil etmektedir. Bu sürümle birlikte, e-ticaretin kalbi olan **Sipariş Yaşam Döngüsü**, Ana Monolith'ten tamamen ayrıştırılarak kendi kendine yeten, bağımsız bir **Siparis-Servisi** haline getirilmiştir.

**v3.1'in Ana Hedefleri:**
-   **Dayanıklılık:** Sipariş alma ve işleme süreçlerinin, platformun diğer parçalarından (örn: CMS, Tedarik Zinciri) izole edilerek, bu servislerde yaşanacak olası sorunlardan etkilenmemesini sağlamak.
-   **Odaklanmış Sorumluluk:** Siparişle ilgili tüm karmaşık iş mantığını (ödeme, adres yönetimi, kargolama) tek bir serviste toplayarak geliştirmeyi ve bakımı kolaylaştırmak.

---

## Mimari Konseptler (v3.1 Güncellemeleri)

### 1. Mantıksal Servis Ayrımı (Genişletildi)
Platform artık daha fazla, daha odaklanmış servislerden oluşmaktadır:
-   `servisler/auth-servisi/` (v3.0)
-   `servisler/katalog-servisi/` (v3.0)
-   `servisler/envanter-servisi/` (v3.0)
-   `servisler/siparis-servisi/` **(YENİ):** Ödeme, adres yönetimi, kargo seçenekleri ve siparişin oluşturulmasından kargolanmasına kadar olan tüm "fulfillment" sürecinden sorumludur.
-   `servisler/bildirim-servisi/` **(YENİ):** E-posta gibi tüm asenkron bildirim gönderimlerinden sorumludur.

### 2. Ana Monolith (Legacy Core) (Küçültüldü)
Ana Monolith'in rolü önemli ölçüde azalmıştır. Artık sadece şu modülleri yönetmektedir:
-   İadeler (RMA), Tedarik Zinciri (PO), Kuponlar, CMS ve Destek Talepleri.

### 3. Servisler Arası İletişim (Detaylandırıldı)
Servisler, birbirleriyle iki temel yöntemle haberleşir:

#### **Asenkron İletişim (Event Bus üzerinden)**
Siparis-Servisi, tamamladığı önemli iş adımlarını diğer servislerin dinlemesi için "olay" (event) olarak yayınlar. Bu, sistemin dayanıklılığını artırır.
-   **Örnek Akış 1 (Sipariş Başarılı):**
    1.  `Siparis-Servisi`, ödeme başarılı olduğunda `siparis.basarili` olayını yayınlar.
    2.  `Bildirim-Servisi`, bu olayı dinler ve müşteriye "Siparişiniz Alındı" e-postasını gönderir.
-   **Örnek Akış 2 (Sipariş Kargolandı):**
    1.  `Siparis-Servisi` (Depo API'si aracılığıyla), bir siparişi kargoya verdiğinde `siparis.kargolandi` olayını yayınlar.
    2.  `Envanter-Servisi`, bu olayı dinler, ilgili ürünlerin stoğunu düşürür ve maliyet kaydını (Ledger) oluşturur.
    3.  `Bildirim-Servisi`, bu olayı dinler ve müşteriye "Kargonuz Yola Çıktı" e-postasını gönderir.

#### **Senkron İletişim (Internal API Çağrıları üzerinden)**
Bir servisin, bir işlemi tamamlamak için başka bir servisten anlık olarak veri alması gerektiğinde kullanılır.
-   **Örnek Akış (Ödeme Başlatma):**
    1.  `Siparis-Servisi`, ödemeyi başlatmadan önce sepet tutarını doğrulamak zorundadır.
    2.  Bunun için, `Katalog-Servisi`'ne anlık, senkron bir internal API çağrısı (`GET /internal/katalog/varyantlar?ids=...`) yaparak ürünlerin güncel ve doğru fiyatlarını alır.
    3.  Eğer kupon varsa, `Ana Monolith`'e bir internal API çağrısı (`POST /internal/legacy/kupon-dogrula`) yaparak kuponun geçerliliğini kontrol eder.

---

## Kurulum ve Çalıştırma
Kurulum adımları v3.0 ile aynıdır, ancak artık her biri kendi `schema_*.sql` dosyasına sahip daha fazla servis bulunmaktadır. API Gateway (`public/index.php`), siparişle ilgili tüm istekleri otomatik olarak yeni `siparis-servisi`'ne yönlendirecek şekilde güncellenmiştir.
