<?php
// FulcrumOS: Envanter Servisi - API Giriş Noktası

header("Content-Type: application/json; charset=UTF-8");

// --- Ana Yönlendirici (Router) ---
$path = $_SERVER['REQUEST_URI'] ?? '/';

if (preg_match('/\/internal\/stok-durumu\/?$/', $path)) {
    getStokDurumu();
} elseif (preg_match('/\/internal\/envanter\/uygun-depo-bul\/?$/', $path)) {
    getUygunDepo();
} elseif (preg_match('/\/internal\/envanter\/ai-stok-durumu\/?$/', $path)) {
    getAiStokDurumu(); // v10.4 Yeni endpoint
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Endpoint bulunamadı.']);
}

// --- Endpoint Fonksiyonları ---

function getStokDurumu() {
    // Simülasyon: Sipariş veya diğer servisler için stok durumu sağlar
    $response = [
        "basarili" => true,
        "veri" => [
            "123" => [["depo_id" => 1, "stok" => 10], ["depo_id" => 2, "stok" => 150]]
        ]
    ];
    http_response_code(200);
    echo json_encode($response);
}

function getUygunDepo() {
    // Simülasyon: Siparişi karşılayacak en uygun depoyu bulur
    $response = [
        "basarili" => true,
        "veri" => ["depo_id" => 2, "depo_adi" => "İstanbul Deposu"]
    ];
    http_response_code(200);
    echo json_encode($response);
}

/**
 * GET /internal/envanter/ai-stok-durumu
 * v10.4 AI Co-Pilot için anlık stok durumunu sağlar (simülasyon).
 */
function getAiStokDurumu() {
    $response = [
        "anlik_stok_durumu" => [
            ["depo_id" => 1, "depo_adi" => "Ankara Deposu", "varyant_id" => 123, "sku" => "TSHIRT-KRM-L", "mevcut_stok" => 10],
            ["depo_id" => 2, "depo_adi" => "İstanbul Deposu", "varyant_id" => 123, "sku" => "TSHIRT-KRM-L", "mevcut_stok" => 150]
        ]
    ];
    http_response_code(200);
    echo json_encode($response);
}
