<?php
namespace ProSiparis\Controllers;

use ProSiparis\Service\ReportService;
use ProSiparis\Core\Request;
use PDO;

class ReportController
{
    private ReportService $reportService;

    public function __construct()
    {
        global $pdo;
        $this->reportService = new ReportService($pdo);
    }

    public function olustur(Request $request)
    {
        $filtreler = $request->getQueryParams();
        $raporTipi = $filtreler['rapor_tipi'] ?? 'kar_zarar';

        $sonuc = [];
        if ($raporTipi === 'kar_zarar') {
            $sonuc = $this->reportService->olusturKarZararRaporu($filtreler);
        } else {
            $sonuc = ['basarili' => false, 'kod' => 400, 'mesaj' => 'Geçersiz rapor tipi.'];
        }

        $this->jsonYanitGonder($sonuc);
    }

    private function jsonYanitGonder(array $sonuc): void
    {
        http_response_code($sonuc['kod']);
        if ($sonuc['basarili']) {
            echo json_encode(['durum' => 'basarili', 'veri' => $sonuc['veri']]);
        } else {
            echo json_encode(['durum' => 'hata', 'mesaj' => $sonuc['mesaj']]);
        }
    }
}
