<?php
namespace ProSiparis\Service;

require_once __DIR__ . '/../../../core/EventBusService.php';

use PDO;
use ProSiparis\Core\EventBusService;

class UrunService
{
    private PDO $pdo;
    private EventBusService $eventBus;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->eventBus = new EventBusService();
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

    public function getVaryantDetaylari(int $varyantId): array
    {
        $sql = "
            SELECT
                u.urun_adi,
                uv.varyant_sku,
                k.kategori_adi
            FROM urun_varyantlari uv
            JOIN urunler u ON uv.urun_id = u.urun_id
            LEFT JOIN kategoriler k ON u.kategori_id = k.kategori_id
            WHERE uv.varyant_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$varyantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Varyant bulunamadı.'];
        }

        return ['basarili' => true, 'kod' => 200, 'veri' => $result];
    }

    // --- v6.0 CUD Metodları ve Olay Yayınlama ---

    public function urunOlustur(array $veri): array
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO urunler (urun_adi, kategori_id, takip_yontemi, meta_baslik, meta_aciklama, slug, marka) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $veri['urun_adi'],
                $veri['kategori_id'] ?? null,
                $veri['takip_yontemi'] ?? 'adet',
                $veri['meta_baslik'] ?? null,
                $veri['meta_aciklama'] ?? '',
                $veri['slug'],
                $veri['marka'] ?? null
            ]);
            $urunId = $this->pdo->lastInsertId();
            $this->pdo->commit();

            // İşlem başarılı olduktan sonra, bu ürüne bağlı tüm varyantlar için olay yayınla
            $this->varyantlarIcinOlayYayinla($urunId, $veri, 'katalog.varyant.yaratildi');

            return ['basarili' => true, 'kod' => 201, 'veri' => ['urun_id' => $urunId]];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün oluşturulurken hata: ' . $e->getMessage()];
        }
    }

    public function urunGuncelle(int $urunId, array $veri): array
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE urunler SET urun_adi = ?, kategori_id = ?, meta_baslik = ?, meta_aciklama = ?, slug = ?, marka = ? WHERE urun_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $veri['urun_adi'],
                $veri['kategori_id'] ?? null,
                $veri['meta_baslik'] ?? null,
                $veri['meta_aciklama'] ?? '',
                $veri['slug'],
                $veri['marka'] ?? null,
                $urunId
            ]);
            $this->pdo->commit();

            // İşlem başarılı olduktan sonra, bu ürüne bağlı tüm varyantlar için olay yayınla
            $this->varyantlarIcinOlayYayinla($urunId, $veri, 'katalog.varyant.guncellendi');

            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Ürün başarıyla güncellendi.'];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün güncellenirken hata: ' . $e->getMessage()];
        }
    }

    private function varyantlarIcinOlayYayinla(int $urunId, array $urunVeri, string $olayAdi): void
    {
        $stmt = $this->pdo->prepare("SELECT varyant_id FROM urun_varyantlari WHERE urun_id = ?");
        $stmt->execute([$urunId]);
        $varyantlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($varyantlar as $varyant) {
            $olayVerisi = [
                'varyant_id' => $varyant['varyant_id'],
                'urun_adi' => $urunVeri['urun_adi'],
                'aciklama' => $urunVeri['meta_aciklama'] ?? '',
                'kategori_id' => $urunVeri['kategori_id'] ?? null
            ];
            $this->eventBus->publish($olayAdi, $olayVerisi);
        }
    }
}
