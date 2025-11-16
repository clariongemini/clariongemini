<?php
// Kupon-Servisi API Giriş Noktası v4.2

header('Content-Type: application/json');

// Veritabanı bağlantısı ve servis kurulumu...
try {
    $pdo = new PDO('mysql:host=db;dbname=prosiparis_kupon', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { /* ... */ }
require_once __DIR__ . '/../src/KuponService.php';
$service = new \ProSiparis\Kupon\KuponService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);
$body = json_decode(file_get_contents('php://input'), true);

$response = null;

// Rota Yönetimi
if ($path === '/internal/kupon/dogrula' && $requestMethod === 'POST') {
    $response = $service->dogrula($body['kupon_kodu']);
} elseif ($path === '/api/sepet/kupon-dogrula' && $requestMethod === 'POST') {
    $response = $service->dogrula($body['kupon_kodu']);
}
// ... Admin CRUD rotaları buraya eklenecek

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'Kupon-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
