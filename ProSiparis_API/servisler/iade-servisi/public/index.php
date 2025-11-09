<?php
// Iade-Servisi API Giriş Noktası v4.0
// servisler/iade-servisi/public/index.php

header('Content-Type: application/json');

// Veritabanı bağlantısı ve servis kurulumu...
try {
    $pdo = new PDO('mysql:host=db;dbname=prosiparis_iade', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['basarili' => false, 'mesaj' => 'Veritabanı bağlantı hatası.']);
    exit;
}
require_once __DIR__ . '/../src/IadeService.php';
$service = new \ProSiparis\Iade\IadeService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$response = null;

// Rota Yönetimi
if (preg_match('/^\/api\/depo\/(\d+)\/iade-teslim-al\/(\d+)\/?$/', $path, $matches)) {
    if ($requestMethod === 'POST') {
        $depoId = (int)$matches[1];
        $iadeId = (int)$matches[2];
        $kullaniciId = 2; // Depo görevlisi, JWT'den alınmalı
        $response = $service->iadeTeslimAl($depoId, $iadeId, json_decode(file_get_contents('php://input'), true), $kullaniciId);
    }
} elseif (preg_match('/^\/api\/kullanici\/iade-talebi-olustur\/?$/', $path)) {
    // Diğer iade endpoint'leri... (değişiklik yok)
}
// ... diğer rotalar

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Iade-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
