<?php
// Envanter-Servisi API Giriş Noktası v4.0
// servisler/envanter-servisi/public/index.php

header('Content-Type: application/json');

// Veritabanı bağlantısı (varsayımsal)
try {
    $pdo = new PDO('mysql:host=db;dbname=prosiparis_envanter', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['basarili' => false, 'mesaj' => 'Veritabanı bağlantı hatası.']);
    exit;
}

require_once __DIR__ . '/../src/EnvanterService.php';

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$service = new \ProSiparis\Envanter\EnvanterService($pdo);

// Sadece dahili (internal) API'ler için basit rota yönetimi
if (preg_match('/^\/internal\/stok-durumu\/?$/', $path)) {
    if ($requestMethod === 'GET' && isset($_GET['varyant_ids'])) {
        $varyantIds = explode(',', $_GET['varyant_ids']);
        $varyantIds = array_map('intval', $varyantIds);
        $response = $service->getDepoStokDurumu($varyantIds);
    }
} elseif (preg_match('/^\/internal\/envanter\/uygun-depo-bul\/?$/', $path)) {
    if ($requestMethod === 'POST') {
        $sepet = json_decode(file_get_contents('php://input'), true);
        if (isset($sepet['sepet'])) {
            $response = $service->findUygunDepo($sepet['sepet']);
        } else {
            $response = ['basarili' => false, 'kod' => 400, 'mesaj' => '`sepet` anahtarı ile bir JSON nesnesi bekleniyor.'];
        }
    }
}

if (!isset($response)) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
