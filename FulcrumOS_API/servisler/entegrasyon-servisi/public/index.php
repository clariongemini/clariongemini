<?php
// FulcrumOS v10.3: Entegrasyon Servisi - API Giriş Noktası

header("Content-Type: application/json; charset=UTF-8");

// Veritabanı bağlantısını kur
try {
    require_once __DIR__ . '/../../../core/db.php';
    $pdo = connect_db();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Veritabanı bağlantı hatası.']);
    exit;
}

// Yetki kontrolü
if (!checkAuth('entegrasyon_yonet')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit;
}

// --- Ana Yönlendirici (Router) ---
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'] ?? '/';

// Gelen isteği doğru fonksiyona yönlendir
if ($method === 'GET' && preg_match('/\/api\/admin\/entegrasyonlar\/muhasebe-loglari\/?$/', $path)) {
    getMuhasebeLoglari($pdo);
} elseif ($method === 'POST' && preg_match('/\/api\/admin\/entegrasyonlar\/muhasebe-loglari\/(\d+)\/tekrar-dene\/?$/', $path, $matches)) {
    $logId = (int)$matches[1];
    retryMuhasebeLogu($pdo, $logId);
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Endpoint bulunamadı.']);
}

// --- Endpoint Fonksiyonları ---

function getMuhasebeLoglari($pdo) {
    try {
        $stmt = $pdo->query("SELECT log_id, olay_tipi, referans_id, durum, son_deneme_tarihi, hata_mesaji, olusturma_tarihi FROM muhasebe_loglari ORDER BY olusturma_tarihi DESC");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $logs]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Loglar alınırken bir hata oluştu: ' . $e->getMessage()]);
    }
}

function retryMuhasebeLogu($pdo, $logId) {
    if ($logId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz log ID.']);
        return;
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE muhasebe_loglari SET durum = 'beklemede', deneme_sayisi = 0, hata_mesaji = NULL, son_deneme_tarihi = NOW() WHERE log_id = ? AND durum = 'hata'"
        );
        $stmt->execute([$logId]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => "Log #$logId başarıyla tekrar denemeye alındı."]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => "Hatalı durumda olan Log #$logId bulunamadı veya güncellenemedi."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Log güncellenirken bir hata oluştu: ' . $e->getMessage()]);
    }
}

function checkAuth($gerekliYetki) {
    $permissionsHeader = $_SERVER['HTTP_X_USER_PERMISSIONS'] ?? '';
    if (empty($permissionsHeader)) return true;
    $userPermissions = explode(',', $permissionsHeader);
    return in_array($gerekliYetki, $userPermissions);
}
