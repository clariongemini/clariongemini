# FulcrumOS API v9.1 - Paketleme (Faz 1: Demo Veri Seti)

Bu sürüm, FulcrumOS platformunun "Paketleme & Dağıtım" fazının ilk ve en kritik adımını tamamlamaktadır. v9.1 ile, platformun "boş" bir iskelet olmaktan çıkarılıp, potansiyel müşterilere demo yapılabilecek veya geliştiriciler tarafından anında test edilebilecek "canlı bir ekosistem" haline gelmesi sağlanmıştır.

## v9.1 Yenilikleri: Merkezi Demo Veri Seti

-   **`demo.sql` Betiği Oluşturuldu:** Projenin kök dizinine eklenen `demo.sql` adındaki merkezi SQL betiği, tek bir komutla platformun 10+ mikroservisinin tamamını dolduracak zengin ve anlamlı veriler içerir. Bu, platformun "iş değerini" anında gösterebilmesini sağlar.

---

## Mimari Konseptler (v9.1 Güncellemeleri)

### 1. İlişkisel Bütünlüğün Korunması (Referential Integrity)
`demo.sql` betiği, mikroservis mimarisinin dağıtık yapısına rağmen, veriler arasında tam bir ilişkisel bütünlük kurar. Örneğin:
-   `Siparis-Servisi`'ne eklenen bir `siparis`, `Auth-Servisi`'nde oluşturulan sahte bir `kullanici`'ya aittir.
-   Bu siparişin içindeki `siparis_urunleri`, `Katalog-Servisi`'nde tanımlanan `urun_varyantlari`'na referans verir.
-   `Iade-Servisi`'ndeki bir `iade_talebi`, `Siparis-Servisi`'nde "teslim edildi" olarak işaretlenmiş bir `siparis`'e aittir.

Bu yaklaşım, platformun sadece rastgele verilerle değil, gerçek bir e-ticaret operasyonunu simüle eden, birbiriyle tutarlı bir veri setiyle "canlı" hale gelmesini sağlar.

---

## Kullanım Talimatı

Bu demo veri setini kullanarak FulcrumOS platformunu "canlı" bir demo ortamına dönüştürmek için aşağıdaki adımları izleyin:

1.  **Veritabanlarını Oluşturun:** Her bir mikroservis için (`fulcrumos_auth`, `fulcrumos_katalog`, `fulcrumos_siparis` vb.) boş MySQL veritabanları oluşturun.
2.  **Şemaları Yükleyin:** Her bir veritabanına, ilgili servisin `servisler/{servis-adi}/schema_*.sql` dosyasını yükleyerek (import ederek) tabloları oluşturun.
3.  **Demo Verilerini Yükleyin:** Bu projenin kök dizininde bulunan `demo.sql` betiğini, **oluşturduğunuz her bir veritabanına karşı ayrı ayrı** çalıştırın. Betik, her veritabanında sadece kendi tabloları mevcutsa ilgili verileri ekleyecektir.

Bu işlemler tamamlandığında, `admin@fulcrumos.com` (şifre: `admin123`) kullanıcısıyla `admin-ui`'ye giriş yaparak platformun tüm modüllerini "canlı" verilerle inceleyebilirsiniz.

---

## API Endpoint'leri (v9.1)

Bu sürümde yeni bir API endpoint'i eklenmemiştir. Bu sürümün odak noktası, platformu test ve demo için hazır hale getirecek zengin bir veri seti oluşturmaktır.
