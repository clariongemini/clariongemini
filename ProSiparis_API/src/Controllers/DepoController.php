<?php
namespace ProSiparis\Controllers;

use ProSiparis\Service\DepoService;
use ProSiparis\Core\Request;
use PDO;

class DepoController
{
    private DepoService $depoService;

    public function __construct()
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException("Veritabanı bağlantısı kurulamadı.");
        }
        $this->depoService = new DepoService($pdo);
    }

    /**
     * GET /api/depo/hazirlanacak-siparisler
     */
    public function hazirlanacakSiparisler(Request $request)
    {
        $sonuc = $this->depoService->hazirlanacakSiparisleriListele();
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * GET /api/depo/siparis/{id}/toplama-listesi
     */
    public function toplamaListesi(Request $request, $params)
    {
        $siparisId = $params['id'];
        $sonuc = $this->depoService->toplamaListesiGetir($siparisId);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * POST /api/depo/siparis/{id}/kargoya-ver
     */
    public function kargoyaVer(Request $request, $params)
    {
        $siparisId = $params['id'];
        $veri = $request->getBody();
        $kargoFirmasi = $veri['kargo_firmasi'] ?? '';
        $kargoTakipKodu = $veri['kargo_takip_kodu'] ?? '';

        $sonuc = $this->depoService->kargoyaVer($siparisId, $kargoFirmasi, $kargoTakipKodu);
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
