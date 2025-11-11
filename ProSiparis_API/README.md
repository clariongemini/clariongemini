# ProSiparis API v7.0 - Yönetim Paneli (Faz 1: Temel Kurulum)

## v7.0 Yenilikleri

Bu sürüm, ProSiparis platformunda tamamen yeni bir fazı başlatmaktadır: **Frontend (Kokpit) Fazı**. v6.1 ile mimari olarak tamamlanan "Headless" Backend API motorunu (Motor) yönetecek olan modern, mobil uyumlu ve tema destekli **Yönetim Paneli (Admin UI)** projesinin temelleri bu sürümle atılmıştır. Artık platform, sadece bir API değil, aynı zamanda o API'yi yönetmek için bir arayüze de sahiptir.

## Mimari Konseptler (v7.0 Güncellemeleri)

### Yeni Proje: Yönetim Paneli (`admin-ui`)

-   **Konum:** `servisler/admin-ui/`
-   Bu, Backend servislerinden tamamen ayrı, kendi yaşam döngüsüne sahip bir Frontend projesidir.

### Kullanılan Ana Teknolojiler

-   **React & Vite:** Proje, modern ve hızlı bir geliştirme deneyimi sunan Vite altyapısı üzerinde React ile geliştirilmiştir.
-   **MUI (Material-UI):** Tüm arayüz bileşenleri, mobil uyumluluk (responsive design) ve esneklik için Google'ın Material Design prensiplerini uygulayan MUI kütüphanesi ile oluşturulmuştur.
-   **React Router:** Tek sayfa uygulaması (SPA) içindeki sayfa yönlendirmeleri için kullanılmıştır.
-   **Axios:** Backend API'si ile güvenli ve merkezi bir iletişim kurmak için kullanılmıştır.

### Tema Yönetimi (Açık / Koyu / Sistem)

-   Panel, kullanıcıların göz zevkine ve çalışma ortamlarına uyum sağlamak için **Açık Tema**, **Koyu Tema** ve **Sistem Varsayılanı** seçeneklerini destekler.
-   Bu özellik, MUI'nin `ThemeProvider`'ı ve React Context API'si üzerine kurulu merkezi bir tema yönetim sistemi ile sağlanmıştır. Kullanıcının tercihi, tarayıcının `localStorage`'ında saklanır.

## Servisler Arası İletişim Akışı

### Admin UI (React) <-> Gateway-Servisi (API) İletişimi

1.  **Giriş (Login):**
    -   Kullanıcı, `admin-ui`'nin `/login` sayfasında e-posta ve parolasını girer.
    -   React uygulaması, `axios` istemcisi aracılığıyla Backend'in `POST /api/kullanici/giris` endpoint'ine bir istek gönderir.
    -   Gateway-Servisi, bu isteği `Auth-Servisi`'ne yönlendirir.
    -   `Auth-Servisi` kimlik bilgilerini doğrular ve bir JWT token üretip yanıt olarak döner.
    -   React uygulaması, aldığı JWT token'ını tarayıcının `localStorage`'ına kaydeder.

2.  **Yetkili İstekler (Authenticated Requests):**
    -   Kullanıcı, `/dashboard` gibi korunmuş bir sayfaya gittiğinde, React uygulaması `axios` istemcisi ile API'ye yeni bir istek yapar.
    -   `axios` istemcisi, her istek gönderilmeden önce `localStorage`'dan JWT token'ını okur ve isteğin `Authorization: Bearer <token>` başlığına otomatik olarak ekler.
    -   Gateway-Servisi, bu başlığı görür, `Auth-Servisi`'ne doğrulatarak kullanıcıya erişim izni verir ve isteği ilgili servise (örn: `Raporlama-Servisi`) yönlendirir.

### Dashboard Sayfasının Veri Çekme Akışı

-   Kullanıcı `/dashboard` sayfasını açar.
-   React (`DashboardPage` bileşeni), `useEffect` hook'u içinde `axios` istemcisini kullanarak `GET /api/admin/dashboard/kpi-ozet` endpoint'ine bir istek atar.
-   Gateway-Servisi, bu isteği `Raporlama-Servisi`'ne yönlendirir.
-   `Raporlama-Servisi` veritabanından KPI verilerini toplar ve JSON olarak yanıt döner.
-   React uygulaması, gelen veriyi `useState` hook'u ile saklar ve MUI `Card` bileşenleri içinde ekranda gösterir.

## API Endpoint'leri (v7.0)

-   Bu sürümde yeni bir Backend API endpoint'i **eklenmemiştir**.
-   Yönetim Paneli, v6.1 ve önceki sürümlerde geliştirilmiş olan mevcut `Auth-Servisi` ve `Raporlama-Servisi` endpoint'lerini kullanmaktadır.

