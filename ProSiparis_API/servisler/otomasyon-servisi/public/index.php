<?php
// Otomasyon-Servisi API Giriş Noktası v4.3

header('Content-Type: application/json');

// Veritabanı bağlantısı ve servis kurulumu...
require_once __DIR__ . '/../src/OtomasyonService.php';
$service = new \ProSiparis\Otomasyon\OtomasyonService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);
$body = json_decode(file_get_contents('php://input'), true);
$kullaniciId = 1; // JWT'den alınmalı

$response = null;

// Rota Yönetimi
if ($path === '/api/sepet' && $requestMethod === 'GET') {
    $response = $service->getSepet($kullaniciId);
} elseif ($path === '/api/sepet/guncelle' && $requestMethod === 'POST') {
    $response = $service->guncelleSepet($kullaniciId, $body['urunler']);
} elseif ($path === '/api/cron/run' && $requestMethod === 'POST') {
    $response = $service->runCronJobs();
}

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Otomasyon-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
