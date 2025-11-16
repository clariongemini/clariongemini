<?php
// Raporlama-Servisi API Giriş Noktası v4.1
// servisler/raporlama-servisi/public/index.php

header('Content-Type: application/json');

// Veritabanı bağlantısı ve servis kurulumu...
try {
    $pdo = new PDO('mysql:host=db;dbname=prosiparis_raporlama', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['basarili' => false, 'mesaj' => 'Veritabanı bağlantı hatası.']);
    exit;
}
require_once __DIR__ . '/../src/RaporlamaService.php';
$service = new \ProSiparis\Raporlama\RaporlamaService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$response = null;

// Rota Yönetimi
if (preg_match('/^\/api\/admin\/raporlar\/?$/', $path)) {
    if ($requestMethod === 'GET') {
        // Filtreleri GET parametrelerinden al
        $filtreler = $_GET;
        $response = $service->getSatisRaporu($filtreler);
    }
} elseif (preg_match('/^\/api\/admin\/dashboard\/kpi-ozet\/?$/', $path)) {
    if ($requestMethod === 'GET') {
        $response = $service->getKpiOzet();
    }
}

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Raporlama-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
