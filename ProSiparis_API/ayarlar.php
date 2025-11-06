<?php
// ayarlar.php - ProSiparis_API Projesi için Veritabanı ve JWT Yapılandırma Dosyası

// Manuel olarak eklenen kütüphaneler için autoload dosyasını dahil et.
require_once __DIR__ . '/vendor/manual_autoload.php';

// Hata raporlamayı geliştirme aşamasında açın, canlı ortamda kapatın.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Veritabanı bağlantı sabitleri
// Bu bilgileri kendi veritabanı sunucunuza göre güncelleyin.
define('DB_HOST', 'localhost'); // Veritabanı sunucusunun adresi
define('DB_NAME', 'prosiparis_db'); // Veritabanı adı
define('DB_USER', 'root'); // Veritabanı kullanıcı adı
define('DB_PASS', ''); // Veritabanı parolası
define('DB_CHARSET', 'utf8mb4'); // Karakter seti

// --- JWT (JSON Web Token) Ayarları ---

// Token'ı imzalamak için kullanılacak gizli anahtar.
// BU ANAHTARI KESİNLİKLE GÜVENLİ VE TAHMİN EDİLEMEZ BİR DEĞERLE DEĞİŞTİRİN!
// Üretim ortamında bu değeri ortam değişkeninden (environment variable) okumak en iyisidir.
define('JWT_SECRET_KEY', 'bU8x@F!z%C*F-JaNdRgUjXn2r5u7x!A%D*G-KaPdSgVkYp3s6v9y$B&E)H+MbQeT');

// Token'ın geçerlilik süresi (saniye cinsinden). 3600 saniye = 1 saat.
define('JWT_EXPIRATION_TIME', 3600);

// Token'ı kimin oluşturduğunu belirten bilgi (isteğe bağlı ama önerilir).
define('JWT_ISSUER', 'ProSiparisAPI');

// Token'ın hangi kitleye yönelik olduğunu belirten bilgi (isteğe bağlı ama önerilir).
define('JWT_AUDIENCE', 'ProSiparisApp');
