<?php
namespace ProSiparis\Controller;

use ProSiparis\Service\AdresService;
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;

class AdresController
{
    private AdresService $adresService;

    public function __construct()
    {
        global $pdo;
        $this->adresService = new AdresService($pdo);
    }

    public function listele(): void
    {
        $kullaniciId = Auth::id();
        $sonuc = $this->adresService->kullaniciAdresleriniGetir($kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    public function olustur(Request $request): void
    {
        $kullaniciId = Auth::id();
        $veri = $request->getBody();
        $sonuc = $this->adresService->olustur($kullaniciId, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function guncelle(Request $request, int $id): void
    {
        $kullaniciId = Auth::id();
        $veri = $request->getBody();
        $sonuc = $this->adresService->guncelle($id, $kullaniciId, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function sil(int $id): void
    {
        $kullaniciId = Auth::id();
        $sonuc = $this->adresService->sil($id, $kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    private function jsonYanitGonder(array $sonuc): void
    {
        http_response_code($sonuc['kod']);
        $response = ['durum' => $sonuc['basarili'] ? 'basarili' : 'hata'];
        if (isset($sonuc['veri'])) $response['veri'] = $sonuc['veri'];
        if (isset($sonuc['mesaj'])) $response['mesaj'] = $sonuc['mesaj'];
        echo json_encode($response);
    }
}
