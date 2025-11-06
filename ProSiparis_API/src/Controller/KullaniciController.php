<?php
namespace ProSiparis\Controller;

use ProSiparis\Service\AuthService;
use ProSiparis\Service\KullaniciService;
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;

class KullaniciController
{
    private AuthService $authService;
    private KullaniciService $kullaniciService;
    private \PDO $pdo;

    public function __construct()
    {
        // Gerçek bir uygulamada, bu bağımlılıklar (PDO) bir Dependency Injection Container
        // aracılığıyla enjekte edilirdi. Şimdilik manuel olarak oluşturuyoruz.
        global $pdo; // veritabani_baglantisi.php'den gelen global değişkeni kullan
        $this->pdo = $pdo;
        $this->authService = new AuthService($this->pdo);
        $this->kullaniciService = new KullaniciService($this->pdo);
    }

    /**
     * POST /api/kullanici/kayit endpoint'ini yönetir.
     * @param Request $request
     */
    public function kayitOl(Request $request): void
    {
        $veri = $request->getBody();
        $sonuc = $this->authService->kayitOl($veri);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * POST /api/kullanici/giris endpoint'ini yönetir.
     * @param Request $request
     */
    public function girisYap(Request $request): void
    {
        $veri = $request->getBody();
        $sonuc = $this->authService->girisYap($veri);
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
     * GET /api/kullanici/profil endpoint'ini yönetir.
     * @param Request $request
     */
    public function profilGetir(Request $request): void
    {
        $kullaniciId = Auth::id();
        $sonuc = $this->kullaniciService->profilGetir($kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * PUT /api/kullanici/profil endpoint'ini yönetir.
     * @param Request $request
     */
    public function profilGuncelle(Request $request): void
    {
        $kullaniciId = Auth::id();
        $veri = $request->getBody();
        $sonuc = $this->kullaniciService->profilGuncelle($kullaniciId, $veri);
        $this->jsonYanitGonder($sonuc);
    }
}
