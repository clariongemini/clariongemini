<?php
namespace ProSiparis\Raporlama;

use PDO;
use Exception;

class RaporlamaService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * v5.1: RabbitMQ'dan gelen tek bir olayı işler.
     */
    public function tekOlayIsle(string $olayTipi, array $veri): void
    {
        $this->pdo->beginTransaction();
        try {
            if ($olayTipi === 'siparis.kargolandi') {
                $this->satisOzetiniIsle($veri);
            }
            // Gelecekte diğer olay tipleri için de işlem eklenebilir.

            $this->pdo->commit();
            echo "Raporlama olayı başarıyla işlendi: $olayTipi" . PHP_EOL;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Raporlama olayı işlenirken hata ($olayTipi): " . $e->getMessage());
        }
    }

    private function satisOzetiniIsle(array $veri): void
    {
        $depoDetaylari = $this->getDepoDetaylari($veri['depo_id']);

        foreach ($veri['urunler'] as $urun) {
            $urunDetaylari = $this->getUrunDetaylari($urun['varyant_id']);
            $adet = $urun['adet'] ?? 1;

            $sql = "INSERT INTO rapor_satis_ozetleri (siparis_id, siparis_tarihi, depo_id, depo_adi, urun_adi, varyant_sku, kategori_adi, musteri_id, adet, birim_fiyat, birim_maliyet) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->pdo->prepare($sql)->execute([
                $veri['siparis_id'], $veri['depo_id'],
                $depoDetaylari['depo_adi'] ?? null,
                $urunDetaylari['urun_adi'] ?? null,
                $urunDetaylari['varyant_sku'] ?? null,
                $urunDetaylari['kategori_adi'] ?? null,
                $veri['kullanici_id'] ?? null,
                $adet,
                $urun['birim_fiyat'] ?? 0.00, // Bu verilerin olayda olması gerekir
                $urun['maliyet_fiyati'] ?? 0.00 // Bu verilerin olayda olması gerekir
            ]);
        }
    }

    private function getDepoDetaylari(int $depoId): array
    {
        $url = "http://organizasyon-servisi/api/organizasyon/depolar/" . $depoId;
        $response = @json_decode(file_get_contents($url), true);
        return ($response && $response['basarili']) ? $response['veri'] : [];
    }

    private function getUrunDetaylari(int $varyantId): array
    {
        $url = "http://katalog-servisi/internal/varyant-detaylari/" . $varyantId;
        $response = @json_decode(file_get_contents($url), true);
        return ($response && $response['basarili']) ? $response['veri'] : [];
    }

    public function getSatisRaporu(array $filtreler): array
    {
        // ... (getSatisRaporu mantığı - önceki adımla aynı)
    }

    public function getKpiOzet(): array
    {
        // ... (getKpiOzet mantığı - önceki adımla aynı)
    }
}
