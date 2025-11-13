# FulcrumOS API v9.3 - Paketleme (Faz 3: Kurulum Sihirbazı ve Proje Tamamlama)

Bu sürüm, FulcrumOS platformunun v1.0'dan beri süregelen geliştirme yol haritasının planlanmış son adımıdır. v9.3 ile birlikte, projenin "Paketleme ve Dağıtım" (v9.x) fazı tamamlanmış ve platform, teknik olarak nihai ve bütüncül bir yapıya kavuşmuştur.

## v9.3 Yenilikleri

Bu sürümle birlikte "Paketleme" fazı tamamlanmıştır. v9.2'de sunulan Docker tabanlı kurulum yöntemine ek olarak, Docker kullanmayan veya kullanamayan "On-Premise" müşteriler için `install.php` (Kurulum Sihirbazı) eklenmiştir. Bu sihirbaz, müşterilerin teknik bilgiye ihtiyaç duymadan, tarayıcı üzerinden tüm platformu adım adım kurmasını sağlar.

## PROJE TAMAMLANDI NOTU (v9.3 - FİNAL)

**v9.3 ile FulcrumOS platformunun 'Paketleme ve Dağıtım' (v9.x) fazı tamamlanmıştır. Platform artık 'İşlevsel' (v7.x), 'Sağlam' (v8.x - Otomatik Testler) ve 'Paketlenmiş' (v9.x - Docker & Kurulum Sihirbazı) durumdadır. v1.0'da başlayan proje yolculuğu, bu sürümle birlikte teknik olarak tamamlanmıştır.**

Bu, FulcrumOS'un mimari vizyonunun hayata geçirildiği ve projenin "üretim-hazır" (production-ready) bir dağıtım paketine dönüştüğü anlamına gelmektedir.

## Kurulum Talimatları (Güncellendi)

Platformu kurmak için artık iki farklı yol bulunmaktadır. Lütfen ihtiyacınıza uygun olan yöntemi seçin:

### Yöntem 1: Docker ile Kurulum (Hızlı Başlangıç / Geliştiriciler için - v9.2)

Bu yöntem, tüm platformu (19 servis) tek bir komutla, izole konteynerler içinde başlatır. Geliştirme, test ve modern SaaS dağıtımları için tavsiye edilen en hızlı yöntemdir.

1.  Makinenizde **Docker Desktop**'ın kurulu olduğundan emin olun.
2.  `.env.example` dosyasını `.env` olarak kopyalayın: `cp .env.example .env`
3.  Platformu başlatın: `docker-compose up -d --build`

### Yöntem 2: Kurulum Sihirbazı (On-Premise / Geleneksel Kurulum - v9.3)

Bu yöntem, Docker kullanmayan veya paylaşımlı hosting (cPanel/Plesk) gibi ortamlarda kurulum yapmak isteyen kurumsal müşteriler için tasarlanmıştır.

1.  Proje dosyalarını sunucunuza yükleyin.
2.  Sunucunuzda boş bir veritabanı oluşturun ve veritabanı kullanıcı bilgilerinizi (host, kullanıcı adı, şifre) not alın.
3.  `setup/sql/` dizininin ve içeriğinin sunucu tarafından okunabilir olduğundan emin olun.
4.  Tarayıcınızda `http://siteniz.com/install.php` adresini açın.
5.  Formdaki adımları (sunucu kontrolü, veritabanı bilgileri, admin kullanıcısı) takip ederek kurulumu tamamlayın. Sihirbaz, kurulum bittiğinde kendini otomatik olarak kilitleyecektir.

## API Endpoint'leri (v9.3)

Bu sürümde yeni bir API endpoint'i eklenmemiştir. Değişiklik, sadece projenin kök dizinine eklenen `install.php` kurulum betiğinden ibarettir.
