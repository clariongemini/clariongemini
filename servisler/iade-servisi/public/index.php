<?php
// Iade-Servisi API Giriş Noktası v3.2
// servisler/iade-servisi/public/index.php

header('Content-Type: application/json');

// Veritabanı bağlantısı (varsayımsal)
try {
    $pdo = new PDO('mysql:host=db;dbname=prosiparis_iade', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['basarili' => false, 'mesaj' => 'Veritabanı bağlantı hatası.']);
    exit;
}

require_once __DIR__ . '/../src/IadeService.php';

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$service = new \ProSiparis\Iade\IadeService($pdo);

// Basit Rota Yönetimi
if (preg_match('/^\/api\/kullanici\/iade-talebi-olustur\/?$/', $path)) {
    if ($requestMethod === 'POST') {
        $response = $service->iadeTalebiOlustur(json_decode(file_get_contents('php://input'), true));
    }
} elseif (preg_match('/^\/api\/kullanici\/iade-talepleri\/?$/', $path)) {
    if ($requestMethod === 'GET') {
        // Kullanıcı ID'si JWT'den alınmalı.
        $kullaniciId = 1; // Varsayım
        $response = $service->listeleKullaniciIadeTalepleri($kullaniciId);
    }
} elseif (preg_match('/^\/api\/admin\/iade-talepleri\/?$/', $path)) {
     if ($requestMethod === 'GET') {
        $response = $service->listeleTumIadeTalepleri();
    }
} elseif (preg_match('/^\/api\/admin\/iade-talepleri\/(\d+)\/?$/', $path, $matches)) {
    $id = (int)$matches[1];
    if ($requestMethod === 'PUT') {
        $response = $service->guncelleIadeTalebiDurumu($id, json_decode(file_get_contents('php://input'), true));
    }
} elseif (preg_match('/^\/api\/admin\/iade-talepleri\/(\d+)\/odeme-yap\/?$/', $path, $matches)) {
    $id = (int)$matches[1];
    if ($requestMethod === 'POST') {
        $response = $service->iadeOdemeYap($id, json_decode(file_get_contents('php://input'), true));
    }
} elseif (preg_match('/^\/api\/depo\/iade-teslim-al\/(\d+)\/?$/', $path, $matches)) {
    $id = (int)$matches[1];
    if ($requestMethod === 'POST') {
        $kullaniciId = 2; // Depo görevlisi
        $response = $service->iadeTeslimAl($id, json_decode(file_get_contents('php://input'), true), $kullaniciId);
    }
}


if (!isset($response)) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
