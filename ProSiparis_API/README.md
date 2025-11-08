# ProSiparis API v2.9 - Finansal Zeka ve Envanter Bütünlüğü

Bu proje, "ProSiparis" mobil uygulaması için geliştirilmiş, B2C/B2B satıştan tedarik zinciri ve iade yönetimine kadar tüm e-ticaret operasyonlarını yöneten, finansal kârlılık analizi yapabilen ve tam denetlenebilir bir envanter altyapısına sahip, modern bir backend (API) sunucusudur.

**v2.9 Yenilikleri:** Bu sürüm, API'ye finansal bir beyin ve operasyonel bir omurga ekler. Tüm envanter hareketlerini kaydeden bir "Ledger" sistemi, satılan her ürünün maliyetini hesaplayan bir "Ağırlıklı Ortalama Maliyet (AOM)" motoru ve yöneticiler için dinamik bir "Raporlama API'si" ile donatılmıştır.

---

## Kurulum
1.  **Veritabanı Oluşturma:** `schema.sql` dosyasını (**v2.9**) veritabanınıza içe aktarın. Bu şema, yeni envanter hareket kaydı (ledger) tablosunu ve kârlılık için gerekli sütunları içerir.
2.  **Yapılandırma & Diğer Adımlar:** `composer install` komutunu çalıştırın, `config/ayarlar.php` dosyasını düzenleyin ve cron job'u kurun.

---

## Temel Konseptler (v2.9 Güncellemeleri)

### Envanter Bütünlüğü (Ledger) ve `EnvanterService` (YENİ)
Artık `urun_varyantlari.stok_adedi` sütununa asla doğrudan müdahale edilmez. Tüm envanter değişiklikleri (alım, satım, iade, sayım) yeni ve merkezi `EnvanterService` üzerinden yapılır.
-   **`envanter_hareketleri` Tablosu:** Stoğu değiştiren her bir işlem (örn: +50 adet alım, -2 adet satış) bu tabloya bir "hareket kaydı" olarak atılır. Bu, tam bir denetim ve geriye dönük izlenebilirlik sağlar.
-   **Görev Ayrımı:** `DepoService` ve `IadeService` gibi servisler artık stok miktarını kendileri güncellemez; bunun yerine `EnvanterService`'e "stoğu X kadar Y sebebiyle değiştir" komutu gönderirler.

### Kârlılık Analizi (Ağırlıklı Ortalama Maliyet - AOM/WAC) (YENİ)
Sistem artık satılan her ürünün maliyetini ve dolayısıyla her siparişin net kârını bilir.
1.  **AOM Hesaplama:** Tedarikçiden yeni bir maliyetle ürün alındığında (`satin_alma`), `EnvanterService` otomatik olarak o ürünün `agirlikli_ortalama_maliyet`'ini günceller.
2.  **Maliyet Kaydı:** Bir sipariş kargolandığında (`satis`), o anki `agirlikli_ortalama_maliyet` değeri `siparis_detaylari.maliyet_fiyati` sütununa kaydedilir.
3.  **Sonuç:** Bu iki veri sayesinde, `(birim_fiyat - maliyet_fiyati) * adet` formülüyle her bir satışın net kârı hesaplanabilir.

---

## API Endpoint'leri (v2.9)

### Dashboard API (GÜNCELLENMİŞ - Yetki: `dashboard_goruntule`)
-   **`GET /api/admin/dashboard/kpi-ozet`**: Yanıt artık `bugunku_net_kar` ve `kar_marji` gibi kârlılık metriklerini de içerir.
-   **`GET /api/admin/dashboard/satis-grafigi`**: Yanıt artık her gün için `tutar` (ciro) ve `kar` (net kâr) verilerini ayrı ayrı döndürür.

### Depo API'si (YENİ Endpoint - Yetki: `envanter_duzelt`)
-   **`POST /api/depo/envanter-duzeltme`**: Depo görevlisinin fiziksel sayım sonrası stok adetlerini düzeltmesini sağlar.
    -   **Body:** `{ "varyant_id": 12, "fiziksel_stok": 145, "sebep": "Yıllık sayım, 3 adet kayıp" }`
    -   Bu işlem, `envanter_hareketleri` tablosuna 'sayim_duzeltme_giris' veya 'sayim_duzeltme_cikis' olarak kaydedilir.

### Dinamik Raporlama API'si (YENİ - Yetki: `rapor_olustur`)
-   **`GET /api/admin/raporlar`**: Yöneticiye, sorgu parametreleri aracılığıyla filtrelenebilir, dinamik raporlar sunar.
    -   **Örnek Sorgular:**
        -   `?rapor_tipi=kar_zarar&baslangic_tarihi=2025-01-01&bitis_tarihi=2025-03-31`
        -   `?rapor_tipi=kar_zarar&grup_by=kategori`
    -   **Döndürdüğü Veri:** Sorguya göre gruplanmış Ciro, Maliyet ve Net Kâr verilerini içerir.

_(Diğer tüm endpoint'ler v2.8 ile aynıdır.)_
