# ProSiparis API v7.7 - Tedarik Servisi'nin (PO) Kurulması ve Arayüzü

Bu sürüm, ProSiparis WMS/ERP vizyonunun temel taşlarından birini yerine oturtarak "Envanter Yaşam Döngüsü"nü tamamlamaktadır. v7.7 ile, platformun ürünleri nasıl sattığı (Sipariş) ve geri aldığı (İade) gibi, bu ürünlerin depoya ilk nasıl girdiği (Tedarik) süreci de hem backend servisi hem de modern bir frontend arayüzü ile yönetilebilir hale gelmiştir.

## v7.7 Yenilikleri: Teknik Borcun Ödenmesi ve WMS'in Tamamlanması

-   **Teknik Borç Ödendi (v3.2):** Proje yol haritasında planlanmış ancak atlanmış olan bağımsız `Tedarik-Servisi`'nin kurulumu bu sürümle tamamlanmıştır. Bu, platformun mimari bütünlüğünü sağlamış ve "Satın Alma Siparişi" (Purchase Order - PO) mantığını kendi izole servisine taşımıştır.
-   **Envanter Yaşam Döngüsü Tamamlandı:** Bir ürünün tedarikçiden satın alınarak depoya kabul edilmesi (`Stok Girişi`), müşteriye satılması (`Stok Çıkışı`) ve iade olarak geri alınması (`Stok Girişi`) döngüsü, v7.7 ile artık baştan sona yönetilmektedir. Bu, WMS/ERP vizyonumuzun temelini oluşturur.
-   **Tam Yığın (Full-Stack) Modül:** Bu sürüm, hem sıfırdan bir backend mikroservisinin (Tedarik-Servisi) inşasını hem de bu servisi kullanan tam özellikli bir yönetim arayüzünün (Admin-UI) geliştirilmesini içermektedir.

---

## Mimari Konseptler (v7.7 Güncellemeleri)

### 1. Yeni Mikroservis: `servisler/tedarik-servisi/`
Platform mimarisine, satın alma ve mal kabul süreçlerinden sorumlu, tamamen bağımsız yeni bir servis eklenmiştir.
-   **Veritabanı Şeması (`schema_tedarik.sql`):**
    -   `tedarikciler`: Satın alım yapılan firmaların ana verilerini tutar.
    -   `tedarik_siparisleri`: Satın Alma Siparişlerinin (PO) başlık bilgilerini (tedarikçi, depo, durum) yönetir.
    -   `tedarik_siparis_urunleri`: Her bir PO'ya ait ürün satırlarını (sipariş edilen/teslim alınan adet, maliyet) yönetir.
    -   `tedarik_gecmisi_loglari`: PO üzerindeki tüm durum değişikliklerini ve mal kabul işlemlerini denetim kaydı olarak tutar.

### 2. ACL Yetkileri: Görev Ayrılığı Prensibi
`Auth-Servisi`'ne eklenen yeni yetkilerle, tedarik süreçlerindeki görevler net bir şekilde ayrılmıştır:
-   `tedarikci_yonet`: Yalnızca tedarikçi ana verilerini yönetme yetkisi.
-   `tedarik_yonet`: Satın Alma Siparişi oluşturma ve durumunu yönetme yetkisi.
-   `tedarik_teslim_al`: Bir PO'ya ait ürünleri fiziksel olarak depoya kabul etme (mal kabul) yetkisi. Bu yetki, `depo_gorevlisi` rolüne atanarak, finansal ve operasyonel görevlerin ayrılmasını sağlar.

### 3. Frontend Mimarisi: "Kısmi Teslim" (Partial Delivery)
Admin UI'ye eklenen "Tedarik Yönetimi" modülünün "Ürünleri Teslim Al" modalı, WMS'in temel bir gereksinimi olan kısmi teslimi destekler. Arayüz, kullanıcıya sipariş edilen, daha önce alınan ve kalan adetleri net bir şekilde gösterir. Ayrıca, kullanıcının kalan adetten daha fazla ürün teslim almasını engelleyen frontend doğrulamaları içerir.

---

## Servisler Arası Yeni İletişim Akışları

### 1. Senkron Akış: Admin UI -> Tedarik-Servisi
Kullanıcının Admin Panelinde yaptığı tüm tedarik yönetimi işlemleri, API Gateway üzerinden `Tedarik-Servisi`'ne yönlendirilen senkron API çağrıları ile gerçekleştirilir:
-   `GET /api/admin/tedarik/tedarikciler`
-   `POST /api/admin/tedarik/siparisler`
-   `POST /api/admin/tedarik/siparisler/:poId/teslim-al`

### 2. Asenkron Akış: Tedarik-Servisi -> RabbitMQ -> Envanter-Servisi
Bir mal kabul işlemi başarıyla tamamlandığında, envanterin güncellenmesi asenkron bir "zengin olay" (rich event) ile tetiklenir. Bu, sistemin dayanıklılığını artırır ve servisler arasındaki bağımlılığı azaltır.
-   **Olay:** `tedarik.mal_kabul_yapildi`
-   **Akış:**
    1.  **Publisher (`Tedarik-Servisi`):** Depo görevlisi bir ürünü teslim aldığında, `teslim-al` endpoint'i bu olayı RabbitMQ'ya yayınlar.
    2.  **Payload (Zengin Olay):** Olay, `Envanter-Servisi`'nin ihtiyaç duyacağı tüm bilgileri içerir: `po_id`, `depo_id`, `gelen_urunler` (içinde `varyant_id`, `gelen_adet` ve en önemlisi `maliyet_fiyati`).
    3.  **Consumer (`Envanter-Servisi`):** Bu olayı dinler, gelen `depo_id` ve ürün bilgilerini kullanarak `depo_stoklari` tablosundaki envanteri artırır ve `maliyet_fiyati` bilgisini kullanarak Ağırlıklı Ortalama Maliyet (AOM) hesaplamalarını günceller.

---

## API Endpoint'leri (v7.7)

### Yeni Backend Servisi: `Tedarik-Servisi`
-   `GET /api/admin/tedarik/tedarikciler`: Tüm tedarikçileri listeler.
-   `POST /api/admin/tedarik/tedarikciler`: Yeni tedarikçi oluşturur.
-   `PUT /api/admin/tedarik/tedarikciler/:id`: Bir tedarikçiyi günceller.
-   `DELETE /api/admin/tedarik/tedarikciler/:id`: Bir tedarikçiyi siler.
-   `GET /api/admin/tedarik/siparisler`: Tüm Satın Alma Siparişlerini listeler.
-   `POST /api/admin/tedarik/siparisler`: Yeni bir Satın Alma Siparişi oluşturur.
-   `GET /api/admin/tedarik/siparisler/:poId`: Tek bir PO'nun detaylarını getirir.
-   `PUT /api/admin/tedarik/siparisler/:poId/durum`: Bir PO'nun durumunu günceller.
-   `POST /api/admin/tedarik/siparisler/:poId/teslim-al`: Bir PO'ya ait ürünlerin depoya girişini kaydeder (Mal Kabul).
-   `GET /api/admin/tedarik/siparisler/:poId/gecmis`: Bir PO'nun denetim kayıtlarını listeler.

### Güncellenen Backend Servisi: `Auth-Servisi`
-   **YENİ Yetkiler:** `tedarikci_yonet`, `tedarik_yonet`, `tedarik_teslim_al`.
