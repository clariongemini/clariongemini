<?php
namespace ProSiparis\Controllers;

use ProSiparis\Service\UrunService;
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;
use PDO;

class UrunController
{
    private UrunService $urunService;

    public function __construct()
    {
        global $pdo;
        $this->urunService = new UrunService($pdo);
    }

    /**
     * GET /api/urunler endpoint'ini yönetir.
     * Kullanıcının fiyat listesine göre ürünleri listeler.
     */
    public function listele(Request $request): void
    {
        // Giriş yapmış kullanıcının fiyat listesini al, yoksa varsayılan (1) kullan
        $fiyatListesiId = Auth::check() ? Auth::user()->fiyat_listesi_id : 1;

        $sonuc = $this->urunService->tumunuGetir($fiyatListesiId);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * GET /api/urunler/{id} endpoint'ini yönetir.
     * Kullanıcının fiyat listesine göre ürün detayını getirir.
     */
    public function detay(Request $request, $params): void
    {
        $id = $params['id'];
        $fiyatListesiId = Auth::check() ? Auth::user()->fiyat_listesi_id : 1;
        $kullaniciId = Auth::check() ? Auth::id() : null;

        $sonuc = $this->urunService->idIleGetir($id, $fiyatListesiId, $kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * POST /api/admin/urunler endpoint'ini yönetir.
     */
    public function olustur(Request $request): void
    {
        $veri = $request->getBody();
        $sonuc = $this->urunService->urunOlustur($veri);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * DELETE /api/admin/urunler/{id} endpoint'ini yönetir.
     */
    public function sil(Request $request, $params): void
    {
        $id = $params['id'];
        $sonuc = $this->urunService->urunSil($id);
        $this->jsonYanitGonder($sonuc);
    }

    // (Diğer metodlar: guncelle, kategoriyeGoreListele, favori işlemleri vb. benzer şekilde güncellenebilir)

    public function favoriyeEkle(Request $request): void
    {
        // Bu metodun güncellenmesine gerek yok.
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
