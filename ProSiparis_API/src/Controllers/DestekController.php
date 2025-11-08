<?php
namespace ProSiparis\Controllers;

use ProSiparis\Service\DestekService;
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;
use PDO;

class DestekController
{
    private DestekService $destekService;

    public function __construct()
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException("Veritabanı bağlantısı kurulamadı.");
        }
        $this->destekService = new DestekService($pdo);
    }

    // --- Kullanıcı Endpoint'leri ---

    public function kullaniciTalepleriniListele(Request $request)
    {
        $kullaniciId = Auth::id();
        $sonuc = $this->destekService->kullaniciTalepleriniListele($kullaniciId);
        $this->jsonYanitGonder($sonuc);
    }

    public function talepOlustur(Request $request)
    {
        $kullaniciId = Auth::id();
        $veri = $request->getBody();
        $sonuc = $this->destekService->talepOlustur($kullaniciId, $veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function talepDetaylariniGetir(Request $request, $params)
    {
        $kullaniciId = Auth::id();
        $talepId = $params['id'];

        // Güvenlik: Kullanıcının sadece kendi talebini görebildiğinden emin ol
        $talep = $this->destekService->talepDetaylariniGetir($talepId);
        if ($talep['basarili'] && $talep['veri']['kullanici_id'] != $kullaniciId) {
             $this->jsonYanitGonder(['basarili' => false, 'kod' => 403, 'mesaj' => 'Bu destek talebini görüntüleme yetkiniz yok.']);
             return;
        }

        $this->jsonYanitGonder($talep);
    }

    public function kullaniciMesajEkle(Request $request, $params)
    {
        $kullaniciId = Auth::id();
        $talepId = $params['id'];
        $mesaj = $request->getBody()['mesaj'] ?? '';

        // Güvenlik: Kullanıcının sadece kendi talebine yazabildiğinden emin ol
        $talep = $this->destekService->talepDetaylariniGetir($talepId);
         if (!$talep['basarili'] || ($talep['basarili'] && $talep['veri']['kullanici_id'] != $kullaniciId)) {
             $this->jsonYanitGonder(['basarili' => false, 'kod' => 403, 'mesaj' => 'Bu destek talebine mesaj ekleme yetkiniz yok.']);
             return;
        }

        $sonuc = $this->destekService->mesajaEkle($talepId, $kullaniciId, $mesaj, false);
        $this->jsonYanitGonder($sonuc);
    }

    // --- Admin Endpoint'leri ---

    public function tumTalepleriListele(Request $request)
    {
        $filtreler = $request->getQueryParams();
        $sonuc = $this->destekService->tumTalepleriListele($filtreler);
        $this->jsonYanitGonder($sonuc);
    }

    public function adminMesajEkle(Request $request, $params)
    {
        $adminId = Auth::id();
        $talepId = $params['id'];
        $mesaj = $request->getBody()['mesaj'] ?? '';

        $sonuc = $this->destekService->mesajaEkle($talepId, $adminId, $mesaj, true);
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
