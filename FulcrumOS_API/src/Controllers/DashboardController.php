<?php
namespace FulcrumOS\Controllers;

use FulcrumOS\Service\DashboardService;
use PDO;

class DashboardController
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        // Veritabanı bağlantısını global olarak veya bir DI container'dan al
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException("Veritabanı bağlantısı kurulamadı.");
        }
        $this->dashboardService = new DashboardService($pdo);
    }

    /**
     * KPI özet verilerini döndürür.
     * GET /api/admin/dashboard/kpi-ozet
     */
    public function kpiOzet()
    {
        $sonuc = $this->dashboardService->getKpiOzet();
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * Satış grafiği verilerini döndürür.
     * GET /api/admin/dashboard/satis-grafigi
     */
    public function satisGrafigi()
    {
        $sonuc = $this->dashboardService->getSatisGrafigi();
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * En çok satılan ürünler listesini döndürür.
     * GET /api/admin/dashboard/en-cok-satilan-urunler
     */
    public function enCokSatilanUrunler()
    {
        $sonuc = $this->dashboardService->getEnCokSatilanUrunler();
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * Son faaliyetler verilerini döndürür.
     * GET /api/admin/dashboard/son-faaliyetler
     */
    public function sonFaaliyetler()
    {
        $sonuc = $this->dashboardService->getSonFaaliyetler();
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * Gelen sonucu uygun HTTP kodu ve JSON formatında gönderir.
     * @param array $sonuc ['basarili', 'kod', 'veri'/'mesaj']
     */
    private function jsonYanitGonder(array $sonuc)
    {
        http_response_code($sonuc['kod']);
        if ($sonuc['basarili']) {
            echo json_encode(['durum' => 'basarili', 'veri' => $sonuc['veri']]);
        } else {
            echo json_encode(['durum' => 'hata', 'mesaj' => $sonuc['mesaj']]);
        }
    }
}
