# ProSiparis API v7.8 - Yönetim Paneli (Faz 8: Destek Talepleri ve Faz Tamamlama)

Bu sürüm, ProSiparis API projesinin v7.x "Frontend Fazı"nı tamamlayan son ve nihai adımdır. v7.8 ile, platformun son operasyonel mikroservisi olan `Destek-Servisi`'nin de yönetim arayüzü `admin-ui`'ye eklenmiş ve böylece "Kokpit" projesi vizyonu tamamlanmıştır.

## v7.8 Yenilikleri:

Yönetim Paneli'ne (`admin-ui`), `Destek-Servisi`'ne bağlanan "Destek Talepleri (Ticketing)" modülü eklenmiştir. Bu arayüz, yöneticilerin müşteri destek taleplerini modern bir mesajlaşma arayüzü üzerinden verimli bir şekilde yönetmesini sağlar.

---

## PROJE TAMAMLANDI NOTU:

**v7.8 ile `admin-ui` ("Frontend Fazı") için planlanan tüm ana modüller (Ürün, CMS, Depo, Medya, Sipariş, İade, Tedarik, Destek) tamamlanmıştır. "Kokpit" (Admin UI) artık platformun tüm ana backend servislerini yönetebilmektedir.**

Bu sürüm, v1.0'da başlayan monolitik bir yapıdan, v7.8'de tam donanımlı, yönetilebilir ve "headless" bir mikroservis mimarisine dönüşen bu uzun ve kapsamlı projenin sonunu işaret etmektedir.

---

## API Endpoint'leri (v7.8)

### Yeni Backend Servisi: `Destek-Servisi`
-   `GET /api/admin/destek-talepleri`: Tüm destek taleplerini (duruma göre filtrelenebilir) listeler.
-   `GET /api/admin/destek-talepleri/:talepId`: Tek bir talebin tüm mesajlaşma geçmişini getirir.
-   `POST /api/admin/destek-talepleri/:talepId/mesaj`: Bir talebe yönetici olarak yanıt verir.
-   `PUT /api/admin/destek-talepleri/:talepId/durum`: Bir talebin durumunu günceller (örn: 'kapandi').

### Güncellenen Backend Servisi: `Auth-Servisi`
-   **YENİ Yetki:** `destek_yonet`.
