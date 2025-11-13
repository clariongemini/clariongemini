<?php
namespace FulcrumOS\Controllers;

use FulcrumOS\Service\TedarikService;
use FulcrumOS\Core\Request;
use PDO;

class TedarikController
{
    private TedarikService $tedarikService;

    public function __construct()
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException("Veritabanı bağlantısı kurulamadı.");
        }
        $this->tedarikService = new TedarikService($pdo);
    }

    // --- Tedarikçi CRUD ---

    public function listeleTedarikciler(Request $request)
    {
        $sonuc = $this->tedarikService->listeleTedarikciler();
        $this->jsonYanitGonder($sonuc);
    }

    public function olusturTedarikci(Request $request)
    {
        $veri = $request->getBody();
        $sonuc = $this->tedarikService->olusturTedarikci($veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function guncelleTedarikci(Request $request, $params)
    {
        $id = $params['id'];
        $veri = $request->getBody();
        $sonuc = $this->tedarikService->guncelleTedarikci($id, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function silTedarikci(Request $request, $params)
    {
        $id = $params['id'];
        $sonuc = $this->tedarikService->silTedarikci($id);
        $this->jsonYanitGonder($sonuc);
    }

    // --- Satın Alma Siparişi (PO) CRUD ---

    public function listeleSatinAlmaSiparisleri(Request $request)
    {
        $sonuc = $this->tedarikService->listeleSatinAlmaSiparisleri();
        $this->jsonYanitGonder($sonuc);
    }

    public function olusturSatinAlmaSiparisi(Request $request)
    {
        $veri = $request->getBody();
        $sonuc = $this->tedarikService->olusturSatinAlmaSiparisi($veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function guncelleSatinAlmaSiparisi(Request $request, $params)
    {
        $id = $params['id'];
        $veri = $request->getBody();
        $sonuc = $this->tedarikService->guncelleSatinAlmaSiparisi($id, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    private function jsonYanitGonder(array $sonuc)
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
