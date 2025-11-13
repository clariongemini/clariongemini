# FulcrumOS API v9.0 - Proje Yeniden Markalama

Bu sürüm, platformun "ProSiparis" olan eski kimliğinden "FulcrumOS" olarak bilinen yeni marka kimliğine geçişini tamamlayan, teknik ve yapısal olarak kritik bir kilometre taşıdır. Bu değişiklik, projenin vizyonunu ve gelecekteki yönünü daha iyi yansıtmak için yapılmıştır.

## v9.0 Değişiklikleri: Kökten Uca Yeniden Markalama

-   **Ana Dizin Adı Güncellendi:** Projenin kök dizini `ProSiparis_API/`'den `FulcrumOS_API/` olarak yeniden adlandırılmıştır.
-   **PHP Namespace'leri Değiştirildi:** Tüm `ProSiparis\\` önekli PHP namespace'leri, `FulcrumOS\\` olarak güncellenmiştir. Bu, tüm mikroservislerdeki sınıf ve arayüzleri kapsamaktadır.
-   **Composer Paket Adı Değiştirildi:** `composer.json` dosyalarındaki paket adları (`prosiparis/core`, `prosiparis/auth-servisi` vb.) `fulcrumos/` önekini kullanacak şekilde değiştirilmiştir.
-   **Yapılandırma ve Metin Değişiklikleri:** Kod tabanındaki "ProSiparis" geçen tüm metinler, log mesajları ve yapılandırma dosyaları "FulcrumOS" olarak güncellenmiştir.

Bu kapsamlı refactoring işlemi, projenin teknik temelini yeni marka kimliğiyle tamamen uyumlu hale getirmiştir.

---

## Mimari Bütünlük

Yeniden markalama işlemi, v8.1'de oluşturulan ve backend (`PHPUnit`) ile frontend (`Vitest`) testlerini kapsayan **kapsamlı test altyapısı** sayesinde güvenli bir şekilde gerçekleştirilmiştir. Tüm testler, bu büyük refactoring sonrasında başarıyla çalıştırılarak platformun işlevsel bütünlüğünün korunduğu doğrulanmıştır.

---

## API Endpoint'leri (v9.0)

Bu sürümde API endpoint'lerinde herhangi bir değişiklik yapılmamıştır. Değişiklikler tamamen yapısal ve isimlendirme odaklıdır.
