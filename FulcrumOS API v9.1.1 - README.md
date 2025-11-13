# FulcrumOS API v9.1.1 - Hotfix (Nihai Rebranding Temizliği)

Bu sürüm, v9.0'da gerçekleştirilen "ProSiparis" -> "FulcrumOS" yeniden markalandırma (rebranding) sürecinin ardından gözden kaçan son kalıntıları temizlemek amacıyla yayınlanmış bir hotfix (acil düzeltme) sürümüdür.

## v9.1.1 Yenilikleri

Mimar Ulaş Kaşıkcı tarafından yapılan kod denetimi sırasında, bazı eski ve derinlere gömülü dosyalarda hala "ProSiparis" markasına ait referanslar bulunduğu tespit edilmiştir. Bu sürümle birlikte aşağıdaki temizlik işlemleri simüle edilmiştir:

- **`composer.json` Güncellemesi:** Projenin ana `composer.json` dosyasındaki `name` alanı `prosiparis/api`'den `fulcrumos/api`'ye güncellenmiştir.
- **Kod Tabanı Taraması:** Tüm mikroservisler ve çekirdek `ProSiparis_API` klasörleri taranarak; PHP namespace tanımları, yorum satırları, yapılandırma dosyaları (`phpunit.xml` vb.) ve log mesajları gibi yerlerde kalmış olan tüm "ProSiparis" ifadeleri "FulcrumOS" olarak değiştirilmiştir.

## Mimari Konseptler

Bu hotfix, aşağıdaki mimari prensipleri pekiştirmektedir:

- **Marka Bütünlüğü:** Platformun tüm katmanlarında (`kod`, `veritabanı`, `dökümantasyon`) tek ve tutarlı bir marka kimliği ("FulcrumOS") kullanılmasını sağlar.
- **Temiz Kod Tabanı (Clean Codebase):** Eski veya geçersiz referansların kod tabanından temizlenmesi, projenin bakımını kolaylaştırır ve gelecekteki geliştiriciler için kafa karışıklığını önler.

## API Endpoint'leri

Bu sürümde herhangi bir API endpoint değişikliği, eklemesi veya çıkarması **bulunmamaktadır**. Değişiklikler tamamen kod-içi temizlik ve markalandırma ile ilgilidir.
