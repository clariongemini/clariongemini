<?php
// api/urun_listesi_getir.php - Veritabanındaki tüm ürünleri listeler.

// Gerekli dosyaları ve başlıkları dahil et.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET'); // İzin verilen metot
require_once __DIR__ . '/../veritabani_baglantisi.php';

// Sadece GET isteklerini kabul et.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Metot İzin Verilmedi
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Sadece GET metodu kabul edilmektedir.']);
    exit;
}

// Tüm ürünleri seçen sorgu.
$sql = "SELECT id, urun_adi, aciklama, fiyat, resim_url FROM urunler ORDER BY id DESC";

try {
    $stmt = $pdo->query($sql);
    $urunler = $stmt->fetchAll();

    // Ürünleri JSON olarak döndür.
    http_response_code(200); // OK
    echo json_encode(['durum' => 'basarili', 'urunler' => $urunler]);

} catch (PDOException $e) {
    // Veritabanı hatası.
    http_response_code(500);
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Ürünler getirilirken bir veritabanı hatası oluştu.']);
}
