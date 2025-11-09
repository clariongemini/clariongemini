<?php
namespace ProSiparis\Controllers;

use ProSiparis\Service\AuthService;
use ProSiparis\Service\KullaniciService;
use ProSiparis\Service\RecommendationService;
use ProSiparis\Service\IadeService;
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;
use PDO;

class KullaniciController
{
    private AuthService $authService;
    private KullaniciService $kullaniciService;
    private RecommendationService $recommendationService;
    private IadeService $iadeService;

    public function __construct()
    {
        global $pdo;
        $this->authService = new AuthService($pdo);
        $this->kullaniciService = new KullaniciService($pdo);
        $this->recommendationService = new RecommendationService($pdo);
        $this->iadeService = new IadeService($pdo);
    }

    public function kayitOl(Request $request): void { /* ... */ }
    public function girisYap(Request $request): void { /* ... */ }
    public function profilGetir(Request $request): void { /* ... */ }
    public function profilGuncelle(Request $request): void { /* ... */ }
    public function onerilenUrunler(Request $request): void { /* ... */ }

    public function iadeTalebiOlustur(Request $request): void
    {
        $kullaniciId = Auth::id();
        $veri = $request->getBody();
        $sonuc = $this->iadeService->iadeTalebiOlustur($kullaniciId, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function iadeTalepleriniListele(Request $request): void
    {
        $kullaniciId = Auth::id();
        $sonuc = $this->iadeService->kullaniciTalepleriniListele($kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * Dahili servis kullanımı için kullanıcı bilgilerini ID ile getirir.
     * Bu endpoint'in gateway tarafından korunuyor olması gerekir.
     */
    public function dahiliKullaniciGetir(Request $request, array $params): void
    {
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->jsonYanitGonder(['basarili' => false, 'kod' => 400, 'mesaj' => 'Geçersiz kullanıcı ID.']);
            return;
        }

        $kullaniciId = (int)$params['id'];
        $sonuc = $this->kullaniciService->profilGetir($kullaniciId);
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
