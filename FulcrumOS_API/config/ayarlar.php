<?php
// ayarlar.php - FulcrumOS_API Projesi için Yapılandırma Dosyası

// Hata raporlamayı ortam değişkenine göre ayarla
ini_set('display_errors', getenv('APP_DEBUG') === 'true' ? 1 : 0);
error_reporting(E_ALL);

// Ortam değişkenlerinden yapılandırmayı yükle. Eğer tanımlı değilse, uygulama hata verecektir.
define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_CHARSET', 'utf8mb4');

define('JWT_SECRET_KEY', getenv('JWT_SECRET_KEY'));
define('JWT_EXPIRATION_TIME', getenv('JWT_EXPIRATION_TIME') ?: 3600);

define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT'));
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASS', getenv('SMTP_PASS'));
define('SMTP_FROM_ADDRESS', getenv('SMTP_FROM_ADDRESS'));
define('SMTP_FROM_NAME', 'FulcrumOS Destek');

define('CRON_SECRET_KEY', getenv('CRON_SECRET_KEY'));

// Iyzico Ayarları (varsa)
define('IYZICO_API_KEY', getenv('IYZICO_API_KEY'));
define('IYZICO_SECRET_KEY', getenv('IYZICO_SECRET_KEY'));
define('IYZICO_BASE_URL', getenv('IYZICO_BASE_URL'));
