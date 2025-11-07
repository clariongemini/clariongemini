# ProSiparis API v2.5 - Profesyonel E-Ticaret API

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş, modern PHP standartlarına uygun, güvenli ve tam teşekküllü bir e-ticaret backend (API) sunucusudur.

**v2.5 Yenilikleri:** Bu sürüm, API'nin temel güvenlik altyapısını modernize ederek Yetki Bazlı Erişim Kontrolü (ACL) sistemine geçiş yapmış, yöneticiler için güçlü bir analitik API'si eklemiş ve kullanıcılara kişiselleştirilmiş ürün önerileri sunmaya başlamıştır.

---

## Kurulum

1.  **Gerekli Araçlar:** PHP >= 7.4, Composer, MySQL, Apache (veya `.htaccess` destekli başka bir web sunucusu).
2.  **Projeyi Kurma:** Projenin ana dizininde `composer install` komutunu çalıştırın.
3.  **Veritabanı Oluşturma:** `schema.sql` dosyasını (**v2.5**) veritabanınıza içe aktarın. Bu şema, yeni ACL tablolarını ve başlangıç verilerini (roller, yetkiler) içerir.
4.  **Yapılandırma:** Gerekli veritabanı, JWT, SMTP ve Iyzico ayarlarını `config/` klasöründeki dosyalarda düzenleyin.
5.  **Web Sunucusu:** Sunucunuzun "Document Root" olarak projenin `public/` klasörünü göstermesi tavsiye edilir.

---

## Temel Konseptler: Yetki Bazlı Erişim Kontrolü (ACL)

v2.5 ile birlikte sistem, basit 'admin' ve 'kullanici' rollerinden, granüler **yetki bazlı** bir sisteme geçmiştir.

-   **Roller:** Kullanıcılara atanan etiketlerdir (örn: `super_admin`, `magaza_yoneticisi`, `kullanici`).
-   **Yetkiler:** Belirli bir eylemi gerçekleştirme hakkıdır (örn: `urun_yarat`, `siparis_durum_guncelle`, `dashboard_goruntule`).
-   **İşleyiş:** Bir kullanıcı giriş yaptığında, rolüne atanmış tüm yetkiler listesi JWT'nin içine gömülür. API'de yetki korumalı bir endpoint çağrıldığında, `PermissionMiddleware` bu JWT içindeki listeyi kontrol eder. Bu sayede her istekte veritabanı sorgusu yapılmasına gerek kalmaz.

---

## Versiyon Geçmişi

### v2.5: ACL, Admin Dashboard ve Kişiselleştirme Motoru
-   **İleri Düzey Yetkilendirme (ACL):** Rol tabanlı sistemden yetki tabanlı sisteme geçiş yapıldı.
-   **Admin Dashboard API'si:** Yöneticiler için KPI, satış grafiği gibi kritik verileri sunan endpoint'ler eklendi.
-   **Kişiselleştirme Motoru:** Kullanıcının geçmiş etkileşimlerine göre ürün önerileri sunan bir API eklendi.

### v2.4: Sosyal Kanıt ve Kullanıcı Etkileşimi
-   **Ürün Değerlendirme/Puanlama ve Favori/İstek Listesi özellikleri eklendi.**

(Önceki versiyonlar için projenin git geçmişine bakınız.)

---

## API Endpoint'leri (v2.5)

### Admin Korumalı Endpoint'ler (YENİ & GÜNCELLENMİŞ)
_Tüm admin endpoint'leri artık `AuthMiddleware` ve `PermissionMiddleware` tarafından korunmaktadır. Gerekli yetki JWT içinde bulunmalıdır._

#### **Admin Dashboard API (YENİ)**
-   **Yetki:** `dashboard_goruntule`
-   **`GET /api/admin/dashboard/kpi-ozet`**: Temel iş metriklerini (bugünkü satış, bekleyen sipariş vb.) döndürür.
-   **`GET /api/admin/dashboard/satis-grafigi`**: Son 30 günün satış verilerini grafik için döndürür.
-   **`GET /api/admin/dashboard/en-cok-satilan-urunler`**: En çok satan 10 ürünü listeler.
-   **`GET /api/admin/dashboard/son-faaliyetler`**: Son 5 sipariş ve son 5 yorumu listeler.

#### **Ürün & Kategori Yönetimi**
-   **`POST /api/admin/urunler`**: Yetki: `urun_yarat`
-   **`PUT /api/admin/urunler/{id}`**: Yetki: `urun_guncelle`
-   **`DELETE /api/admin/urunler/{id}`**: Yetki: `urun_sil`
-   (Kategori endpoint'leri de benzer şekilde `urun_yarat`, `urun_guncelle`, `urun_sil` yetkilerini kullanır.)

#### **Sipariş Yönetimi**
-   **`GET /api/admin/siparisler`**: Yetki: `siparis_listele`
-   **`PUT /api/admin/siparisler/{id}`**: Yetki: `siparis_durum_guncelle`

#### **Kupon Yönetimi**
-   **`GET /api/admin/kuponlar`**: Yetki: `kupon_listele`
-   **`POST /api/admin/kuponlar`**: Yetki: `kupon_yarat`
-   **`PUT /api/admin/kuponlar/{id}`**: Yetki: `kupon_guncelle`
-   **`DELETE /api/admin/kuponlar/{id}`**: Yetki: `kupon_sil`

#### **Değerlendirme Yönetimi**
-   **`DELETE /api/admin/degerlendirmeler/{id}`**: Yetki: `degerlendirme_sil`

### Kullanıcı Korumalı Endpoint'ler (`Authorization: Bearer <token>` Gerekli)

#### **Kişiselleştirme (YENİ)**
-   **`GET /api/kullanici/onerilen-urunler`**: Kullanıcının geçmiş etkileşimlerine (siparişler, favoriler) dayalı olarak kişiselleştirilmiş ürün önerileri sunar. Yeterli veri yoksa genel popüler ürünleri döndürür.

(Diğer kullanıcı ve herkese açık endpoint'ler v2.4 ile aynıdır.)
