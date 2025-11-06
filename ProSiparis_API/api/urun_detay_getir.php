<?php
// api/urun_detay_getir.php - Belirli bir ürünün detaylarını getirir.

// Gerekli dosyaları ve başlıkları dahil et.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET');
require_once __DIR__ . '/../veritabani_baglantisi.php';

// Sadece GET isteklerini kabul et.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Metot İzin Verilmedi
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Sadece GET metodu kabul edilmektedir.']);
    exit;
}

// GET parametresinden ürün ID'sini al.
$urun_id = isset($_GET['urun_id']) ? (int)$_GET['urun_id'] : 0;

// Ürün ID'si geçerli değilse hata döndür.
if ($urun_id <= 0) {
    http_response_code(400); // Kötü İstek
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Geçerli bir ürün ID\'si belirtilmelidir.']);
    exit;
}

// Belirli bir ürünü seçen sorgu.
$sql = "SELECT id, urun_adi, aciklama, fiyat, resim_url FROM urunler WHERE id = :urun_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':urun_id', $urun_id, PDO::PARAM_INT);
    $stmt->execute();

    $urun = $stmt->fetch();

    // Ürün bulunduysa, JSON olarak döndür.
    if ($urun) {
        http_response_code(200); // OK
        echo json_encode(['durum' => 'basarili', 'urun' => $urun]);
    } else {
        // Ürün bulunamadıysa.
        http_response_code(404); // Bulunamadı
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Belirtilen ID\'ye sahip ürün bulunamadı.']);
    }

} catch (PDOException $e) {
    // Veritabanı hatası.
    http_response_code(500);
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Ürün detayı getirilirken bir veritabanı hatası oluştu.']);
}
