<?php
namespace ProSiparis\Controller;

use ProSiparis\Service\UrunService;
use ProSiparis\Service\FileUploadService;
use ProSiparis\Core\Request;

class UrunController
{
    private UrunService $urunService;
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
        // Servisleri manuel olarak oluştur
        $fileUploadService = new FileUploadService();
        $this->urunService = new UrunService($this->pdo, $fileUploadService);
    }

    /**
     * GET /api/urunler endpoint'ini yönetir.
     * @param Request $request
     */
    public function listele(Request $request): void
    {
        $sonuc = $this->urunService->tumunuGetir();
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * GET /api/urunler/{id} endpoint'ini yönetir.
     * @param Request $request
     * @param int $id
     */
    public function detay(Request $request, int $id): void
    {
        $sonuc = $this->urunService->idIleGetir($id);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * POST /api/admin/urunler endpoint'ini yönetir.
     * @param Request $request
     */
    public function olustur(Request $request): void
    {
        $veri = $request->getBody();
        $dosyalar = $request->getFiles();
        $sonuc = $this->urunService->urunOlustur($veri, $dosyalar);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * PUT /api/admin/urunler/{id} endpoint'ini yönetir.
     * @param Request $request
     * @param int $id
     */
    public function guncelle(Request $request, int $id): void
    {
        $veri = $request->getBody();
        $sonuc = $this->urunService->urunGuncelle($id, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * DELETE /api/admin/urunler/{id} endpoint'ini yönetir.
     * @param Request $request
     * @param int $id
     */
    public function sil(Request $request, int $id): void
    {
        $sonuc = $this->urunService->urunSil($id);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * GET /api/kategoriler/{id}/urunler endpoint'ini yönetir.
     * @param Request $request
     * @param int $id
     */
    public function kategoriyeGoreListele(Request $request, int $id): void
    {
        $sonuc = $this->urunService->kategoriyeGoreGetir($id);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * Servis katmanından gelen sonuca göre standart bir JSON yanıtı gönderir.
     * @param array $sonuc
     */
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
