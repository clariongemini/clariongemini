<?php
namespace ProSiparis\Service;

use PDO;

class UrunService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ... (mevcut public metodlar)

    /**
     * Verilen ID listesindeki varyantların, belirtilen fiyat listesine göre fiyatlarını getirir.
     * @param array $varyantIds
     * @param int $fiyatListesiId
     * @return array
     */
    public function idListesineGoreFiyatlariGetir(array $varyantIds, int $fiyatListesiId): array
    {
        if (empty($varyantIds)) {
            return ['basarili' => true, 'kod' => 200, 'veri' => []];
        }

        $placeholders = rtrim(str_repeat('?,', count($varyantIds)), ',');
        $sql = "
            SELECT vf.varyant_id, vf.fiyat
            FROM varyant_fiyatlari vf
            WHERE vf.varyant_id IN ($placeholders) AND vf.fiyat_listesi_id = ?
        ";

        $params = array_merge($varyantIds, [$fiyatListesiId]);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // Sonucu ['varyant_id' => fiyat] formatında bir diziye dönüştür
        $fiyatlar = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return ['basarili' => true, 'kod' => 200, 'veri' => $fiyatlar];
    }

    public function getTakipYontemiByVaryantId(int $varyantId): array
    {
        $sql = "SELECT u.takip_yontemi FROM urunler u JOIN urun_varyantlari uv ON u.urun_id = uv.urun_id WHERE uv.varyant_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$varyantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Varyant bulunamadı.'];
        }

        return ['basarili' => true, 'kod' => 200, 'veri' => ['takip_yontemi' => $result['takip_yontemi']]];
    }
}
