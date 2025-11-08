<?php
// ayarlar.php - ProSiparis_API Projesi için Veritabanı ve JWT Yapılandırma Dosyası

// Hata raporlamayı geliştirme aşamasında açın, canlı ortamda kapatın.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Veritabanı bağlantı sabitleri
define('DB_HOST', 'localhost');
define('DB_NAME', 'prosiparis_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --- JWT (JSON Web Token) Ayarları ---
define('JWT_SECRET_KEY', 'bU8x@F!z%C*F-JaNdRgUjXn2r5u7x!A%D*G-KaPdSgVkYp3s6v9y$B&E)H+MbQeT');
define('JWT_EXPIRATION_TIME', 3600);
define('JWT_ISSUER', 'ProSiparisAPI');
define('JWT_AUDIENCE', 'ProSiparisApp');

// --- E-posta (SMTP) Ayarları ---
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'user@example.com');
define('SMTP_PASS', 'your_smtp_password');
define('SMTP_FROM_ADDRESS', 'destek@prosiparis.com');
define('SMTP_FROM_NAME', 'ProSiparis Destek');

// --- Cron Job (Zamanlanmış Görev) Ayarları ---
// Bu anahtar, /api/cron/run endpoint'ini tetiklemek için kullanılmalıdır.
// wget veya curl ile istek atarken "Authorization: Bearer <BU_DEGER>" başlığını ekleyin.
// Üretim ortamında bu değeri tahmin edilemez, uzun ve güvenli bir değerle değiştirin.
define('CRON_SECRET_KEY', 'EaFp7@z!uC*F-JaNdRgUjXn2r5u8x/A%');
