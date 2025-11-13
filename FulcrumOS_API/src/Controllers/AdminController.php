<?php
namespace FulcrumOS\Controllers;

use FulcrumOS\Service\IadeService;
use FulcrumOS\Core\Request;
use PDO;

class AdminController
{
    private IadeService $iadeService;

    public function __construct()
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException("Veritabanı bağlantısı kurulamadı.");
        }
        $this->iadeService = new IadeService($pdo);
    }

    public function listeleIadeTalepleri(Request $request)
    {
        $sonuc = $this->iadeService->listeleTalepler($request->getQueryParams());
        $this->jsonYanitGonder($sonuc);
    }

    public function iadeDurumGuncelle(Request $request, $params)
    {
        $iadeId = $params['id'];
        $yeniDurum = $request->getBody()['durum'] ?? '';
        $sonuc = $this->iadeService->iadeDurumGuncelle($iadeId, $yeniDurum);
        $this->jsonYanitGonder($sonuc);
    }

    public function iadeOdemeYap(Request $request, $params)
    {
        $iadeId = $params['id'];
        $sonuc = $this->iadeService->iadeOdemeYap($iadeId);
        $this->jsonYanitGonder($sonuc);
    }

    private function jsonYanitGonder(array $sonuc): void
    {
        http_response_code($sonuc['kod']);
        if ($sonuc['basarili']) {
            $response = ['durum' => 'basarili'];
            if (isset($sonuc['veri'])) $response['veri'] = $sonuc['veri'];
            if (isset($sonuc['mesaj'])) $response['mesaj'] = $sonuc['mesaj'];
            echo json_encode($response);
        } else {
            echo json_encode(['durum' => 'hata', 'mesaj' => $sonuc['mesaj']]);
        }
    }
}
