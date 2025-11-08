<?php
namespace ProSiparis\Controllers;

use ProSiparis\Service\SiparisService;
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;
use PDO;

class SiparisController
{
    private SiparisService $siparisService;

    public function __construct()
    {
        global $pdo;
        $this->siparisService = new SiparisService($pdo);
    }

    /**
     * GET /api/siparisler (kullanıcı için)
     */
    public function gecmis(Request $request): void
    {
        $kullaniciId = Auth::id();
        $sonuc = $this->siparisService->kullaniciSiparisleriniGetir($kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * GET /api/siparisler/{id} (kullanıcı için)
     */
    public function detay(Request $request, $params): void
    {
        $id = $params['id'];
        $kullaniciId = Auth::id();
        $sonuc = $this->siparisService->idIleGetir($id, $kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * GET /api/admin/siparisler
     */
    public function tumunuListele(Request $request): void
    {
        $sonuc = $this->siparisService->tumSiparisleriGetir();
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * PUT /api/admin/siparisler/{id}
     * Admin'in nihai durumları güncellemesini sağlar.
     */
    public function durumGuncelle(Request $request, $params): void
    {
        $id = $params['id'];
        $veri = $request->getBody();
        $yeniDurum = $veri['durum'] ?? '';

        if (empty($yeniDurum)) {
             $this->jsonYanitGonder(['basarili' => false, 'kod' => 400, 'mesaj' => 'Yeni durum belirtilmelidir.']);
             return;
        }

        $sonuc = $this->siparisService->adminSiparisDurumGuncelle($id, $yeniDurum);
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
