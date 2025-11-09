# ProSiparis API v5.1 - Gerçek Message Broker Entegrasyonu

## v5.1 Yenilikleri

Bu sürüm, platformun altyapısal sağlamlaştırma fazının ikinci ve son adımını tamamlamaktadır. Asenkron iletişimi yöneten ve veritabanı üzerinde bir "hot spot" oluşturan **`olay_gunlugu` veritabanı tablosu simülasyonu** tamamen feshedilmiştir. Onun yerine, tüm servisler arası asenkron iletişim, anlık "push" bildirimleri ve garantili teslimat sağlayan, **RabbitMQ konseptini temel alan bir Message Broker mimarisine** taşınmıştır. Bu değişiklik, platformun arka plan işlemlerini daha performanslı, ölçeklenebilir ve dayanıklı (resilient) hale getirmektedir.

## Mimari Konseptler (v5.1 Güncellemeleri)

### Yeni Mimari: "Polling" yerine "Push"

-   **Eski Yaklaşım (Polling):** Dinleyici servisler (`Envanter`, `Raporlama` vb.), yeni bir olay olup olmadığını anlamak için `olay_gunlugu` tablosunu periyodik olarak sorgulamak zorundaydı.
-   **Yeni Yaklaşım (Push/Worker):** Dinleyici servislerin artık kendilerine özel "worker" (tüketici) betikleri (`consume.php`) bulunmaktadır. Bu betikler, RabbitMQ kuyruğunu sürekli dinler ve bir olay geldiği anda anlık olarak ilgili servisi tetikler.

### Publisher ve Consumer Sorumlulukları

-   **Publisher (Yayıncı):** Bir iş akışı sonucunda bir olay yaratan servisler (`Siparis-Servisi`, `Tedarik-Servisi` vb.), artık veritabanına kayıt atmak yerine, merkezi `EventBusService` aracılığıyla olayı RabbitMQ'ya yayınlar.
-   **Consumer (Tüketici):** Başka servislerin yayınladığı olaylarla ilgilenen servisler (`Envanter-Servisi`, `Bildirim-Servisi` vb.), artık veritabanı sorgulamak yerine, kendi RabbitMQ kuyruklarına gelen olayları anlık olarak işler.

## Servisler Arası İletişim Akışı

**Örnek Akış:** Bir siparişin kargoya verilmesi

1.  **Olayın Yayınlanması (Publisher):**
    -   Bir depo çalışanı, `Siparis-Servisi`'nin bir endpoint'i üzerinden siparişi "Kargoya Verildi" olarak işaretler.
    -   `Siparis-Servisi`, sipariş durumunu kendi veritabanında güncelledikten sonra, `EventBusService->publish('siparis.kargolandi', ...)` metodunu çağırır.

2.  **Mesaj Kuyruğu (RabbitMQ):**
    -   `EventBusService`, `siparis.kargolandi` olayını merkezi "prosiparis_events" Exchange'ine gönderir.
    -   RabbitMQ, bu olayı ilgili "routing key" (`siparis.kargolandi`) üzerinden bu olayla ilgilenen tüm kuyruklara (örn: `q_envanter`, `q_raporlama`, `q_bildirim`) kopyalar.

3.  **Olayın Tüketilmesi (Consumers):**
    -   **Anlık olarak**, `Envanter-Servisi`'nin `consume.php` worker'ı `q_envanter` kuyruğundan olayı alır ve stok düşme işlemini başlatır.
    -   **Aynı anda**, `Bildirim-Servisi`'nin `consume.php` worker'ı `q_bildirim` kuyruğundan olayı alır ve müşteriye "Siparişiniz Kargolandı" e-postasını gönderir.
    -   **Yine aynı anda**, `Raporlama-Servisi`'nin `consume.php` worker'ı `q_raporlama` kuyruğundan olayı alır ve satış özet tablosunu günceller.

## Kaldırılan Bileşenler

v5.1 yükseltmesiyle birlikte aşağıdaki eski bileşenler projeden tamamen kaldırılmıştır:

-   **`olay_gunlugu` Tablosu:** Tüm asenkron iletişimi yöneten merkezi veritabanı tablosu ve bu tabloya ait tüm `INSERT` ve `SELECT` sorguları kod tabanından temizlenmiştir.
-   **Polling Kodları:** Dinleyici servislerin içinde bulunan ve periyodik olarak `olay_gunlugu` tablosunu sorgulayan tüm eski "cron-like" mantıklar kaldırılmıştır.
