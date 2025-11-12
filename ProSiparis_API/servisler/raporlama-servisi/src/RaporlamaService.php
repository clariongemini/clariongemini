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
        // v6.1: Zengin olaydan gelen verileri doğrudan kullan
        $depoAdi = $veri['depo_adi'] ?? null;

        foreach ($veri['urunler'] as $urun) {
            $adet = $urun['adet'] ?? 1;

            $sql = "INSERT INTO rapor_satis_ozetleri (siparis_id, siparis_tarihi, depo_id, depo_adi, urun_adi, varyant_sku, kategori_adi, musteri_id, adet, birim_fiyat, birim_maliyet) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->pdo->prepare($sql)->execute([
                $veri['siparis_id'],
                $veri['depo_id'],
                $depoAdi,
                $urun['urun_adi'] ?? null,
                $urun['varyant_sku'] ?? null,
                $urun['kategori_adi'] ?? null,
                $veri['kullanici_id'] ?? null,
                $adet,
                $urun['birim_fiyat'] ?? 0.00,
                $urun['maliyet_fiyati'] ?? 0.00 // Bu verinin de olayda olması beklenir
            ]);
        }
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
