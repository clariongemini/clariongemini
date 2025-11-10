# ProSiparis API v6.1 - Mimari Mükemmelleştirme: Zengin Olaylar

## v6.1 Yenilikleri

Bu sürüm, platformun altyapısındaki son "teknik borcu" temizleyerek mimariyi mükemmelleştirmeye odaklanmaktadır. v6.1, "Zengin Olaylar" (Rich Events) ve "Tüketici Bağımsızlığı" (Consumer Independence) prensiplerini hayata geçirir. Artık, bir olayı yayınlayan servis (Publisher), o olayla ilgilenebilecek tüm servislerin (Consumer) ihtiyaç duyabileceği **tüm zenginleştirilmiş veriyi** (örneğin sadece `urun_id` değil, aynı zamanda `urun_adi`, `sku`, `kategori_adi` vb.) olayın içeriğine (payload) eklemekle sorumludur. Bu değişiklik, tüketici servislerin veri toplamak için başka servislere anlık (senkron) çağrılar yapma ihtiyacını ortadan kaldırarak platformun genel dayanıklılığını (resilience) ve performansını artırır.

## Mimari Konseptler (v6.1 Güncellemeleri)

### Zengin Olay (Rich Event) Prensibi

-   **Eski Yaklaşım:** Olaylar, sadece temel ID'leri (`urun_id`, `depo_id`) içeriyordu. Bir olayı tüketen servis (`Raporlama-Servisi` gibi), bu ID'leri kullanarak ihtiyaç duyduğu diğer bilgileri (`urun_adi`, `depo_adi`) ilgili servislere anlık API çağrıları yaparak "zenginleştirmek" zorundaydı. Bu, tüketicileri diğer servislere bağımlı kılıyordu.
-   **Yeni Yaklaşım (v6.1):** Olayı yayınlayan servis, olayı yayınlamadan hemen önce gerekli tüm dahili API çağrılarını yaparak olayın içeriğini zenginleştirir. Örneğin, `Siparis-Servisi` artık `siparis.kargolandi` olayını yayınlarken `urun_adi`, `varyant_sku`, `kategori_adi` ve `depo_adi` gibi bilgileri de payload'a ekler.

### Tüketici Bağımsızlığı (Consumer Independence)

-   Bu mimarinin doğal bir sonucu olarak, `Raporlama-Servisi` ve `Bildirim-Servisi` gibi tüketiciler artık tamamen bağımsızdır. Bir olayı işlemek için ihtiyaç duydukları tüm bilgi, aldıkları olayın içinde mevcuttur.
-   **Sonuç:** `Katalog-Servisi` veya `Organizasyon-Servisi` anlık olarak hizmet veremese bile, `Raporlama-Servisi` olayları işlemeye devam edebilir. Bu, sistemin genel dayanıklılığını ve servisler arası izolasyonu en üst seviyeye çıkarır.

## Güncellenmiş Servisler Arası İletişim Akışı

### Asenkron Akış (Zengin Olay ile)

**Örnek:** Bir siparişin kargoya verilmesi

1.  **Olayın Zenginleştirilmesi ve Yayınlanması (Publisher - `Siparis-Servisi`):**
    -   `Siparis-Servisi`, `siparis.kargolandi` olayını hazırlarken, siparişteki `varyant_id`'ler için `Katalog-Servisi`'ne ve `depo_id` için `Organizasyon-Servisi`'ne **kendi içinde** dahili API çağrıları yapar.
    -   Topladığı tüm bu zengin veriyi (`urun_adi`, `depo_adi` vb.) olayın payload'una ekler ve Message Broker'a (RabbitMQ) yayınlar.

2.  **Olayın Tüketilmesi (Bağımsız Consumer - `Raporlama-Servisi`):**
    -   `Raporlama-Servisi`'nin "worker" betiği, `siparis.kargolandi` olayını kuyruktan alır.
    -   `RaporlamaService`, rapor tablosuna kayıt atmak için ihtiyaç duyduğu `urun_adi`, `depo_adi` gibi tüm bilgilere zaten sahiptir. **Hiçbir ek API çağrısı yapmadan** doğrudan kendi veritabanına yazar.

## Kaldırılan Bileşenler

-   `Raporlama-Servisi` ve `Bildirim-Servisi` gibi tüketici servislerin içindeki, veri zenginleştirmek amacıyla diğer servislere yapılan tüm dahili API çağrıları (`internalApiCall` veya `file_get_contents`) kaldırılmıştır.
