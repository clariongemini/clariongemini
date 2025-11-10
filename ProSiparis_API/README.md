# ProSiparis API v6.0 - AI Alışveriş Asistanı (Faz-1)

## v6.0 Yenilikleri

Bu sürüm, platformun sağlam altyapısı üzerine inşa edilen ilk "katil özelliği" (killer feature) sunmaktadır: **Gemini tabanlı AI Alışveriş Asistanı**. Bu özellik, müşterilerin artık standart arama kutuları yerine, "doğal dil" kullanarak ürün aramasına, karmaşık sorular sormasına ve canlı stok/fiyat verilerine dayalı kişiselleştirilmiş ürün önerileri almasına olanak tanır. Bu, müşteri deneyimini temelden dönüştürerek platformu standart bir e-ticaret motorundan akıllı bir "alışveriş partneri" haline getirir.

## Mimari Konseptler (v6.0 Güncellemeleri)

### Yeni Servis: AI-Asistan-Servisi

-   **Konum:** `servisler/ai-asistan-servisi/`
-   Bu yeni servis, AI ile ilgili tüm mantığın merkezidir. Hem asenkron "öğrenme" (vektör veritabanını besleme) hem de senkron "yanıtlama" (müşteri sorularını işleme) görevlerini yönetir.

### AI'ın "Beyni": Vektör Veritabanı ve Asenkron Besleme

-   **Vektör Veritabanı:** `AI-Asistan-Servisi`, `urun_vektorleri` adında bir tablo yönetir. Bu tablo, ürünlerin metinsel verilerinin (isim, açıklama vb.) anlamsal (semantik) bir temsilini tutan "vektörleri" (embedding'ler) içerir.
-   **Asenkron Besleme:** `Katalog-Servisi`'nde bir ürün yaratıldığında veya güncellendiğinde, `katalog.urun.guncellendi` gibi bir olay anında Message Broker'a (RabbitMQ) yayınlanır. `AI-Asistan-Servisi`'nin "worker" betiği bu olayı dinler, ürün metinlerini Gemini Embedding API'si ile vektöre dönüştürür ve kendi veritabanını günceller. Bu sayede, AI'ın "bilgisi" her zaman katalogla güncel kalır.

### AI'ın "Ağzı": 2 Adımlı Soru Yanıtlama Mimarisi

`POST /api/asistan/soru-sor` endpoint'i tetiklendiğinde, AI-Asistan-Servisi aşağıdaki 2 adımlı AI akışını çalıştırır:

1.  **Anlamsal Arama (Vector Search):** Müşterinin doğal dildeki sorusu ("kırmızı, kapüşonlu...") önce bir "soru vektörüne" dönüştürülür. Ardından, bu soru vektörüne "anlamsal olarak" en çok benzeyen ürünler, vektör veritabanından bulunur.
2.  **Canlı Veri Zenginleştirme + LLM Cevap Üretme:** Anlamsal olarak bulunan ürünlerin (`varyant_id`'leri) canlı verileri (stok, fiyat, resim vb.) `Katalog-Servisi` ve `Envanter-Servisi`'ne yapılan anlık dahili API çağrıları ile toplanır. Son olarak, müşterinin sorusu ve bulunan canlı ürün verileri, Gemini Chat API'sine tek bir "prompt" içinde gönderilir. Gemini, bu bilgileri kullanarak müşteriye doğal, akıcı ve bilgilendirici bir cevap metni üretir.

## Servisler Arası Yeni İletişim Akışları

### Asenkron Akış (AI'ı Besleme)

-   `Katalog-Servisi` (Admin bir ürünü günceller) -> **`katalog.urun.guncellendi` Olayı** -> Message Broker (RabbitMQ) -> `AI-Asistan-Servisi` (Worker dinler, Gemini'dan vektör alır ve kendi DB'sine yazar).

### Senkron Akış (Müşteriye Yanıt Verme)

-   Müşteri -> `Gateway-Servisi` (`/api/asistan/soru-sor`) -> `AI-Asistan-Servisi`
    -   `AI-Asistan-Servisi` -> `Katalog-Servisi` (`/internal/varyant-detaylari`)
    -   `AI-Asistan-Servisi` -> `Envanter-Servisi` (`/internal/stok-durumu`)
-   `AI-Asistan-Servisi` (Cevabı üretir) -> Müşteri

## API Endpoint'leri (v6.0)

### Yeni PUBLIC Endpoint'ler

-   `POST /api/asistan/soru-sor`: AI Alışveriş Asistanı'na doğal dilde soru sormak için kullanılır.

### Yeni ADMIN Endpoint'ler

-   `POST /api/admin/urunler`: Yeni bir ürün oluşturur (ve AI'ı besleyen olayı tetikler).
-   `PUT /api/admin/urunler/{id}`: Bir ürünü günceller (ve AI'ı besleyen olayı tetikler).
