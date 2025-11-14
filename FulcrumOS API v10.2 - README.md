# FulcrumOS API v10.2 - Fatura PDF Oluşturma

Bu sürüm, platformun en kritik ticari işlevlerinden birini ekleyerek v10.x "İyileştirme ve Eklentiler" fazında önemli bir adım atmaktadır. Bu "tam yığın" (full-stack) geliştirme, platformu operasyonel olarak çok daha yetenekli hale getirmektedir.

## v10.2 Yenilikleri

Platforma, her sipariş için dinamik **'Fatura PDF' oluşturma yeteneği** eklenmiştir. Bu işlevsellik, `Siparis-Servisi`'nin `dompdf` kullanarak PDF üretmesi ve `Organizasyon-Servisi`'nin merkezi fatura ayarlarını saklaması ile sağlanmıştır. `Admin-UI`'ye eklenen yeni ayar sayfaları ve sipariş yönetimi arayüzündeki güncellemelerle bu özellik tamamlanmıştır.

## Mimari Konseptler (v10.2 Güncellemeleri)

- **Fatura İş Akışı:** Dinamik bir faturanın oluşturulması, birden fazla servis ve bileşen arasında koordineli bir veri akışı gerektirir:
    1.  **Admin-UI (Ayar & Talep):** Yönetici, ilk olarak `/admin/ayarlar/fatura` sayfasından (react-hook-form kullanılarak) şirketin fatura bilgilerini (Firma Unvanı, Vergi No vb.) girer. Daha sonra, bir sipariş detay sayfasında "Fatura PDF İndir" butonuna tıklar.
    2.  **Siparis-Servisi (PDF Üretici):** `GET .../:siparisId/fatura-pdf` endpoint'i, ana orkestratör olarak çalışır. Önce kendi veritabanından sipariş detaylarını alır.
    3.  **Organizasyon-Servisi (Ayar Alıcı):** `Siparis-Servisi`, faturayı kesen firma bilgilerini almak için `Organizasyon-Servisi`'ne anlık ve güvenli bir iç API çağrısı (`/internal/.../fatura`) yapar.
    4.  **HTML Şablonu & `dompdf` (Dönüştürücü):** `Siparis-Servisi`, bu iki veri setini (sipariş + firma) kendi içinde bulunan (`templates/fatura_template.html`) bir HTML şablonuna basar. Son olarak, `dompdf` kütüphanesi bu zenginleştirilmiş HTML'i bir PDF dosyasına dönüştürür ve kullanıcıya gönderir.

- **Rol Bazlı Arayüz (Koşullu Görüntüleme):** `admin-ui`'deki "Fatura PDF İndir" butonu, herkes tarafından görülemez. Butonun görüntülenmesi, kullanıcının `AuthContext`'ten gelen yetkileri arasında `fatura_goruntule` izninin olup olmamasına bağlıdır. Bu, frontend (UI) ve backend (API) güvenlik katmanlarının tutarlı bir şekilde çalışmasını sağlar.

## Servisler Arası Yeni İletişim Akışları

- **`Siparis-Servisi` -> `Organizasyon-Servisi`:** Bu sürümle birlikte, fatura verilerinin anlık tutarlılığını sağlamak için yeni, senkron ve iç ağa özel bir iletişim kanalı kurulmuştur.
    - `siparis-servisi`, bir fatura PDF'i oluşturma talebi aldığında, faturayı kesen firma bilgilerini almak için `organizasyon-servisi`'ne anlık bir `GET http://organizasyon-servisi/internal/organizasyon/ayarlar/fatura` çağrısı yapar.

## API Endpoint'leri (v10.2)

### YENİ Backend API Endpoint'leri
- **Servis:** `organizasyon-servisi`
    - `GET /api/admin/organizasyon/ayarlar/fatura`: Fatura ayarlarını listeler.
    - `PUT /api/admin/organizasyon/ayarlar/fatura`: Fatura ayarlarını günceller.
- **Servis:** `siparis-servisi`
    - `GET /api/admin/siparisler/:siparisId/fatura-pdf`: Belirtilen sipariş için dinamik olarak bir Fatura PDF'si oluşturur ve indirilebilir bir dosya olarak döndürür.

### YENİ Yetki
- **Servis:** `auth-servisi`
    - `fatura_goruntule`: Kullanıcıların fatura PDF'lerini görüntüleme ve indirme yetkisini tanımlar.
