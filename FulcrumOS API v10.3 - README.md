# FulcrumOS API v10.3 - Muhasebe Entegrasyon Altyapısı (Faz 1)

Bu sürüm, v10.x "İyileştirme ve Eklentiler" fazının en önemli mimari adımlarından birini atmaktadır. Bu sürümle, platformun finansal verilerini harici sistemlere (örn: ERP, Muhasebe Yazılımları) aktarmak için gerekli olan temel altyapı kurulmuştur.

## v10.3 Yenilikleri

Platformdaki tüm finansal olayları (Satış, İade, Alış) dinleyen, bunları standart bir formata dönüştüren ve bu işlemleri denetleyen (loglayan) yeni bir **`Entegrasyon-Servisi`** kurulmuştur. Bu servis, gelecekteki Logo, Mikro gibi spesifik muhasebe konektörleri için "hazır veri" üreten bir "veri hazırlama katmanı" olarak görev yapacaktır.

## Mimari Konseptler (v10.3 Güncellemeleri)

- **Yeni Servis: `servisler/entegrasyon-servisi/`**
    - Platforma, tüm harici sistem entegrasyonlarının merkezileştirileceği yeni bir mikroservis eklenmiştir. Bu servisin ilk sorumluluğu "Muhasebe Entegrasyonu" için veri toplamaktır.

- **Finansal Olay Akışı (Event-Driven Architecture):**
    - Muhasebe entegrasyonu, tamamen asenkron ve olay tabanlı bir mimari üzerine kurulmuştur. Bu, platformun ana iş akışlarını (sipariş, iade vb.) yavaşlatmadan, entegrasyon işlemlerinin arka planda güvenilir bir şekilde çalışmasını sağlar.
    1.  **Publisher (Yayıncı):** `Siparis-Servisi` gibi bir servis, finansal bir döngüyü tamamladığında (örn: bir siparişi kargoladığında) RabbitMQ'ya bir "Zengin Olay" yayınlar.
    2.  **Message Broker (Mesaj Kuyruğu):** RabbitMQ, bu olayı ilgili kuyruğa ekler.
    3.  **Consumer (Tüketici):** `Entegrasyon-Servisi`, bu kuyruğu sürekli dinleyen bir "worker" betiği çalıştırır. İlgili bir olay geldiğinde, bu olayı alır ve işlemeye başlar.
    4.  **Transformation & Auditing (Dönüştürme ve Denetim):** Servis, olaydaki zengin veriyi (müşteri, ürünler, tutarlar) jenerik bir XML/JSON formatına dönüştürür ve bu çıktıyı, işlemin durumunu (`beklemede`) da içerecek şekilde kendi `muhasebe_loglari` veritabanı tablosuna kaydeder.

- **Admin-UI'de Denetim Arayüzü:** `/admin/entegrasyonlar/muhasebe` adresine eklenen yeni sayfa, `muhasebe_loglari` tablosunu yöneticilere sunar. Bu arayüz sayesinde, harici sistemlere aktarımı bekleyen, başarıyla tamamlanan veya hata alan tüm finansal kayıtlar merkezi bir yerden izlenebilir ve yönetilebilir. "Tekrar Dene" butonu, özellikle harici API'lerde yaşanan geçici sorunları çözmek için kritik bir operasyonel araçtır.

## Servisler Arası Yeni İletişim Akışları

- **Asenkron Olay Tüketimi (Event Consumption):** `Entegrasyon-Servisi`, platformun finansal sağlığını takip etmek için aşağıdaki 3 ana "Zengin Olayı" dinler:
    - **`siparis.kargolandi`**: Bir "Satış Faturası" ihtiyacını tetikler. Bu olay, muhasebedeki "Gelir Tanıma" (Revenue Recognition) prensibi gereği, ödemenin alındığı `siparis.basarili` olayından daha doğru bir finansal andır.
    - **`iade.odeme_basarili`**: Bir "İade Faturası" veya "Gider Pusulası" ihtiyacını tetikler.
    - **`tedarik.mal_kabul_yapildi`**: Bir "Alış Faturası" veya "Maliyet Kaydı" ihtiyacını tetikler.

## API Endpoint'leri (v10.3)

### YENİ Backend API Endpoint'leri
- **Servis:** `entegrasyon-servisi`
    - `GET /api/admin/entegrasyonlar/muhasebe-loglari`: Tüm muhasebe entegrasyon loglarını listeler.
    - `POST /api/admin/entegrasyonlar/muhasebe-loglari/:logId/tekrar-dene`: Hata durumundaki bir log kaydını tekrar işlenmek üzere "beklemede" durumuna alır.

### YENİ Yetki
- **Servis:** `auth-servisi`
    - `entegrasyon_yonet`: Kullanıcıların muhasebe entegrasyon loglarını görüntüleme ve yönetme yetkisini tanımlar.
