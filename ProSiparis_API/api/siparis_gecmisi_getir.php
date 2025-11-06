<?php
// api/siparis_gecmisi_getir.php - Belirli bir kullanıcının geçmiş siparişlerini listeler.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET');
require_once __DIR__ . '/../veritabani_baglantisi.php';

// Sadece GET isteklerini kabul et.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Sadece GET metodu kabul edilmektedir.']);
    exit;
}

// GET parametresinden kullanıcı ID'sini al.
$kullanici_id = isset($_GET['kullanici_id']) ? (int)$_GET['kullanici_id'] : 0;

// Kullanıcı ID'si geçerli değilse hata döndür.
if ($kullanici_id <= 0) {
    http_response_code(400);
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Geçerli bir kullanıcı ID\'si belirtilmelidir.']);
    exit;
}

// Belirli bir kullanıcıya ait siparişleri en yeniden eskiye doğru sıralayarak seçen sorgu.
$sql = "SELECT id, siparis_tarihi, toplam_tutar, durum FROM siparisler WHERE kullanici_id = :kullanici_id ORDER BY siparis_tarihi DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':kullanici_id', $kullanici_id, PDO::PARAM_INT);
    $stmt->execute();

    $siparisler = $stmt->fetchAll();

    // Siparişleri JSON olarak döndür.
    // Sipariş olmasa bile boş bir dizi dönmek bir hata değildir.
    http_response_code(200);
    echo json_encode(['durum' => 'basarili', 'siparisler' => $siparisler]);

} catch (PDOException $e) {
    // Veritabanı hatası.
    http_response_code(500);
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'Sipariş geçmişi getirilirken bir veritabanı hatası oluştu.'
    ]);
}
