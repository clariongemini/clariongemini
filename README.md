# ProSiparis API v8.0 - Teknik Sağlamlaştırma (Faz 1: Backend Testleri)

Bu sürüm, ProSiparis API'sinin "işlevsel olarak tamamlanmış" bir platformdan, "üretim ortamına hazır" ve "sağlamlaştırılmış" bir platforma geçişinin ilk ve en önemli adımını temsil etmektedir. v8.0 ile, platformun "kırılganlığını" azaltmak ve uzun vadeli sürdürülebilirliğini sağlamak amacıyla backend mikroservisleri için kapsamlı bir otomatik test altyapısı kurulmuştur.

## v8.0 Yenilikleri: Teknik Borcun Ödenmesi

-   **Backend Test Altyapısı Kuruldu:** v7.8 analizinde tespit edilen en kritik teknik borç olan "otomatik test eksikliği" bu sürümle giderilmiştir. Her bir PHP mikroservisi artık PHPUnit tabanlı bir test paketine sahiptir. Bu, gelecekteki değişikliklerin mevcut işlevselliği bozmamasını garanti altına alan bir güvenlik ağı sağlar.

---

## Mimari Konseptler (v8.0 Güncellemeleri)

### 1. Test Altyapısı: PHPUnit ve İzole Veritabanları
-   **PHPUnit Entegrasyonu:** Her servisin `composer.json` dosyasına `phpunit/phpunit` geliştirme bağımlılığı olarak eklenmiş ve testlerin çalıştırılmasını yöneten bir `phpunit.xml.dist` dosyası yapılandırılmıştır.
-   **İzole Test Veritabanı:** Entegrasyon testlerinin canlı veriyi etkilemesini önlemek için, testler çalıştırılmadan önce `in-memory` bir SQLite veritabanı oluşturulur. İlgili servisin `schema_*.sql` dosyası bu geçici veritabanına uygulanarak, her testin temiz ve öngörülebilir bir durumda başlaması sağlanır.

### 2. Test Çeşitleri ve Stratejileri
-   **Birim Testi (Unit Test):** Dış bağımlılıklar olmadan, tek bir sınıfın veya metodun iş mantığını izole bir şekilde test eder. Bu testler çok hızlıdır ve kodun temel doğruluğunu garanti eder.
    -   *Örnek:* `AOMHesaplamaTest.php`, `Envanter-Servisi`'ndeki karmaşık Ağırlıklı Ortalama Maliyet formülünün matematiksel olarak doğru sonuçlar verdiğini doğrular.
-   **Entegrasyon Testi (Integration Test):** Birden fazla bileşenin (örn: API endpoint'i -> Controller -> Service -> Veritabanı) birlikte uyum içinde çalıştığını test eder. Bu testler, sistemin gerçek dünya senaryolarına daha yakın davranışını doğrular.
    -   *Örnek:* `SiparisDurumGuncellemeTest.php`, bir API isteğinin sadece sipariş durumunu güncellemekle kalmayıp, aynı zamanda `siparis_gecmisi_loglari` tablosuna doğru denetim kaydını da attığını birlikte doğrular.

---

## Test Edilen Kritik Akışlar (Örnekler)

v8.0 ile platformun en hayati iş akışları için otomatik testler oluşturulmuştur:

### 1. Kimlik Doğrulama (Auth-Servisi)
-   **Test:** `LoginApiTest.php`
-   **Akış:** Sahte bir kullanıcı test veritabanına eklenir. API'ye yanlış parola ile yapılan isteğin `401 Unauthorized` hatası, doğru parola ile yapılan isteğin ise `200 OK` ve bir JWT token'ı içeren yanıt döndürdüğü doğrulanır.

### 2. Sipariş Durum Güncelleme ve Denetim Kaydı (Siparis-Servisi)
-   **Test:** `SiparisDurumGuncellemeTest.php`
-   **Akış:** Bir API isteği ile sipariş durumu güncellendiğinde, hem `siparisler` tablosundaki durumun değiştiği hem de `siparis_gecmisi_loglari` tablosuna bu değişikliği yapan kullanıcıyı içeren bir denetim kaydının eklendiği doğrulanır.

### 3. Asenkron Stok Güncelleme (Tedarik-Servisi -> RabbitMQ -> Envanter-Servisi)
-   **Test:** `TedarikMalKabulEnvanterGuncellemeTest.php`
-   **Akış:** Bu uçtan uca test, mimarinin en karmaşık akışını doğrular:
    1.  `Tedarik-Servisi`'nin `teslimAl` API'si çağrılır.
    2.  `EventBusService`'in, doğru `tedarik.mal_kabul_yapildi` olayını yayınladığı bir "mock" nesne ile doğrulanır.
    3.  Yayınlanan olayın içeriği manuel olarak `Envanter-Servisi`'nin ilgili metoduna verilir.
    4.  Sonuç olarak `Envanter-Servisi`'nin veritabanındaki `depo_stoklari` tablosunda stok adetinin doğru şekilde arttığı doğrulanır.

---

## API Endpoint'leri (v8.0)

Bu sürümde yeni bir API endpoint'i eklenmemiştir. Bu sürümün odak noktası, mevcut API'lerin ve iş mantığının kalitesini ve güvenilirliğini otomatik testler ile artırmaktır.
