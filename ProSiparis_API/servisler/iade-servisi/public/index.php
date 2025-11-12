<?php
// Iade-Servisi API Giriş Noktası v7.6

header('Content-Type: application/json');

// Veritabanı bağlantısı
try {
    $pdo = new PDO('mysql:host=db;dbname=prosiparis_iade', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['basarili' => false, 'mesaj' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]);
    exit;
}

require_once __DIR__ . '/../src/IadeService.php';
$service = new \ProSiparis\Iade\IadeService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$response = null;

// Rota Yönetimi
// v7.6 ADMIN Endpoints
if (preg_match('/^\/api\/admin\/iadeler\/?$/', $path) && $requestMethod === 'GET') {
    $response = $service->listeleIadeler();
} elseif (preg_match('/^\/api\/admin\/iadeler\/(\d+)\/?$/', $path, $matches)) {
    $iadeId = (int)$matches[1];
    if ($requestMethod === 'GET') {
        $response = $service->getIadeDetay($iadeId);
    }
} elseif (preg_match('/^\/api\/admin\/iadeler\/(\d+)\/gecmis\/?$/', $path, $matches)) {
    $iadeId = (int)$matches[1];
    if ($requestMethod === 'GET') {
        $response = $service->getIadeGecmisi($iadeId);
    }
} elseif (preg_match('/^\/api\/admin\/iadeler\/(\d+)\/durum\/?$/', $path, $matches)) {
    $iadeId = (int)$matches[1];
    if ($requestMethod === 'PUT') {
        $response = $service->guncelleIadeDurumu($iadeId, json_decode(file_get_contents('php://input'), true));
    }
}

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Iade-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
