<?php
namespace FulcrumOS\Controller;

use FulcrumOS\Service\ReviewService;
use FulcrumOS\Core\Request;
use FulcrumOS\Core\Auth;

class ReviewController
{
    private ReviewService $reviewService;

    public function __construct()
    {
        global $pdo;
        $this->reviewService = new ReviewService($pdo);
    }

    public function olustur(Request $request, int $urunId): void
    {
        $kullaniciId = Auth::id();
        if (!$this->reviewService->kullaniciUrunuSatinAldiMi($kullaniciId, $urunId)) {
            $this->jsonYanitGonder(403, null, 'Bu ürünü yorumlamak için önce satın alıp teslim almanız gerekmektedir.');
            return;
        }

        $veri = $request->getBody();
        $sonuc = $this->reviewService->olustur($kullaniciId, $urunId, $veri);
        $this->jsonYanitGonder($sonuc['kod'], null, $sonuc['mesaj']);
    }

    public function listele(Request $request, int $urunId): void
    {
        $sonuc = $this->reviewService->urunDegerlendirmeleriniGetir($urunId);
        $this->jsonYanitGonder($sonuc['kod'], $sonuc['veri']);
    }

    public function sil(Request $request, int $id): void
    {
        $kullanici = Auth::user();
        $sonuc = $this->reviewService->sil($id, $kullanici->kullanici_id, $kullanici->rol);
        $this->jsonYanitGonder($sonuc['kod'], null, $sonuc['mesaj']);
    }

    private function jsonYanitGonder(int $kod, ?array $veri = null, ?string $mesaj = null): void
    {
        http_response_code($kod);
        $response = ['durum' => $kod >= 200 && $kod < 300 ? 'basarili' : 'hata'];
        if ($veri) $response['veri'] = $veri;
        if ($mesaj) $response['mesaj'] = $mesaj;
        echo json_encode($response);
    }
}
