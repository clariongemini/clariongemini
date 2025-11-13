# FulcrumOS API v9.2 - Paketleme (Faz 2: Docker & Üretim Ortamı)

Bu sürüm, FulcrumOS platformunun "Paketleme ve Dağıtım" fazının en kritik adımını temsil etmektedir. v9.2 ile birlikte, platformun tüm karmaşıklığı soyutlanarak, "tek komutla kurulum" (single-command setup) yeteneği kazandırılmıştır. Artık 19 bileşenin (altyapı, backend, frontend) tamamı, Docker ve Docker Compose kullanılarak üretime hazır (production-ready) bir paket haline getirilmiştir.

## v9.2 Yenilikleri: Tek Komutla Kurulum

Platformun kurulumu ve yerel geliştirme ortamının ayağa kaldırılması süreci radikal bir şekilde basitleştirilmiştir. Daha önce 10'dan fazla servisin manuel olarak başlatılmasını gerektiren süreç, artık `docker-compose up` komutu ile saniyeler içinde tamamlanabilmektedir. Bu yenilik, geliştirici katılımını (developer onboarding) hızlandırır ve farklı ortamlarda (geliştirme, test, üretim) tutarlı bir yapı sağlar.

## Mimari Konseptler (v9.2 Güncellemeleri)

- **Docker Konteyner Mimarisi:** Platformdaki her bir servis (örneğin `siparis-servisi`, `admin-ui`) artık kendi izole konteyneri içinde çalışmaktadır. Bu mimari, servislerin birbirlerinin bağımlılıklarından etkilenmesini engeller, güvenliği artırır ve her servisin kendi kaynaklarını verimli bir şekilde kullanmasını sağlar.

- **Service Discovery (Servis Keşfi):** Konteynerler, `fulcrumos-net` adında ortak bir sanal ağda çalışır. Bu sayede servisler birbirleriyle `localhost` veya IP adresi yerine, `docker-compose.yml` içinde tanımlanan servis isimleriyle (hostname) haberleşir. Örneğin, `gateway-servisi` artık `siparis-servisi`'ne `http://siparis-servisi` adresi üzerinden güvenli ve tutarlı bir şekilde erişebilir.

- **Otomatik Veritabanı Kurulumu:** `mysql` servisi, ilk başlangıcında `./demo.sql` dosyasını otomatik olarak veritabanına yükleyecek şekilde yapılandırılmıştır. Bu sayede platform, test ve demo için gerekli tüm verilerle birlikte "kutudan çıktığı gibi" çalışır hale gelir.

## Kurulum ve Çalıştırma Talimatı (The Golden Path)

FulcrumOS platformunu yerel makinenizde çalıştırmak için aşağıdaki adımları izleyin:

1.  **Docker ve Docker Compose'u Yükleyin:**
    Makinenizde [Docker Desktop](https://www.docker.com/products/docker-desktop/)'ın kurulu olduğundan emin olun.

2.  **Çevre Değişkenlerini Yapılandırın:**
    Proje kök dizinindeki `.env.example` dosyasını kopyalayarak `.env` adında yeni bir dosya oluşturun. Gerekirse portları veya şifreleri bu dosyada düzenleyebilirsiniz.
    ```bash
    cp .env.example .env
    ```

3.  **Platformu Başlatın:**
    Aşağıdaki komut ile tüm FulcrumOS platformunu (19 servis) arka planda başlatın:
    ```bash
    docker-compose up -d --build
    ```

4.  **Platformu Durdurmak İçin:**
    Tüm konteynerleri durdurmak için aşağıdaki komutu kullanın:
    ```bash
    docker-compose down
    ```

## Servis Listesi ve Portlar

Platform başlatıldığında, aşağıdaki servisler belirtilen portlardan dış dünyaya açılacaktır:

| Servis Adı      | Açıklama                      | Port (localhost) |
|-----------------|-------------------------------|------------------|
| **Admin UI**    | React Yönetim Paneli          | `3000`           |
| **Gateway API** | Ana API Giriş Kapısı          | `8000`           |
| **RabbitMQ UI** | Message Broker Yönetim Paneli | `15672`          |
| **MySQL DB**    | Veritabanı Sunucusu           | `3306`           |
