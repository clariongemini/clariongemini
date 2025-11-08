<?php
namespace ProSiparis\Controllers;

use ProSiparis\Service\SepetService;
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;
use PDO;

class SepetController
{
    private SepetService $sepetService;

    public function __construct()
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException("Veritabanı bağlantısı kurulamadı.");
        }
        $this->sepetService = new SepetService($pdo);
    }

    /**
     * GET /api/sepet endpoint'ini yönetir.
     * Kullanıcının sepetindeki ürünleri listeler.
     */
    public function sepetiGetir(Request $request)
    {
        $kullaniciId = Auth::id();
        $sonuc = $this->sepetService->sepetiGetir($kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    /**
     * POST /api/sepet/guncelle endpoint'ini yönetir.
     * Kullanıcının sepetini günceller.
     * Body formatı: { "urunler": { "varyant_id_1": adet, "varyant_id_2": adet } }
     */
    public function sepetiGuncelle(Request $request)
    {
        $kullaniciId = Auth::id();
        $veri = $request->getBody();
        $urunler = $veri['urunler'] ?? [];

        $sonuc = $this->sepetService->sepetiGuncelle($kullaniciId, $urunler);
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
