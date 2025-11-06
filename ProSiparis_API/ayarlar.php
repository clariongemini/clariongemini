<?php
// ayarlar.php - ProSiparis_API Projesi için Veritabanı Yapılandırma Dosyası

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
