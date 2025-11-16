<?php
// AI-Asistan-Servisi API Giriş Noktası v6.0

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../src/AiAsistanService.php';

use ProSiparis\Core\Database;
use ProSiparis\AiAsistan\AiAsistanService;

header('Content-Type: application/json');

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

$response = null;
$pdo = Database::getConnection('prosiparis_ai_asistan');
$service = new AiAsistanService($pdo);

// Mevcut Müşteri AI Endpoint'i
if ($path === '/api/asistan/soru-sor' && $requestMethod === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $soru = $data['soru'] ?? '';

    if (empty($soru)) {
        $response = ['basarili' => false, 'kod' => 400, 'mesaj' => '`soru` alanı zorunludur.'];
    } else {
        $response = $service->soruSor($soru);
    }
}
// Yeni Admin AI Co-Pilot Endpoint'i
elseif ($path === '/api/admin/ai-co-pilot/oneriler' && $requestMethod === 'GET') {
    if (!checkAuth('ai_copilot_goruntule')) {
        $response = ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
    } else {
        $response = $service->getCoPilotOnerileri();
    }
}


if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'AI-Asistan-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);

/**
 * Yetki kontrolü simülasyonu.
 */
function checkAuth($gerekliYetki) {
    $permissionsHeader = $_SERVER['HTTP_X_USER_PERMISSIONS'] ?? '';
    if (empty($permissionsHeader)) return true; // Simülasyon ortamı için varsayılan
    $userPermissions = explode(',', $permissionsHeader);
    return in_array($gerekliYetki, $userPermissions);
}
