# ProSiparis API v4.1 - Merkezi Raporlama ve Optimizasyon

Bu sürüm, v4.0'da kurulan WMS mimarisinin yarattığı en büyük zorluğu çözer: **dağıtık veri kaynaklarından bütünsel raporlama yapabilmek**. Ayrıca, v4.0'da tespit edilen bir performans riskini gidererek sistemi daha ölçeklenebilir hale getirir.

## v4.1 Yenilikleri:

1.  **Dağıtık Raporlama Sorunu Çözüldü:** Artık 7+ farklı mikroservis veritabanına yayılmış olan operasyonel veriyi (satışlar, stok hareketleri vb.) merkezi bir yerde toplayan ve analiz eden yeni bir `raporlama-servisi` kuruldu.
2.  **v4.0 Stok Optimizasyon Performans Riski Giderildi:** Sipariş oluşturma sırasındaki stok uygunluğu kontrolü, bu işin mantığının sahibi olan `Envanter-Servisi`'ne taşındı. Bu, `Siparis-Servisi` üzerindeki analiz yükünü kaldırarak performansı artırdı.

---

## Mimari Konseptler (v4.1 Güncellemeleri)

### Yeni Servis: `servisler/raporlama-servisi/`
-   **Sorumluluk:** Bu servis, bir **Veri Ambarı (Data Warehouse)** görevi görür. Diğer servisler tarafından yayınlanan operasyonel olayları (`siparis.kargolandi` vb.) dinler, bu verileri zenginleştirir ve analiz için optimize edilmiş kendi **OLAP** veritabanına yazar.
-   **Denormalize Veri Ambarı:** `rapor_satis_ozetleri` gibi tablolar, sorgu anında birden fazla servise JOIN atma ihtiyacını ortadan kaldırmak için kasıtlı olarak veri tekrarı içerir (örn: `depo_adi`, `urun_adi` vb.). Bu, raporların milisaniyeler içinde üretilmesini sağlar.

---

## Servisler Arası Yeni İletişim Akışları

### Asenkron Akış (Olay Dinleme)
-   **`Raporlama-Servisi`'nin Olay Aboneliği:**
    -   `siparis.kargolandi` olayını dinler -> `rapor_satis_ozetleri` tablosunu doldurur.
    -   `tedarik.mal_kabul_yapildi` ve `iade.stoga_geri_alindi` olaylarını dinler -> `rapor_stok_hareketleri` tablosunu besler.

### Senkron Akış (Performans İyileştirmesi)
-   **`Siparis-Servisi` -> `Envanter-Servisi` Çağrısı:**
    -   `Siparis-Servisi` artık stok optimizasyonu için `Envanter-Servisi`'ne sepetin tamamını gönderdiği yeni bir dahili API çağrısı yapar: `POST /internal/envanter/uygun-depo-bul`.
    -   `Envanter-Servisi`, kendi veritabanında yaptığı analiz sonucunda uygun depoların bir listesini döner. Bu, analiz yükünü doğru servise taşır.

---

## API Endpoint'leri (v4.1)

### Yeni Endpoint'ler
-   `GET /api/admin/raporlar`: Tarih, depo, kategori gibi filtrelere göre satış raporlarını sunar. (Yetki: `rapor_goruntule`)
-   `GET /api/admin/dashboard/kpi-ozet`: Dashboard için toplam ciro, kar, sipariş sayısı gibi temel metrikleri sunar.
-   `GET /api/organizasyon/depolar`: Sistemdeki tüm depoları listeler.
-   `POST /internal/envanter/uygun-depo-bul`: `Siparis-Servisi`'nin stok optimizasyonu için kullandığı yeni dahili endpoint.

### Güncellenmiş Endpoint'ler
-   `POST /api/odeme/baslat`: Artık stok optimizasyon mantığını kendi içinde çalıştırmak yerine `Envanter-Servisi`'ne delege eder.

### Kaldırılan Endpoint'ler
-   `GET /api/admin/raporlar` (Ana Monolith): Monolith'teki eski ve işlevsiz raporlama endpoint'i tamamen kaldırılmıştır.
