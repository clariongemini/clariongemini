# FulcrumOS API v10.3.1 - Hotfix (Dosya Sistemi Bütünlüğü Düzeltmesi)

Bu sürüm, v10.3'ün dağıtımını engelleyen kritik proje bütünlüğü hatalarını düzelten bir ara sürümdür (hotfix).

## v10.3.1 Yenilikleri

Mimar (Ulaş Kaşıkcı) tarafından tespit edilen, v9.0 Rebranding hatasının (**`ProSiparis_API`** kalıntısı) ve v10.3'teki bozuk dosya yolu (**`.j-s-x`**) hatasının düzeltilmesi. Bu sürümle birlikte projenin dosya sistemi bütünlüğü yeniden sağlanmış ve geliştirme ortamı stabilize edilmiştir.

## Doğrulanan Proje Yapısı

- **Ana Dizin:** Projenin ana geliştirme dizininin **`FulcrumOS_API`** olduğu teyit edilmiş ve tüm çalışmalar bu dizin altına taşınmıştır. `ProSiparis_API` dizini kullanımdan kaldırılmıştır.
- **Dosya Yolları:** v10.3 (Muhasebe) modülüne ait tüm dosyaların, bozulmamış ve standartlara uygun yollarda oluşturulduğu doğrulanmıştır:
    - **Backend:** `FulcrumOS_API/servisler/entegrasyon-servisi/`
    - **Frontend:** `FulcrumOS_API/servisler/admin-ui/src/pages/MuhasebeLoglari.jsx`

## API Endpoint'leri (v10.3)

Bu sürümle birlikte API endpoint'lerinde bir değişiklik yapılmamıştır. v10.3 ile sunulan endpoint'ler geçerliliğini korumaktadır:

- `GET /api/admin/entegrasyonlar/muhasebe-loglari`: Muhasebe entegrasyon loglarının tamamını listeler.
- `POST /api/admin/entegrasyonlar/muhasebe-loglari/:logId/tekrar-dene`: `durum: 'hata'` olan bir log kaydını, `durum: 'beklemede'`'ye geri çekerek worker'ın işlemi tekrar denemesini sağlar.
