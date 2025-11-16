<?php
// Destek-Servisi API Giriş Noktası v4.3

header('Content-Type: application/json');

// Veritabanı bağlantısı ve servis kurulumu...
require_once __DIR__ . '/../src/DestekService.php';
$service = new \ProSiparis\Destek\DestekService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);
$body = json_decode(file_get_contents('php://input'), true);

$response = null;
$kullaniciId = 1; // JWT'den alınmalı

// Rota Yönetimi
if (preg_match('/^\/api\/kullanici\/destek-talepleri\/?$/', $path)) {
    if ($requestMethod === 'GET') {
        $response = $service->getKullaniciTalepleri($kullaniciId);
    } elseif ($requestMethod === 'POST') {
        $response = $service->talepOlustur($kullaniciId, $body);
    }
} elseif (preg_match('/^\/api\/kullanici\/destek-talepleri\/(\d+)\/?$/', $path, $matches)) {
    if ($requestMethod === 'POST') {
        $response = $service->mesajGonder((int)$matches[1], $kullaniciId, $body);
    }
}
// ... Admin rotaları

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Destek-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
