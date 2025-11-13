<?php
namespace FulcrumOS\Controllers;

use FulcrumOS\Service\PaymentService;
use FulcrumOS\Service\SiparisService;
use FulcrumOS\Service\MailService;
use FulcrumOS\Core\Request;
use FulcrumOS\Core\Auth;
use PDO;

class OdemeController
{
    private PaymentService $paymentService;

    public function __construct()
    {
        global $pdo;
        $this->paymentService = new PaymentService($pdo);
    }

    /**
     * POST /api/odeme/baslat
     */
    public function baslat(Request $request): void
    {
        $kullaniciId = Auth::id();
        $fiyatListesiId = Auth::user()->fiyat_listesi_id; // JWT'den fiyat listesini al
        $veri = $request->getBody();

        $sonuc = $this->paymentService->odemeBaslat($kullaniciId, $fiyatListesiId, $veri);

        http_response_code($sonuc['kod']);
        if ($sonuc['basarili']) {
            echo json_encode(['durum' => 'basarili', 'veri' => $sonuc['veri']]);
        } else {
            echo json_encode(['durum' => 'hata', 'mesaj' => $sonuc['mesaj']]);
        }
    }

    /**
     * POST /api/odeme/callback/iyzico
     */
    public function callback(Request $request): void
    {
        $iyzicoResponse = $_POST;
        $sonuc = $this->paymentService->callbackDogrula($iyzicoResponse);

        if ($sonuc['basarili']) {
            $siparisVerisi = $sonuc['veri'];

            global $pdo;
            $siparisService = new SiparisService($pdo);

            // SiparisService'i yeni fiyat_listesi_id parametresiyle çağır
            $siparisSonuc = $siparisService->siparisOlustur(
                $siparisVerisi['kullanici_id'],
                $siparisVerisi['fiyat_listesi_id'], // Yeni eklendi
                $siparisVerisi['sepet'],
                $siparisVerisi['adresler']['teslimat_adresi_id'],
                $siparisVerisi['kargo_id'],
                $siparisVerisi['kullanilan_kupon_kodu'] ?? null,
                $siparisVerisi['indirim_tutari'] ?? 0
            );

            if ($siparisSonuc['basarili']) {
                // ... (E-posta gönderimi)
                http_response_code(200);
                echo "OK";
            } else {
                // ... (Kritik hata loglama)
                http_response_code(500);
                echo "INTERNAL_ORDER_CREATION_FAILURE";
            }
        } else {
            // ... (Callback hata loglama)
            http_response_code(200);
            echo "OK";
        }
    }
}
