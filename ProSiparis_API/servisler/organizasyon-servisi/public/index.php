<?php
// Organizasyon-Servisi API Giriş Noktası v4.0
// servisler/organizasyon-servisi/public/index.php

header('Content-Type: application/json');

// Veritabanı bağlantısı ve servis kurulumu...
try {
    $pdo = new PDO('mysql:host=db;dbname=prosiparis_organizasyon', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['basarili' => false, 'mesaj' => 'Veritabanı bağlantı hatası.']);
    exit;
}
require_once __DIR__ . '/../src/OrganizasyonService.php';
$service = new \ProSiparis\Organizasyon\OrganizasyonService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$response = null;

// Rota Yönetimi
if (preg_match('/^\/api\/organizasyon\/depolar\/?$/', $path)) {
    if ($requestMethod === 'GET') {
        $response = $service->listeleDepolar();
    } elseif ($requestMethod === 'POST') {
        $response = $service->olusturDepo(json_decode(file_get_contents('php://input'), true));
    }
} elseif (preg_match('/^\/api\/organizasyon\/depolar\/(\d+)\/?$/', $path, $matches)) {
    $id = (int)$matches[1];
    if ($requestMethod === 'GET') {
        $response = $service->getDepo($id);
    } elseif ($requestMethod === 'PUT') {
        $response = $service->guncelleDepo($id, json_decode(file_get_contents('php://input'), true));
    } elseif ($requestMethod === 'DELETE') {
        $response = $service->silDepo($id);
    }
}
// v5.2 API Anahtar Kasası Rotaları
elseif (preg_match('/^\/api\/admin\/entegrasyonlar\/?$/', $path)) {
    if ($requestMethod === 'GET') {
        $response = $service->listeleAnahtarlar();
    } elseif ($requestMethod === 'POST') {
        $response = $service->olusturAnahtar(json_decode(file_get_contents('php://input'), true));
    }
} elseif (preg_match('/^\/api\/admin\/entegrasyonlar\/(\d+)\/?$/', $path, $matches)) {
    $id = (int)$matches[1];
    if ($requestMethod === 'DELETE') {
        $response = $service->silAnahtar($id);
    }
} elseif (preg_match('/^\/internal\/organizasyon\/anahtar-al\/?$/', $path) && $requestMethod === 'GET') {
    // Örnek: /internal/organizasyon/anahtar-al?servis=iyzico&anahtar=api_key
    $servis = $_GET['servis'] ?? null;
    $anahtar = $_GET['anahtar'] ?? null;
    $response = $service->getAnahtar($servis, $anahtar);
}

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Organizasyon-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
