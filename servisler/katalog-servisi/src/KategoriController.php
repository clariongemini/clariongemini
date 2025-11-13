<?php
namespace FulcrumOS\Controller;

use FulcrumOS\Service\KategoriService;
use FulcrumOS\Core\Request;

class KategoriController
{
    private KategoriService $kategoriService;
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->kategoriService = new KategoriService($this->pdo);
    }

    /**
     * GET /api/kategoriler (Herkese açık)
     */
    public function listele(): void
    {
        $sonuc = $this->kategoriService->tumunuGetir();
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * POST /api/admin/kategoriler
     */
    public function olustur(Request $request): void
    {
        $veri = $request->getBody();
        $sonuc = $this->kategoriService->olustur($veri);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * PUT /api/admin/kategoriler/{id}
     */
    public function guncelle(Request $request, int $id): void
    {
        $veri = $request->getBody();
        $sonuc = $this->kategoriService->guncelle($id, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * DELETE /api/admin/kategoriler/{id}
     */
    public function sil(Request $request, int $id): void
    {
        $sonuc = $this->kategoriService->sil($id);
        $this->jsonYanitGonder($sonuc);
    }

    private function jsonYanitGonder(array $sonuc): void
    {
        http_response_code($sonuc['kod']);
        $response = ['durum' => $sonuc['basarili'] ? 'basarili' : 'hata'];
        if (isset($sonuc['veri'])) {
            $response['veri'] = $sonuc['veri'];
        }
        if (isset($sonuc['mesaj'])) {
            $response['mesaj'] = $sonuc['mesaj'];
        }
        echo json_encode($response);
    }
}
