<?php
namespace ProSiparis\Controllers;

use ProSiparis\Service\DepoService;
use ProSiparis\Service\IadeService;
use ProSiparis\Service\EnvanterService; // Yeni
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;
use PDO;

class DepoController
{
    private DepoService $depoService;
    private IadeService $iadeService;
    private EnvanterService $envanterService; // Yeni

    public function __construct()
    {
        global $pdo;
        $this->depoService = new DepoService($pdo);
        $this->iadeService = new IadeService($pdo);
        $this->envanterService = new EnvanterService($pdo); // Yeni
    }

    // ... (mevcut metodlar)

    /**
     * POST /api/depo/envanter-duzeltme (YENİ)
     */
    public function envanterDuzeltme(Request $request)
    {
        $veri = $request->getBody();
        $varyantId = $veri['varyant_id'] ?? 0;
        $fizikselStok = $veri['fiziksel_stok'] ?? 0;
        $kullaniciId = Auth::id();

        $sonuc = $this->envanterService->stokDuzelt($varyantId, $fizikselStok, $kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    private function jsonYanitGonder(array $sonuc): void { /* ... */ }
}
