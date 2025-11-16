# FulcrumOS API v10.4 - Proaktif AI Yönetim Asistanı (Co-Pilot)

Bu sürüm, FulcrumOS'un v1.0'dan beri devam eden işlevsel geliştirme yol haritasını tamamlayan ve platforma proaktif zeka katan son "katil özelliktir".

## v10.4 Yenilikleri

v6.0'da (Müşteri AI) kurulan altyapının, v7.0 (Dashboard) arayüzüne "Proaktif Yönetim Önerileri" (Admin AI Co-Pilot) sunacak şekilde genişletilmesi. Bu yenilikle platform, sadece geçmiş veriyi "gösteren" reaktif bir araç olmaktan çıkıp, gelecekteki operasyonel verimsizlikleri öngören ve çözüm öneren "proaktif" bir iş ortağına dönüşmüştür.

## Mimari Konseptler (v10.4 Güncellemeleri)

### AI Co-Pilot Veri Akışı
Admin AI Co-Pilot özelliği, birkaç servisin birbiriyle konuştuğu bir orkestrasyon mantığı ile çalışır:

1.  **Talep (Admin-UI):** Yönetici, Dashboard sayfasını yüklediğinde, AI Co-Pilot bileşeni öneri için API'ye bir istek gönderir.
2.  **Orkestratör (AI-Asistan-Servisi):** Bu isteği karşılar ve bir "orkestratör" görevi görür.
3.  **Veri Toplama (Raporlama & Envanter):** Orkestratör, analiz için gereken güncel verileri toplamak amacıyla `Raporlama-Servisi`'ne ve `Envanter-Servisi`'ne dahili (internal) API çağrıları yapar.
4.  **Yorumlama (Gemini):** Toplanan satış ve stok verilerini, önceden tanımlanmış bir prompt şablonu ile birlikte analiz ve yorumlama için Gemini LLM'ine gönderir.
5.  **Sonuç (Admin-UI):** Gemini'den gelen proaktif öneri metnini alır ve bunu Dashboard'daki AI Co-Pilot bileşeninde yöneticiye sunar.

## Servisler Arası Yeni İletişim Akışları

- **`AI-Asistan-Servisi` -> `Raporlama-Servisi`:** `GET /internal/raporlama/ai-satis-verisi` (Senkron)
- **`AI-Asistan-Servisi` -> `Envanter-Servisi`:** `GET /internal/envanter/ai-stok-durumu` (Senkron)

Bu yeni dahili ve senkron çağrılar, AI-Asistan-Servisi'nin anlık ve doğru yönetim analizi yapabilmesi için gerekli olan canlı verileri toplamasını sağlar.

## API Endpoint'leri (v10.4)

### Yeni Backend API Endpoint'leri (`ai-asistan-servisi`)
- `GET /api/admin/ai-co-pilot/oneriler`: Admin-UI'daki Co-Pilot bileşeni için proaktif önerileri üreten ve döndüren ana endpoint.

### Yeni Yetki (`auth-servisi`)
- **`ai_copilot_goruntule`**: Admin-UI'daki AI Co-Pilot bileşenini görüntüleme ve yukarıdaki API endpoint'ine erişim sağlama yetkisidir.
