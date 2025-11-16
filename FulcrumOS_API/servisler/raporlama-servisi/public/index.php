<?php
// FulcrumOS: Raporlama Servisi - API Giriş Noktası

header("Content-Type: application/json; charset=UTF-8");

// --- Ana Yönlendirici (Router) ---
$path = $_SERVER['REQUEST_URI'] ?? '/';

if (preg_match('/\/api\/admin\/raporlar\/?$/', $path)) {
    getGenelRapor();
} elseif (preg_match('/\/api\/admin\/dashboard\/kpi-ozet\/?$/', $path)) {
    getKpiOzet();
} elseif (preg_match('/\/api\/admin\/dashboard\/satis-grafigi\/?$/', $path)) {
    getSatisGrafigi();
} elseif (preg_match('/\/internal\/raporlama\/ai-satis-verisi\/?$/', $path)) {
    getAiSatisVerisi(); // v10.4 Yeni endpoint
} else {
    http_response_code(444);
    echo json_encode(['status' => 'error', 'message' => 'Endpoint bulunamadı.']);
}

// --- Endpoint Fonksiyonları ---

function getGenelRapor() {
    // Simülasyon: Genel raporlama verileri
    $response = [
        "veri" => [
            "rapor_adi" => "Aylık Satış Raporu",
            "donem" => "Kasım 2025",
            "olusturulma_tarihi" => date('c'),
            "detaylar" => [
                "toplam_urun_satisi" => 1200,
                "en_cok_satan_kategori" => "T-Shirt"
            ]
        ]
    ];
    http_response_code(200);
    echo json_encode($response);
}

function getKpiOzet() {
    // Simülasyon: Dashboard KPI verileri
    $response = [
        "veri" => [
            "toplam_ciro" => "1,250,000 TL",
            "aylik_ciro" => "150,000 TL",
            "toplam_siparis" => 540,
            "yeni_musteri" => 85
        ]
    ];
    http_response_code(200);
    echo json_encode($response);
}

function getSatisGrafigi() {
    // Simülasyon: Dashboard grafik verileri
    $response = [
        "veri" => [
            ["tarih" => "2025-10-17", "toplam_satis" => 4500],
            ["tarih" => "2025-10-18", "toplam_satis" => 5200],
            // ... diğer günler
            ["tarih" => "2025-11-16", "toplam_satis" => 7800]
        ]
    ];
    http_response_code(200);
    echo json_encode($response);
}

/**
 * GET /internal/raporlama/ai-satis-verisi
 * v10.4 AI Co-Pilot için son 48 saatin satış verisini sağlar (simülasyon).
 */
function getAiSatisVerisi() {
    $response = [
        "baslangic_tarihi" => date('c', strtotime('-48 hours')),
        "bitis_tarihi" => date('c'),
        "depo_bazli_satislar" => [
            ["depo_id" => 1, "depo_adi" => "Ankara Deposu", "varyant_id" => 123, "sku" => "TSHIRT-KRM-L", "satilan_adet" => 50],
            ["depo_id" => 2, "depo_adi" => "İstanbul Deposu", "varyant_id" => 123, "sku" => "TSHIRT-KRM-L", "satilan_adet" => 5]
        ]
    ];
    http_response_code(200);
    echo json_encode($response);
}
