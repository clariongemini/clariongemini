<?php
namespace FulcrumOS\Controller;

use FulcrumOS\Service\KargoService;

class KargoController
{
    private KargoService $kargoService;

    public function __construct()
    {
        global $pdo;
        $this->kargoService = new KargoService($pdo);
    }

    /**
     * GET /api/kargo-secenekleri
     */
    public function listele(): void
    {
        $sonuc = $this->kargoService->tumunuGetir();
        http_response_code($sonuc['kod']);
        if ($sonuc['basarili']) {
            echo json_encode(['durum' => 'basarili', 'veri' => $sonuc['veri']]);
        } else {
            echo json_encode(['durum' => 'hata', 'mesaj' => $sonuc['mesaj']]);
        }
    }
}
