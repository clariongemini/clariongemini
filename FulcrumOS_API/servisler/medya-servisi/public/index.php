<?php
// Medya-Servisi API Giriş Noktası v7.4
// servisler/medya-servisi/public/index.php

header('Content-Type: application/json');

// Veritabanı bağlantısı
try {
    // Medya veritabanı adı, schema_medya.sql'e uygun olmalı.
    // Varsayılan olarak ana veritabanı adını kullanıyoruz, gerçekte farklı olabilir.
    $pdo = new PDO('mysql:host=db;dbname=prosiparis_medya', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['basarili' => false, 'mesaj' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]);
    exit;
}

require_once __DIR__ . '/../src/MedyaService.php';
$service = new \ProSiparis\Medya\MedyaService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$response = null;

// Rota Yönetimi
if (preg_match('/^\/api\/admin\/medya\/?$/', $path)) {
    if ($requestMethod === 'GET') {
        $response = $service->listeleMedyalar();
    }
    // Dosya yükleme POST isteği
    elseif ($requestMethod === 'POST') {
        // Dosya yükleme işlemleri $_FILES üzerinden yapılır, php://input değil.
        if (!empty($_FILES['dosya'])) {
            $response = $service->yukleDosya($_FILES['dosya']);
        } else {
            $response = ['basarili' => false, 'kod' => 400, 'mesaj' => 'Yüklenecek dosya bulunamadı. Lütfen "dosya" adında bir form alanı gönderin.'];
        }
    }
}
// Medya silme
elseif (preg_match('/^\/api\/admin\/medya\/(\d+)\/?$/', $path, $matches)) {
    $id = (int)$matches[1];
    if ($requestMethod === 'DELETE') {
        $response = $service->silMedya($id);
    }
}

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Medya-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