---

# ProSiparis API v7.4 - Merkezi Medya Servisi ve Dosya Yükleme

Bu sürüm, platformun en kritik işlevsel eksikliklerinden birini giderir: **Dosya Yükleme**. Bu amaçla, tüm medya varlıklarını yönetecek merkezi bir **Medya-Servisi** kurulmuş ve mevcut modüller (Ürün, Banner) bu servisi kullanacak şekilde refaktör edilmiştir.

## v7.4 Yenilikleri

- **Yeni Servis:** Platform geneli bir ihtiyaç olan dosya yönetimini ele almak için `medya-servisi` kuruldu.
- **Dosya Yükleme API'si:** Yeni servis, `multipart/form-data` kabul eden güvenli bir `POST /api/admin/medya/yukle` endpoint'i sunar.
- **Medya Kütüphanesi:** Admin paneline, yüklenmiş tüm medyaların yönetildiği bir galeri sayfası eklendi.
- **Modül Refaktörü:** Ürün ve Banner modüllerindeki "manuel URL girişi" alanları, Medya Kütüphanesi'nden seçim yapmayı sağlayan modern bir arayüzle değiştirildi.
---

# ProSiparis API v7.3 - Yönetim Paneli (Faz 4: Depo Yönetimi)

Bu sürüm, platformun WMS (Çoklu Depo Yönetim Sistemi) altyapısının temelini oluşturan **Depo Yönetimi** modülünü panele ekler. Bu, hem backend (Organizasyon-Servisi) hem de frontend (Admin UI) katmanlarında geliştirmeler içeren bir "tam yığın" güncellemedir.

## v7.3 Yenilikleri

- **Yeni Modül (CRUD):** Yönetim Paneli'ne, depoları yönetmek için tam bir CRUD arayüzü eklendi.
- **Yeni Backend API'leri:** Organizasyon-Servisi'ne, depoları yönetmek için `/api/admin/organizasyon/depolar` altında yeni, yetki korumalı (ACL) CRUD endpoint'leri eklendi.
- **Yeni Yetki:** Auth-Servisi'ne, bu yeni endpoint'leri korumak için `depo_yonet` yetkisi eklendi.
---

# ProSiparis API v7.2 - Yönetim Paneli (Faz 3: CMS Yönetimi)

Bu sürüm, Yönetim Paneli'nin işlevselliğini CMS-Servisi'ne bağlanarak genişletir ve iki yeni modül ekler: **Sayfa Yönetimi** ve **Banner Yönetimi**.

## v7.2 Yenilikleri

- **Sayfa Yönetimi (CRUD):** "Hakkımızda", "İletişim" gibi statik sayfaları yönetmek için tam bir CRUD arayüzü eklendi.
- **Zengin Metin Editörü (WYSIWYG):** Sayfa içeriği oluşturmak için `react-quill` kütüphanesi ile bir zengin metin editörü entegre edildi.
- **Banner Yönetimi (CRUD):** Sitede gösterilecek görsel banner'ları yönetmek için bir CRUD arayüzü eklendi.
- **Kullanılan API'ler:** Bu geliştirme, CMS-Servisi'nin mevcut `/api/admin/sayfalar` ve `/api/admin/bannerlar` endpoint'lerini kullanmıştır.
---

# ProSiparis API v7.1 - Yönetim Paneli (Faz 2: Ürün Yönetimi)

Bu sürüm, ProSiparis Yönetim Paneli'ne (Admin UI) ilk ve en kritik "yazma" (CRUD - Create, Read, Update, Delete) yeteneğini kazandırır. Artık yöneticiler, doğrudan kullanıcı arayüzü üzerinden Katalog-Servisi'ndeki ürünleri listeleyebilir, oluşturabilir, güncelleyebilir ve silebilir.

## v7.1 Yenilikleri

- **İlk CRUD Modülü:** Yönetim Paneli'ne, platformun ticari kalbi olan **Ürün Yönetimi** modülü eklendi. Bu, paneli salt okunur bir "gösterge paneli" olmaktan çıkarıp, gerçek bir yönetim aracına dönüştüren ilk adımdır.
- **Gelişmiş Veri Tablosu:** Ürünleri listelemek için güçlü ve esnek bir bileşen olan **MUI DataGrid** entegre edildi.
- **Dinamik Form Yönetimi:** Hem "Ürün Oluşturma" hem de "Ürün Düzenleme" işlemlerini yöneten dinamik bir form sayfası oluşturuldu.
- **Kullanılan API'ler:** Bu geliştirme, Katalog-Servisi'nin mevcut `/api/admin/urunler` CRUD endpoint'lerini kullanmıştır. Yeni bir backend endpoint'i oluşturulmamıştır.
