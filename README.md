# ProSiparis API v4.0 - WMS Devrimi (Çoklu Depo & Hibrit Envanter)

Bu sürüm, ProSiparis API'sini temelden yeniden yapılandırarak, basit bir e-ticaret arka ucundan tam teşekküllü bir **Kurumsal Depo Yönetim Sistemi (WMS)** mimarisine dönüştürür. Bu, platformun "Çoklu Depo" yönetmesini ve hem adet bazlı hem de "Seri Numarası" (QR) bazlı hibrit envanter takibi yapabilmesini sağlar.

**v4.0'ın Ana Hedefleri:**
-   **Çoklu Depo Desteği:** Tüm envanter, sipariş, tedarik ve iade operasyonlarının belirli bir depo bağlamında yürütülmesini sağlamak.
-   **Hibrit Envanter Takibi:** Ürünlerin, özelliklerine göre ya adetle ya da benzersiz seri numarasıyla (garanti, değerli ürünler vb. için) takip edilebilmesini sağlamak.
-   **Kurumsal Yetenek:** Platformu, birden fazla fiziksel lokasyonu ve daha karmaşık envanter akışlarını yönetebilecek kurumsal bir seviyeye taşımak.

---

## Mimari Konseptler (v4.0 Değişiklikleri)

### 1. Yeni Servis: `organizasyon-servisi`
-   **Sorumluluk:** Depolar, şubeler, şirketler gibi tüm temel organizasyonel ve yapısal verileri yönetir. Bu, `auth-servisi`'ni sadece kimlik doğrulama ve yetkilendirme ile sorumlu tutarak mimari saflığı korur.
-   **Veritabanı:** `depolar` tablosunu içerir.

### 2. `Envanter-Servisi`'nin Yeniden Doğuşu (Çekirdek Refaktör)
-   **Genel Stok Kalktı:** Tek bir merkezdeki `stok_adedi` ve `agirlikli_ortalama_maliyet` kavramları tamamen kaldırıldı.
-   **Depo Bazlı Stok:** `depo_stoklari` tablosu, adet bazlı ürünlerin hangi depoda kaç adet olduğunu tutar.
-   **Depo Bazlı Maliyet:** `depo_stok_maliyetleri` tablosu, Ağırlıklı Ortalama Maliyetin her depo için ayrı ayrı hesaplanmasını sağlar.
-   **Seri Numarası Takibi:** `envanter_seri_numaralari` tablosu, seri numarasıyla takip edilen her bir ürünün yaşam döngüsünü (stokta, satıldı, iade vb.) ve konumunu (depo) takip eder.

### 3. Operasyonel Servislerin Evrimi (Tedarik, Siparis, Iade)
Tüm operasyonel servisler, artık işlemleri bir `depo_id` bağlamında gerçekleştirir ve ürünün `takip_yontemi`'ne göre hibrit çalışır.

-   **Tedarik:** `POST /api/depo/{depo_id}/teslimat-al/{po_id}` endpoint'i ile mal kabulü artık belirli bir depoya yapılır. Gelen ürünler adetle veya seri numarası listesiyle kabul edilebilir. `tedarik.mal_kabul_yapildi` olayı bu yeni yapıyı `Envanter-Servisi`'ne bildirir.

-   **Sipariş:**
    -   **Stok Optimizasyonu:** Sipariş oluşturma süreci, önce `Envanter-Servisi`'ne (`GET /internal/stok-durumu`) danışarak siparişteki tüm ürünleri karşılayabilecek en uygun depoyu bulur ve siparişi o depoya (`atanan_depo_id`) atar.
    -   **Depo Bazlı Sevkiyat:** Depo görevlileri, `GET /api/depo/{depo_id}/hazirlanacak-siparisler` ile sadece kendi depolarına atanmış siparişleri görür. `POST /api/depo/{depo_id}/siparis/{id}/kargoya-ver` ile kargolama işlemi yapılırken, seri nolu ürünler için taranan seri numarası doğrulanır. `siparis.kargolandi` olayı, sevkiyatın hangi depodan yapıldığını ve (varsa) hangi seri nolu ürünün çıktığını `Envanter-Servisi`'ne bildirir.

-   **Iade:** `POST /api/depo/{depo_id}/iade-teslim-al/{iade_id}` ile iade ürünleri belirli bir depoya kabul edilir. Ürünler, takip yöntemine göre adetle veya taranan seri numarasıyla sisteme geri alınır. `iade.stoga_geri_alindi` olayı bu bilgiyi `Envanter-Servisi`'ne iletir.

### 4. Servisler Arası İletişim Standardı
-   **Dahili API'ler:** Servislerin birbirleriyle anlık ve güvenli konuşması için `/internal/` öneki standartlaştırılmıştır. Örnek: `GET /internal/urun-takip-yontemi`, `GET /internal/stok-durumu`.
-   **Güncellenmiş Olaylar:** Tüm operasyonel olaylar (`tedarik.mal_kabul_yapildi`, `siparis.kargolandi`, `iade.stoga_geri_alindi`) artık `depo_id` ve hibrit ürün verilerini (adet veya seri no) içerecek şekilde güncellenmiştir.
