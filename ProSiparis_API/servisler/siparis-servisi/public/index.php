<?php
// Siparis-Servisi API Giriş Noktası v4.0

header('Content-Type: application/json');

// Veritabanı bağlantısı ve servis kurulumu...
require_once __DIR__ . '/../src/SiparisService.php';
$service = new \ProSiparis\Siparis\SiparisService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$response = null;

// Rota Yönetimi

// v7.5 ADMIN Endpoints
if (preg_match('/^\/api\/admin\/siparisler\/?$/', $path) && $requestMethod === 'GET') {
    $response = $service->listeleSiparisler();
} elseif (preg_match('/^\/api\/admin\/siparisler\/(\d+)\/?$/', $path, $matches)) {
    $siparisId = (int)$matches[1];
    if ($requestMethod === 'GET') {
        $response = $service->getSiparisDetay($siparisId);
    }
} elseif (preg_match('/^\/api\/admin\/siparisler\/(\d+)\/gecmis\/?$/', $path, $matches)) {
    $siparisId = (int)$matches[1];
    if ($requestMethod === 'GET') {
        $response = $service->getSiparisGecmisi($siparisId);
    }
} elseif (preg_match('/^\/api\/admin\/siparisler\/(\d+)\/durum\/?$/', $path, $matches)) {
    $siparisId = (int)$matches[1];
    if ($requestMethod === 'PUT') {
        $response = $service->guncelleSiparisDurumu($siparisId, json_decode(file_get_contents('php://input'), true));
    }
} elseif (preg_match('/^\/api\/admin\/siparisler\/(\d+)\/kargo\/?$/', $path, $matches)) {
    $siparisId = (int)$matches[1];
    if ($requestMethod === 'POST') {
        $response = $service->ekleKargoBilgisi($siparisId, json_decode(file_get_contents('php://input'), true));
    }
}
// Mevcut Public/Depo Endpoints
elseif (preg_match('/^\/api\/depo\/(\d+)\/hazirlanacak-siparisler\/?$/', $path, $matches)) {
    if ($requestMethod === 'GET') {
        $depoId = (int)$matches[1];
        $response = $service->getHazirlanacakSiparisler($depoId);
    }
} elseif (preg_match('/^\/api\/depo\/(\d+)\/siparis\/(\d+)\/kargoya-ver\/?$/', $path, $matches)) {
    if ($requestMethod === 'POST') {
        $depoId = (int)$matches[1];
        $siparisId = (int)$matches[2];
        $response = $service->kargoyaVer($depoId, $siparisId, json_decode(file_get_contents('php://input'), true));
    }
} elseif (strpos($path, '/api/odeme/baslat') !== false) {
    // Sipariş oluşturma...
}
// ... diğer rotalar

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Siparis-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
