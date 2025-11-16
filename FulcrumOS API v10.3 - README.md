# FulcrumOS API v10.3 - Muhasebe Entegrasyon Altyapısı (Faz 1)

Bu sürüm, FulcrumOS platformunun finansal döngüsünü tamamlayan kritik bir altyapı güncellemesidir. Faz 1, platformdaki tüm finansal hareketlerin denetlenebilir bir şekilde toplanmasını ve standart bir formata dönüştürülmesini sağlar.

## v10.3 Yenilikleri

Platformdaki tüm finansal olayları (Satış, İade, Alış) dinleyen, bunları standart bir formata dönüştüren ve denetleyen (loglayan) yeni bir **Entegrasyon-Servisi** kuruldu. Bu altyapı, gelecekte herhangi bir muhasebe yazılımıyla (Logo, Mikro, SAP vb.) entegrasyon için zemin hazırlar.

## Mimari Konseptler (v10.3 Güncellemeleri)

### 1. Yeni Servis: `servisler/entegrasyon-servisi/`
- **Sorumluluk:** Finansal olayları Message Broker üzerinden dinlemek, veriyi jenerik bir muhasebe formatına dönüştürmek ve bu işlemi `muhasebe_loglari` tablosuna kaydetmek.
- **Yapı:** Servis, senkron API istekleri için bir `public/index.php` giriş noktasına ve asenkron olayları dinlemek için bir `consumer.php` (worker) betiğine sahiptir.

### 2. Finansal Olay Akışı (Event Flow)
Bu sürümle birlikte finansal veriler için standart bir olay akışı oluşturulmuştur:
1.  **Publisher (Yayıncı):** Kaynak servis (örn: `Siparis-Servisi`), bir iş olayı gerçekleştiğinde (örn: sipariş kargolandığında) zengin bir olayı (`siparis.kargolandi`) RabbitMQ'ya yayınlar.
2.  **Message Broker (RabbitMQ):** Olayı alır ve ilgili kuyruğa (`finansal_olaylar`) iletir.
3.  **Consumer (Tüketici):** `Entegrasyon-Servisi`, bu kuyruğu dinler ve olayı tüketir.
4.  **Transformation & Logging (Dönüştürme ve Loglama):** Servis, olay verisini standart bir JSON formatına dönüştürür ve `muhasebe_loglari` tablosuna `durum: 'beklemede'` olarak kaydeder.

## Servisler Arası Yeni İletişim Akışı

`Entegrasyon-Servisi`, aşağıdaki 3 ana finansal "Zengin Olayı" dinleyecek şekilde yapılandırılmıştır:

- **`siparis.kargolandi`**: Satış gelirinin muhasebeleştiği anı temsil eder ve "Satış Faturası" kaydını tetikler.
- **`iade.odeme_basarili`**: Müşteriye para iadesi yapıldığında "İade Faturası" (veya Gider Pusulası) kaydını tetikler.
- **`tedarik.mal_kabul_yapildi`**: Tedarikçiden gelen ürünler depoya kabul edildiğinde "Alış Faturası" (Maliyet Kaydı) işlemini tetikler.

## API Endpoint'leri (v10.3)

### Yeni Backend API Endpoint'leri (`entegrasyon-servisi`)
- `GET /api/admin/entegrasyonlar/muhasebe-loglari`: Muhasebe entegrasyon loglarının tamamını listeler.
- `POST /api/admin/entegrasyonlar/muhasebe-loglari/:logId/tekrar-dene`: `durum: 'hata'` olan bir log kaydını, `durum: 'beklemede'`'ye geri çekerek worker'ın işlemi tekrar denemesini sağlar.

### Yeni Yetki (`auth-servisi`)
- **`entegrasyon_yonet`**: Yukarıdaki API endpoint'lerine erişimi kontrol eden ve Admin-UI'daki "Muhasebe Logları" sayfasını yönetme yetkisi veren yeni ACL yetkisidir.
