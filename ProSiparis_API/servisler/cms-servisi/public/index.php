<?php
// CMS-Servisi API Giriş Noktası v4.3

header('Content-Type: application/json');

// Veritabanı bağlantısı ve servis kurulumu...
require_once __DIR__ . '/../src/CmsService.php';
$service = new \ProSiparis\Cms\CmsService($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$response = null;

// Public Rotalar
if ($path === '/api/bannerlar' && $requestMethod === 'GET') {
    $response = $service->listeleAktifBannerlar();
} elseif (preg_match('/^\/api\/sayfa\/([a-z0-9-]+)\/?$/', $path, $matches)) {
    if ($requestMethod === 'GET') {
        $response = $service->getSayfaBySlug($matches[1]);
    }
}
// Admin Rotaları
elseif ($path === '/api/admin/sayfalar' && $requestMethod === 'GET') {
    $response = $service->listeleSayfalar();
}
// ... Diğer Admin CRUD rotaları

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'CMS-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
