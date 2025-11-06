<?php
// api/kullanici_kayit.php - Yeni kullanıcı kaydı oluşturur.

// Gerekli dosyaları ve başlıkları dahil et.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST'); // İzin verilen metot
require_once __DIR__ . '/../veritabani_baglantisi.php';

// Sadece POST isteklerini kabul et.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Metot İzin Verilmedi
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Sadece POST metodu kabul edilmektedir.']);
    exit;
}

// POST verilerini al.
$veri = json_decode(file_get_contents('php://input'), true);

// Gerekli alanların kontrolü.
if (empty($veri['ad_soyad']) || empty($veri['eposta']) || empty($veri['parola'])) {
    http_response_code(400); // Kötü İstek
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Ad soyad, e-posta ve parola alanları zorunludur.']);
    exit;
}

// Değişkenleri ata.
$ad_soyad = $veri['ad_soyad'];
$eposta = $veri['eposta'];
$parola = $veri['parola'];

// E-posta formatını kontrol et.
if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Geçersiz e-posta formatı.']);
    exit;
}

// Parolayı güvenli bir şekilde hash'le.
$parola_hash = password_hash($parola, PASSWORD_DEFAULT);

// Veritabanına ekleme sorgusu.
$sql = "INSERT INTO kullanicilar (ad_soyad, eposta, parola) VALUES (:ad_soyad, :eposta, :parola)";

try {
    $stmt = $pdo->prepare($sql);

    // Değerleri bağla.
    $stmt->bindParam(':ad_soyad', $ad_soyad);
    $stmt->bindParam(':eposta', $eposta);
    $stmt->bindParam(':parola', $parola_hash);

    // Sorguyu çalıştır.
    if ($stmt->execute()) {
        http_response_code(201); // Oluşturuldu
        echo json_encode(['durum' => 'basarili', 'mesaj' => 'Kayıt başarıyla oluşturuldu.']);
    } else {
        http_response_code(500); // Sunucu Hatası
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Kayıt oluşturulurken bir hata oluştu.']);
    }
} catch (PDOException $e) {
    // E-posta zaten mevcutsa (unique constraint ihlali).
    if ($e->getCode() == 23000) {
        http_response_code(409); // Çakışma
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Bu e-posta adresi zaten kayıtlı.']);
    } else {
        http_response_code(500);
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
}
