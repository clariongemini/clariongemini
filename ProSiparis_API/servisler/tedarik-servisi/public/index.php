<?php
// Tedarik-Servisi API Giriş Noktası v4.0
// servisler/tedarik-servisi/public/index.php

header('Content-Type: application/json');

// Veritabanı bağlantısı ve servis kurulumu...
try {
    $pdo = new PDO('mysql:host=db;dbname=prosiparis_tedarik', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['basarili' => false, 'mesaj' => 'Veritabanı bağlantı hatası.']);
    exit;
}
require_once __DIR__ . '/../src/TedarikService.php';
$service = new \ProSiparis\Tedarik\TedarikService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$response = null;

// Rota Yönetimi
if (preg_match('/^\/api\/depo\/(\d+)\/teslimat-al\/(\d+)\/?$/', $path, $matches)) {
    if ($requestMethod === 'POST') {
        $depoId = (int)$matches[1];
        $poId = (int)$matches[2];
        $kullaniciId = 1; // JWT'den alınmalı
        $response = $service->teslimatAl($depoId, $poId, json_decode(file_get_contents('php://input'), true), $kullaniciId);
    }
} elseif (preg_match('/^\/api\/admin\/tedarikciler\/?(\d+)?\/?$/', $path, $matches)) {
    // Tedarikçi CRUD endpoint'leri... (değişiklik yok)
} elseif (preg_match('/^\/api\/admin\/satin-alma-siparisleri\/?(\d+)?\/?$/', $path, $matches)) {
    // PO CRUD endpoint'leri... (değişiklik yok)
}


if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Tedarik-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
