<?php
namespace ProSiparis\Controllers;

use ProSiparis\Service\AuthService;
use ProSiparis\Service\KullaniciService;
use ProSiparis\Service\RecommendationService; // Eklendi
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;

class KullaniciController
{
    private AuthService $authService;
    private KullaniciService $kullaniciService;
    private RecommendationService $recommendationService; // Eklendi
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->authService = new AuthService($this->pdo);
        $this->kullaniciService = new KullaniciService($this->pdo);
        $this->recommendationService = new RecommendationService($this->pdo); // Eklendi
    }

    /**
     * POST /api/kullanici/kayit endpoint'ini yönetir.
     */
    public function kayitOl(Request $request): void
    {
        $veri = $request->getBody();
        $sonuc = $this->authService->kayitOl($veri);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * POST /api/kullanici/giris endpoint'ini yönetir.
     */
    public function girisYap(Request $request): void
    {
        $veri = $request->getBody();
        $sonuc = $this->authService->girisYap($veri);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * GET /api/kullanici/profil endpoint'ini yönetir.
     */
    public function profilGetir(Request $request): void
    {
        $kullaniciId = Auth::id();
        $sonuc = $this->kullaniciService->profilGetir($kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * PUT /api/kullanici/profil endpoint'ini yönetir.
     */
    public function profilGuncelle(Request $request): void
    {
        $kullaniciId = Auth::id();
        $veri = $request->getBody();
        $sonuc = $this->kullaniciService->profilGuncelle($kullaniciId, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * GET /api/kullanici/onerilen-urunler endpoint'ini yönetir.
     */
    public function onerilenUrunler(Request $request): void
    {
        $kullaniciId = Auth::id();
        $sonuc = $this->recommendationService->getOnerilenUrunler($kullaniciId);
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
             if (isset($sonuc['not'])) { // RecommendationService'den gelen not için eklendi
                $response['not'] = $sonuc['not'];
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
}
