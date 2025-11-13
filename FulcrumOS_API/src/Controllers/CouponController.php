<?php
namespace FulcrumOS\Controllers;

use FulcrumOS\Service\CouponService;
use FulcrumOS\Core\Request;

class CouponController
{
    private CouponService $couponService;

    public function __construct()
    {
        global $pdo;
        $this->couponService = new CouponService($pdo);
    }

    /**
     * POST /internal/legacy/kupon-dogrula
     * Sadece servisler arası iletişim için.
     */
    public function internalKuponDogrula(Request $request): void
    {
        $veri = $request->getBody();
        $kuponKodu = $veri['kupon_kodu'] ?? '';
        $sepetTutari = $veri['sepet_tutari'] ?? 0;

        $sonuc = $this->couponService->kuponDogrula($kuponKodu, (float)$sepetTutari);

        // Bu bir internal endpoint olduğu için, sonucu doğrudan JSON olarak basabiliriz.
        header("Content-Type: application/json; charset=UTF-8");
        if ($sonuc['gecerli']) {
            http_response_code(200);
            echo json_encode(['durum' => 'basarili', 'veri' => $sonuc]);
        } else {
            http_response_code(400);
            echo json_encode(['durum' => 'hata', 'mesaj' => $sonuc['mesaj']]);
        }
    }
}
