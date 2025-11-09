<?php
namespace ProSiparis\Controllers; // Varsayılan namespace

use ProSiparis\Service\UrunService;
use ProSiparis\Core\Request;

class UrunController
{
    private UrunService $urunService;

    public function __construct()
    {
        global $pdo; // Bu, her servisin kendi veritabanı bağlantısını yönetmesiyle değiştirilmeli
        $this->urunService = new UrunService($pdo);
    }

    // ... (mevcut public metodlar: listele, detay)

    /**
     * GET /internal/katalog/varyantlar
     * Sadece servisler arası iletişim için.
     * Verilen ID'lere sahip varyantların fiyatlarını döndürür.
     */
    public function internalVaryantlariGetir(Request $request): void
    {
        $queryParams = $request->getQueryParams();
        $ids = $queryParams['ids'] ?? '';
        $fiyatListesiId = $queryParams['fiyatListesiId'] ?? 1;

        if (empty($ids)) {
            $this->jsonYanitGonder(['basarili' => false, 'kod' => 400, 'mesaj' => 'Varyant ID\'leri eksik.']);
            return;
        }

        $idList = explode(',', $ids);
        $sonuc = $this->urunService->idListesineGoreFiyatlariGetir($idList, (int)$fiyatListesiId);
        $this->jsonYanitGonder($sonuc);
    }

    public function internalGetTakipYontemi(Request $request): void
    {
        $queryParams = $request->getQueryParams();
        $varyantId = $queryParams['varyant_id'] ?? null;

        if (empty($varyantId)) {
            $this->jsonYanitGonder(['basarili' => false, 'kod' => 400, 'mesaj' => 'varyant_id parametresi zorunludur.']);
            return;
        }

        $sonuc = $this->urunService->getTakipYontemiByVaryantId((int)$varyantId);
        $this->jsonYanitGonder($sonuc);
    }

    public function internalGetVaryantDetaylari(Request $request, array $params): void
    {
        $varyantId = $params['id'] ?? null;
        if (empty($varyantId)) {
            $this->jsonYanitGonder(['basarili' => false, 'kod' => 400, 'mesaj' => 'Varyant ID eksik.']);
            return;
        }

        $sonuc = $this->urunService->getVaryantDetaylari((int)$varyantId);
        $this->jsonYanitGonder($sonuc);
    }

    private function jsonYanitGonder(array $sonuc): void { /* ... */ }
}
