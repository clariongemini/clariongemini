<?php
namespace ProSiparis\Controller;

use ProSiparis\Service\PaymentService;
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;

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
        $veri = $request->getBody();
        $sonuc = $this->paymentService->odemeBaslat($kullaniciId, $veri);

        http_response_code($sonuc['kod']);
        if ($sonuc['basarili']) {
            echo json_encode(['durum' => 'basarili', 'veri' => $sonuc['veri']]);
        } else {
            echo json_encode(['durum' => 'hata', 'mesaj' => $sonuc['mesaj']]);
        }
    }

    /**
     * POST /api/odeme/callback/iyzico
     * Bu metot Iyzico'dan gelen geri aramayı işleyecek.
     */
    public function callback(Request $request): void
    {
        $iyzicoResponse = $_POST; // Iyzico POST olarak döner
        $sonuc = $this->paymentService->callbackDogrula($iyzicoResponse);

        if ($sonuc['basarili']) {
            // Ödeme başarılı, şimdi doğrulanmış verilerle siparişi oluştur.
            $siparisVerisi = $sonuc['veri'];

            global $pdo;
            $siparisService = new \ProSiparis\Service\SiparisService($pdo);
            // SiparisService'i yeni veri yapısıyla çağır
            $siparisSonuc = $siparisService->siparisOlustur(
                $siparisVerisi['kullanici_id'],
                $siparisVerisi['sepet'],
                $siparisVerisi['adresler']['teslimat_adresi_id'],
                $siparisVerisi['kargo_id']
            );

            if ($siparisSonuc['basarili']) {
                http_response_code(200);
                echo "OK";
            } else {
                error_log("Iyzico ödemesi başarılı ancak sipariş oluşturulamadı: " . $siparisSonuc['mesaj']);
                http_response_code(500);
                echo "ORDER_CREATION_FAILED";
            }
        } else {
            error_log("Iyzico callback hatası: " . $sonuc['mesaj']);
            http_response_code(200);
            echo "OK";
        }
    }
}
