# ProSiparis API v5.0 - Gerçek API Gateway Servisi'ne Geçiş

## v5.0 Yenilikleri

Bu sürüm, platformun en kritik altyapısal risklerinden birini çözmektedir. `public/index.php` dosyasında çalışan ve tek bir hata noktası (Single Point of Failure) oluşturan **API Gateway Simülasyonu** tamamen feshedilmiştir. Onun yerine, tüm dış trafiği karşılamak, merkezi kimlik doğrulaması yapmak ve istekleri ilgili mikroservislere yönlendirmek üzere tasarlanmış, yüksek performanslı ve bağımsız bir **Gateway-Servisi** devreye alınmıştır. Bu değişiklik, platformun giriş kapısını sağlamlaştırarak güvenilirliği ve ölçeklenebilirliği artırmaktadır.

## Mimari Konseptler (v5.0 Güncellemeleri)

### Yeni Servis: Gateway-Servisi

-   **Konum:** `servisler/gateway-servisi/`
-   **Teknoloji:** Bu servis, maksimum performans ve minimum gecikme için tasarlanmış, veritabanı bağlantısı olmayan, saf PHP tabanlı bir uygulamadır. Simüle edilmiş bir **Slim Framework** yapısı kullanır.
-   **Sorumlulukları:**
    1.  **Merkezi Yönlendirme (Routing):** Gelen tüm HTTP isteklerini karşılar ve URI'a göre hangi mikroservise gidileceğini belirler.
    2.  **Merkezi Kimlik Doğrulama (Authentication):** `api/kullanici/giris` gibi halka açık endpoint'ler dışındaki tüm istekler için `Authorization: Bearer <token>` başlığını kontrol eder. Token'ı `Auth-Servisi`'ne dahili bir istekle doğrulatarak merkezi bir güvenlik katmanı oluşturur.

## Servisler Arası İletişim Akışı

Dış dünyadan gelen bir isteğin platform içindeki yolculuğu artık aşağıdaki gibidir:

**Örnek İstek:** `GET /api/kullanici/profil`

1.  **Giriş Kapısı (Gateway-Servisi):**
    -   İstek, web sunucusu tarafından doğrudan `servisler/gateway-servisi/public/index.php` dosyasına yönlendirilir.
    -   **AuthMiddleware** devreye girer. `Authorization` başlığındaki JWT'yi alır.
    -   Gateway, `Auth-Servisi`'nin `/internal/auth/dogrula` endpoint'ine dahili bir HTTP isteği (cURL) göndererek token'ı doğrular.
    -   `Auth-Servisi` token'ı doğrular ve kullanıcı bilgilerini (`kullanici_id`, `rol`, `yetkiler`) Gateway'e geri döner.
    -   Gateway, bu bilgileri isteğe `X-User-ID`, `X-Role`, `X-Permissions` gibi güvenli HTTP başlıkları olarak ekler (simülasyonda `$_SERVER` değişkenleri kullanılır).

2.  **Yönlendirme (Gateway-Servisi):**
    -   Kimlik doğrulaması başarılı olduktan sonra, Gateway'in yönlendirme (routing) mantığı devreye girer.
    -   `/api/kullanici/profil` URI'ının `Auth-Servisi` tarafından yönetildiğini belirler.

3.  **Hedef Servis (Auth-Servisi):**
    -   Gateway, isteği `servisler/auth-servisi/public/index.php` dosyasını `require` ederek Auth-Servisi'ne devreder.
    -   `Auth-Servisi`, artık kimliği doğrulanmış ve yetkilendirilmiş olan bu isteği işler, `$_SERVER['HTTP_X_USER_ID']` üzerinden kullanıcı ID'sine erişir ve profil bilgilerini veritabanından alıp yanıt olarak döndürür.

## Kaldırılan Bileşenler

v5.0 yükseltmesiyle birlikte aşağıdaki eski bileşenler projeden tamamen kaldırılmıştır:

-   **`ProSiparis_API/public/index.php` (v4.3 Simülatörü):** Tüm yönlendirme ve karşılama mantığını yürüten eski API Gateway simülatörü.
-   **`ProSiparis_API/config/` Dizini:** Eski veritabanı bağlantıları ve ayar dosyalarını içeren klasör.

Projenin kök dizini artık kod içermeyen, sadece `servisler/` klasörünü ve genel proje dosyalarını barındıran bir "meta-proje" haline gelmiştir.
