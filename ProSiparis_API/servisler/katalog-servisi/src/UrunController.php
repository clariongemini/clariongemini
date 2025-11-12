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

    public function internalGetVaryantDetaylari(Request $request): void
    {
        $queryParams = $request->getQueryParams();
        $ids = $queryParams['ids'] ?? '';

        if (empty($ids)) {
            $this.jsonYanitGonder(['basarili' => false, 'kod' => 400, 'mesaj' => 'Varyant ID\'leri (`ids`) eksik.']);
            return;
        }

        $idList = explode(',', $ids);
        // Bu, `getVaryantDetaylari` metodunun birden fazla ID kabul etmesi gerektiğini varsayar.
        // Şimdilik, döngü içinde tek tek çağırarak simüle edelim.
        $sonuclar = [];
        foreach($idList as $id) {
            $sonuc = $this->urunService->getVaryantDetaylari((int)$id);
            if ($sonuc['basarili']) {
                $sonuclar[$id] = $sonuc['veri'];
            }
        }

        $this->jsonYanitGonder(['basarili' => true, 'kod' => 200, 'veri' => $sonuclar]);
    }

    private function jsonYanitGonder(array $sonuc): void { /* ... */ }

    // --- v6.0 Admin CUD Metodları ---

    public function olustur(Request $request): void
    {
        $veri = $request->getBody();
        $sonuc = $this->urunService->urunOlustur($veri);
        $this->jsonYanitGonder($sonuc);
    }

    public function guncelle(Request $request, array $params): void
    {
        $urunId = (int)($params['id'] ?? 0);
        if ($urunId === 0) {
            $this->jsonYanitGonder(['basarili' => false, 'kod' => 400, 'mesaj' => 'Geçerli bir ürün ID\'si gereklidir.']);
            return;
        }
        $veri = $request->getBody();
        $sonuc = $this->urunService->urunGuncelle($urunId, $veri);
        $this->jsonYanitGonder($sonuc);
    }
}
