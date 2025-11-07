<?php
namespace ProSiparis\Controller;

use ProSiparis\Service\SiparisService;
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;

class SiparisController
{
    private SiparisService $siparisService;
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->siparisService = new SiparisService($this->pdo);
    }

    /**
     * GET /api/siparisler endpoint'ini yönetir.
     * @param Request $request
     */
    public function gecmis(Request $request): void
    {
        // AuthMiddleware bu endpoint'ten önce çalıştığı için Auth::id() güvenilirdir.
        $kullaniciId = Auth::id();
        $sonuc = $this->siparisService->kullaniciSiparisleriniGetir($kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * POST /api/siparisler endpoint'ini yönetir.
     * @param Request $request
     */
    public function olustur(Request $request): void
    {
        $kullaniciId = Auth::id();
        $veri = $request->getBody();
        $sonuc = $this->siparisService->siparisOlustur($kullaniciId, $veri);
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
             $response = ['durum' => 'basarili'];
             if (isset($sonuc['veri'])) {
                $response['veri'] = $sonuc['veri'];
             }
             if (isset($sonuc['mesaj'])) {
                $response['mesaj'] = $sonuc['mesaj'];
             }
             echo json_encode($response);
        } else {
            echo json_encode([
                'durum' => 'hata',
                'mesaj' => $sonuc['mesaj']
            ]);
        }
    }

    /**
     * GET /api/admin/siparisler endpoint'ini yönetir.
     * @param Request $request
     */
    public function tumunuListele(Request $request): void
    {
        $sonuc = $this->siparisService->tumSiparisleriGetir();
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * PUT /api/admin/siparisler/{id} endpoint'ini yönetir.
     * @param Request $request
     * @param int $id
     */
    public function durumGuncelle(Request $request, int $id): void
    {
        $veri = $request->getBody();
        $sonuc = $this->siparisService->siparisDurumGuncelle($id, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * GET /api/siparisler/{id}
     */
    public function detay(Request $request, int $id): void
    {
        $kullaniciId = Auth::id();
        $sonuc = $this->siparisService->idIleGetir($id, $kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }
}
