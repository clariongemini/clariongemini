# ProSiparis API v3.0 - Mikroservis Mimarisine Geçiş Faz-1

Bu proje, ProSiparis e-ticaret platformunun, monolitik bir yapıdan dağıtık bir mikroservis mimarisine geçişinin ilk ve temel fazını temsil etmektedir. v2.x sürümlerinde geliştirilen zengin iş mantığı korunmuş, ancak gelecekteki büyüme, performans ve dayanıklılık hedefleri için stratejik olarak yeniden yapılandırılmıştır.

**v3.0'ın Ana Hedefleri:**
-   **Dayanıklılık (Resilience):** Servislerden birinin (örn: Ödeme) çökmesi durumunda, diğer servislerin (örn: Sipariş) çalışmaya devam etmesini sağlamak.
-   **Ölçeklenebilirlik (Scalability):** Yoğun trafik alan servislerin (örn: Katalog) bağımsız olarak ölçeklendirilebilmesine olanak tanımak.
-   **Teknoloji Çeşitliliği:** Her servisin, işin gerektirdiği en uygun teknolojiyle (Go, Python, Node.js vb.) geliştirilebilmesinin önünü açmak.

---

## Mimari Konseptler (v3.0)

ProSiparis API artık tek bir uygulama değil, birbiriyle haberleşen mantıksal servisler topluluğudur.

### 1. API Gateway Simülasyonu (Merkezi Giriş Noktası)
Tüm dış dünya istekleri (`/api/...`), artık ana projenin `public/index.php` dosyası tarafından bir "API Gateway" gibi karşılanır.
-   **Görevi:** Gelen isteğin yoluna (`/api/urunler`) bakarak, isteği ilgili iç servise (`katalog-servisi`) yönlendirir.
-   **Merkezi Kimlik Doğrulama:** JWT, bu Gateway katmanında doğrulanır ve `X-User-ID`, `X-Permissions` gibi güvenli HTTP başlıkları (headers) ile iç servislere iletilir. İç servisler, gelen isteğin zaten doğrulanmış olduğunu varsayar.

### 2. Mantıksal Servis Ayrımı
Monolith, en kritik ve birbirinden ayrıştırılabilir üç parçaya bölünmüştür:
-   `servisler/auth-servisi/`: Kullanıcı kaydı, giriş ve ACL (Rol/Yetki) yönetiminden sorumludur.
-   `servisler/katalog-servisi/`: Ürün, kategori ve fiyat listelerinin "okunmasından" (READ) sorumludur.
-   `servisler/envanter-servisi/`: Tüm envanter hareketlerinin (Ledger) ve maliyet hesaplamalarının (AOM) "tekil doğruluk kaynağıdır" (Single Source of Truth).

### 3. Ana Monolith (Legacy Core)
`ProSiparis_API/` klasörü, henüz ayrıştırılmamış olan Sipariş, İade (RMA), Tedarik Zinciri (PO), CMS gibi "ağır" iş mantığını yönetmeye devam eden ana servis görevi görür.

### 4. Veritabanı Ayrıştırma Stratejisi
Her servis, kendi veritabanına sahip olma prensibine uygun olarak, kendi `schema_*.sql` dosyasına sahiptir. Bu, gelecekte her servisin kendi veritabanı sunucusunu kullanabilmesinin önünü açar.

### 5. Event Bus Simülasyonu (Asenkron İletişim)
Servisler arası sıkı bağımlılığı (tight coupling) azaltmak için, veritabanı tabanlı basit bir "Event Bus" (`olay_gunlugu` tablosu) kurulmuştur.
-   **Senaryo:** `EnvanterService`, bir ürünün stoğunu güncellediğinde, `KatalogService`'e doğrudan "Stoku güncelle!" demez. Bunun yerine, "Ben stoğu güncelledim" (`stok_guncellendi`) diye bir olay yayınlar.
-   `KatalogService`, bu olayları periyodik olarak dinler ve kendi veritabanındaki stok bilgisini bu olaya göre günceller. Bu, Envanter-Servisi çökse bile Katalog-Servisi'nin çalışmaya devam etmesini sağlar.

---

## Kurulum ve Çalıştırma

Bu dağıtık yapıyı yerel ortamda çalıştırmak için:
1.  **Veritabanlarını Oluşturma:** Her servis klasöründeki (`servisler/*/`) ve ana `ProSiparis_API/` klasöründeki `schema_*.sql` dosyalarını, ideal olarak ayrı veritabanlarına (veya aynı veritabanında farklı öneklerle) içe aktarın.
2.  **Yapılandırma:** `ProSiparis_API/config/` altındaki dosyaları, tüm bu veritabanlarına erişecek şekilde düzenleyin.
3.  **Web Sunucusu:** Sunucunuzun "Document Root" olarak ana projenin `ProSiparis_API/public/` klasörünü göstermesi yeterlidir. API Gateway, istekleri doğru iç servise yönlendirecektir.
