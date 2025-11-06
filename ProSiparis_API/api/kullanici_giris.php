<?php
// api/kullanici_giris.php - Kullanıcı giriş işlemini yönetir.

// Gerekli dosyaları ve başlıkları dahil et.
header('Content-Type: application/json; charset=utf--8');
header('Access-Control-Allow-Methods: POST');
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
if (empty($veri['eposta']) || empty($veri['parola'])) {
    http_response_code(400); // Kötü İstek
    echo json_encode(['durum' => 'hata', 'mesaj' => 'E-posta ve parola alanları zorunludur.']);
    exit;
}

// Değişkenleri ata.
$eposta = $veri['eposta'];
$parola = $veri['parola'];

// Veritabanından kullanıcıyı e-posta adresine göre bul.
$sql = "SELECT id, ad_soyad, parola FROM kullanicilar WHERE eposta = :eposta";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':eposta', $eposta);
    $stmt->execute();

    // Kullanıcıyı bul.
    $kullanici = $stmt->fetch();

    // Kullanıcı varsa ve parola doğruysa.
    if ($kullanici && password_verify($parola, $kullanici['parola'])) {
        // Başarılı giriş.
        http_response_code(200); // OK
        echo json_encode([
            'durum' => 'basarili',
            'kullanici_id' => $kullanici['id'],
            'ad_soyad' => $kullanici['ad_soyad']
        ]);
    } else {
        // E-posta veya parola hatalı.
        http_response_code(401); // Yetkisiz
        echo json_encode(['durum' => 'hata', 'mesaj' => 'E-posta veya parola hatalı.']);
    }
} catch (PDOException $e) {
    // Veritabanı hatası.
    http_response_code(500);
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Giriş yapılırken bir veritabanı hatası oluştu.']);
}
