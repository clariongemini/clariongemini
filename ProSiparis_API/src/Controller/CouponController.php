<?php
namespace ProSiparis\Controller;

use ProSiparis\Service\CouponService;
use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;

class CouponController
{
    private CouponService $couponService;

    public function __construct()
    {
        global $pdo;
        $this->couponService = new CouponService($pdo);
    }

    /**
     * POST /api/sepet/kupon-dogrula
     */
    public function dogrula(Request $request): void
    {
        $veri = $request->getBody();
        $kuponKodu = $veri['kupon_kodu'] ?? '';
        $sepetTutari = $veri['mevcut_sepet_tutari'] ?? 0;

        if (empty($kuponKodu) || $sepetTutari <= 0) {
            http_response_code(400);
            echo json_encode(['gecerli' => false, 'mesaj' => 'Kupon kodu ve sepet tutarı gereklidir.']);
            return;
        }

        $sonuc = $this->couponService->kuponDogrula($kuponKodu, (float)$sepetTutari);

        http_response_code($sonuc['gecerli'] ? 200 : 400);
        echo json_encode($sonuc);
    }
}
