# ProSiparis API v3.2 - Mikroservis Mimarisi (Faz-3): Tedarik ve İade Servisleri

Bu proje, ProSiparis e-ticaret platformunun dağıtık bir mimariye geçişinin üçüncü fazını temsil etmektedir. Bu sürümle birlikte, kritik operasyonel süreçler olan **Tedarik Zinciri (Satın Alma - PO)** ve **İade Yönetimi (RMA)**, Ana Monolith'ten tamamen ayrıştırılarak kendi veritabanlarına sahip, bağımsız ve dayanıklı iki yeni servis haline getirilmiştir.

**v3.2'nin Ana Hedefleri:**
-   **Monolith'in Rolünü Azaltma:** Ana Monolith'in son operasyonel sorumluluklarını da üzerinden alarak, onu sadece "Destekleyici Fonksiyonlar" (CMS, Kupon, Destek) yöneten bir servise dönüştürmek.
-   **Bağımsızlık ve Dayanıklılık:** Stok hareketlerini başlatan temel operasyonları (mal girişi ve iade girişi) kendi servislerine taşıyarak, bu süreçlerin platformun diğer bölümlerinden etkilenmeden, bağımsız olarak çalışmasını sağlamak.

---

## Mimari Konseptler (v3.2 Güncellemeleri)

### 1. Mantıksal Servis Ayrımı (Genişletildi)
Platform artık daha da granüler, daha odaklanmış servislerden oluşmaktadır:
-   `servisler/auth-servisi/` (v3.0)
-   `servisler/katalog-servisi/` (v3.0)
-   `servisler/envanter-servisi/` (v3.0)
-   `servisler/siparis-servisi/` (v3.1)
-   `servisler/bildirim-servisi/` (v3.1)
-   `servisler/tedarik-servisi/` **(YENİ):** Tedarikçiler, satın alma siparişleri (PO) ve depoya mal kabulü gibi tüm tedarik zinciri süreçlerinden sorumludur.
-   `servisler/iade-servisi/` **(YENİ):** Müşteri iade taleplerinin oluşturulması, depoya kabulü ve ödemelerin yapılması gibi tüm iade yönetimi (RMA) süreçlerinden sorumludur.

### 2. Ana Monolith (Legacy Core) (Nihai Rolü)
Ana Monolith'in rolü minimize edilmiştir. Artık sadece operasyonel olmayan, destekleyici modülleri yönetmektedir:
-   Kuponlar, CMS, Destek Talepleri, Pazarlama Otomasyonu ve Raporlama.

### 3. Servisler Arası İletişim (Yeni Olay Akışları)

#### **Asenkron İletişim (Event Bus üzerinden)**
Yeni servisler, tamamladıkları önemli iş adımlarını diğer servislerin dinlemesi için "olay" (event) olarak yayınlar.

-   **Yeni Akış 1 (Mal Kabulü Yapıldı):**
    1.  `Tedarik-Servisi`, depoya yeni ürünlerin teslimatı başarılı olduğunda `tedarik.mal_kabul_yapildi` olayını yayınlar.
    2.  `Envanter-Servisi`, bu olayı dinler, ilgili ürünlerin stoğunu artırır, Ağırlıklı Ortalama Maliyeti (AOM) yeniden hesaplar ve envanter hareketlerine (Ledger) 'satin_alma' kaydı atar.

-   **Yeni Akış 2 (İade Stoğa Alındı):**
    1.  `Iade-Servisi`, depoya gelen iade ürünlerini "Satılabilir" olarak işaretlediğinde `iade.stoga_geri_alindi` olayını yayınlar.
    2.  `Envanter-Servisi`, bu olayı dinler, ilgili ürünün stoğunu artırır ve Ledger'a 'iade_giris' kaydı atar.

-   **Yeni Akış 3 (İade Ödemesi Başarılı):**
    1.  `Iade-Servisi`, müşterinin iade ödemesini başarıyla tamamladığında `iade.odeme_basarili` olayını yayınlar.
    2.  `Bildirim-Servisi`, bu olayı dinler ve müşteriye "İade işleminiz tamamlandı" e-postasını gönderir.

#### **Senkron İletişim (Internal API Çağrıları üzerinden)**
Bir servisin, bir işlemi tamamlamak için başka bir servisten anlık olarak veri alması gerektiğinde kullanılır.
-   **Örnek Akış (İade Talebi Doğrulama):**
    1.  `Iade-Servisi`, bir müşteri iade talebi oluşturmak istediğinde, talebin geçerli olup olmadığını (örn: "Sipariş teslim edilmiş mi?") kontrol etmek zorundadır.
    2.  Bunun için, `Siparis-Servisi`'ne anlık, senkron bir internal API çağrısı (`GET /internal/siparisler/durum-kontrol?siparis_id=...`) yaparak siparişin durumunu doğrular.

---

## Kurulum ve Çalıştırma
API Gateway (`public/index.php`), tedarik ve iade ile ilgili tüm yeni API endpoint'lerine gelen istekleri otomatik olarak ilgili yeni servislere (`tedarik-servisi`, `iade-servisi`) yönlendirecek şekilde güncellenmiştir.
