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

if ($path === '/api/asistan/soru-sor' && $requestMethod === 'POST') {
    $pdo = Database::getConnection('prosiparis_ai_asistan');
    $service = new AiAsistanService($pdo);

    $data = json_decode(file_get_contents('php://input'), true);
    $soru = $data['soru'] ?? '';

    if (empty($soru)) {
        $response = ['basarili' => false, 'kod' => 400, 'mesaj' => '`soru` alanı zorunludur.'];
    } else {
        $response = $service->soruSor($soru);
    }
}

if ($response === null) {
    http_response_code(404);
    $response = ['basarili' => false, 'mesaj' => 'AI-Asistan-Servisi endpoint bulunamadı.'];
}

http_response_code($response['kod'] ?? 200);
echo json_encode($response);
