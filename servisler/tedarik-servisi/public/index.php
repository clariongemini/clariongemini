<?php
// Tedarik-Servisi API Giriş Noktası v3.2
// servisler/tedarik-servisi/public/index.php

// Basit bir veritabanı bağlantısı ve yönlendirme mekanizması.
// Gerçek bir uygulamada, bu kısımlar daha gelişmiş bir yapıya sahip olacaktır.

header('Content-Type: application/json');

// Veritabanı bağlantısı (varsayımsal)
try {
    $pdo = new PDO('mysql:host=db;dbname=prosiparis_tedarik', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['basarili' => false, 'mesaj' => 'Veritabanı bağlantı hatası.']);
    exit;
}

// Servis sınıfını dahil et (varsayımsal yol)
require_once __DIR__ . '/../src/TedarikService.php';

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$service = new \ProSiparis\Tedarik\TedarikService($pdo);

// Basit Rota Yönetimi
// Not: Bu yönlendirme güvenlik ve esneklik açısından basitleştirilmiştir.
if (preg_match('/^\/api\/admin\/tedarikciler\/?$/', $path)) {
    if ($requestMethod === 'GET') {
        $response = $service->listeleTedarikciler();
    } elseif ($requestMethod === 'POST') {
        $response = $service->olusturTedarikci(json_decode(file_get_contents('php://input'), true));
    }
} elseif (preg_match('/^\/api\/admin\/tedarikciler\/(\d+)\/?$/', $path, $matches)) {
    $id = (int)$matches[1];
    if ($requestMethod === 'PUT') {
        $response = $service->guncelleTedarikci($id, json_decode(file_get_contents('php://input'), true));
    } elseif ($requestMethod === 'DELETE') {
         $response = $service->silTedarikci($id);
    }
} elseif (preg_match('/^\/api\/admin\/satin-alma-siparisleri\/?$/', $path)) {
    if ($requestMethod === 'GET') {
        $response = $service->listeleSatinAlmaSiparisleri();
    } elseif ($requestMethod === 'POST') {
        $response = $service->olusturSatinAlmaSiparisi(json_decode(file_get_contents('php://input'), true));
    }
} elseif (preg_match('/^\/api\/admin\/satin-alma-siparisleri\/(\d+)\/?$/', $path, $matches)) {
    $id = (int)$matches[1];
    if ($requestMethod === 'PUT') {
        $response = $service->guncelleSatinAlmaSiparisi($id, json_decode(file_get_contents('php://input'), true));
    }
} elseif (preg_match('/^\/api\/depo\/beklenen-teslimatlar\/?$/', $path)) {
    if ($requestMethod === 'GET') {
        $response = $service->listeleBeklenenTeslimatlar();
    }
} elseif (preg_match('/^\/api\/depo\/teslimat-al\/(\d+)\/?$/', $path, $matches)) {
    $id = (int)$matches[1];
    if ($requestMethod === 'POST') {
        // Bu örnekte, kullanıcı ID'si gibi ek bilgiler normalde JWT'den veya başka bir
        // kimlik doğrulama mekanizmasından alınmalıdır. Şimdilik sabit bir değer kullanıyoruz.
        $kullaniciId = 1; // Varsayılan admin veya depo görevlisi
        $response = $service->teslimatAl($id, json_decode(file_get_contents('php://input'), true), $kullaniciId);
    }
}

if (!isset($response)) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
