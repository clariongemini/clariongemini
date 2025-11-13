<?php
// veritabani_baglantisi.php - PDO kullanarak güvenli veritabanı bağlantısı kurar.

// Yapılandırma dosyasını dahil et.
require_once __DIR__ . '/ayarlar.php';

// PDO bağlantı nesnesi
$pdo = null;

// Veritabanı bağlantısını kurmak için DSN (Data Source Name) oluştur.
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// PDO seçenekleri
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hataları istisna olarak yakala
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Sonuçları ilişkisel dizi olarak al
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Gerçek hazırlanmış ifadeler kullan
];

// Veritabanına bağlanmayı dene.
try {
    // Yeni bir PDO nesnesi oluşturarak bağlantıyı kur.
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // Bağlantı başarısız olursa, bir hata mesajı göster ve betiği sonlandır.
    // Canlı ortamda bu kadar detaylı bir hata mesajı göstermek güvenlik açığına neden olabilir.
    // Hataları loglamak daha iyi bir yaklaşımdır.
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500); // Sunucu Hatası
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'Veritabanı bağlantısı kurulamadı.'
        // Geliştirme için: 'hata_detayi' => $e->getMessage()
    ]);
    exit; // Betiği sonlandır.
}

// Bağlantı nesnesi ($pdo) artık diğer dosyalarda kullanılabilir.
