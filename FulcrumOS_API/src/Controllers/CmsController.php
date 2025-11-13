<?php
namespace FulcrumOS\Controllers;

use FulcrumOS\Service\CmsService;
use FulcrumOS\Core\Request;
use PDO;

class CmsController
{
    private CmsService $cmsService;

    public function __construct()
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException("Veritabanı bağlantısı kurulamadı.");
        }
        $this->cmsService = new CmsService($pdo);
    }

    // --- Herkese Açık Endpoint'ler ---

    public function getSayfa(Request $request, $params)
    {
        $slug = $params['slug'];
        $sonuc = $this->cmsService->getSayfaBySlug($slug);
        $this->jsonYanitGonder($sonuc);
    }

    public function getBannerlar(Request $request)
    {
        $konum = $request->getQueryParams()['konum'] ?? 'anasayfa_ust';
        $sonuc = $this->cmsService->getBannerlarByKonum($konum);
        $this->jsonYanitGonder($sonuc);
    }

    // --- Admin Endpoint'leri ---

    public function listeleSayfalar(Request $request)
    {
        $sonuc = $this->cmsService->listeleSayfalar();
        $this->jsonYanitGonder($sonuc);
    }

    public function olusturSayfa(Request $request)
    {
        $veri = $request->getBody();
        $sonuc = $this->cmsService->olusturSayfa($veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function guncelleSayfa(Request $request, $params)
    {
        $id = $params['id'];
        $veri = $request->getBody();
        $sonuc = $this->cmsService->guncelleSayfa($id, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function silSayfa(Request $request, $params)
    {
        $id = $params['id'];
        $sonuc = $this->cmsService->silSayfa($id);
        $this->jsonYanitGonder($sonuc);
    }

    public function listeleBannerlar(Request $request)
    {
        $sonuc = $this->cmsService->listeleBannerlar();
        $this->jsonYanitGonder($sonuc);
    }

    public function olusturBanner(Request $request)
    {
        $veri = $request->getBody();
        $sonuc = $this->cmsService->olusturBanner($veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function guncelleBanner(Request $request, $params)
    {
        $id = $params['id'];
        $veri = $request->getBody();
        $sonuc = $this->cmsService->guncelleBanner($id, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function silBanner(Request $request, $params)
    {
        $id = $params['id'];
        $sonuc = $this->cmsService->silBanner($id);
        $this->jsonYanitGonder($sonuc);
    }

    private function jsonYanitGonder(array $sonuc)
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
