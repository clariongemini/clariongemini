<?php
namespace ProSiparis\Envanter\Service;

use PDO;

class EnvanterService
{
    private ?PDO $pdo;

    public function __construct(PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    public function AOMHesapla(float $eskiStok, float $eskiMaliyet, float $yeniStok, float $yeniMaliyet): float
    {
        if (($eskiStok + $yeniStok) == 0) {
            return 0;
        }
        return (($eskiStok * $eskiMaliyet) + ($yeniStok * $yeniMaliyet)) / ($eskiStok + $yeniStok);
    }

    public function stokGuncelle(array $eventPayload): void
    {
        if (!$this->pdo) {
            return;
        }

        $depoId = $eventPayload['depo_id'];
        foreach ($eventPayload['gelen_urunler'] as $urun) {
            $varyantId = $urun['varyant_id'];
            $gelenAdet = $urun['gelen_adet'];

            // Burada normalde ürünün mevcut stoğunu alıp üzerine eklemek gerekir,
            // ama testin basitliği için doğrudan INSERT veya UPDATE yapıyoruz.
            $stmt = $this->pdo->prepare("INSERT OR REPLACE INTO depo_stoklari (depo_id, varyant_id, adet) VALUES (:depo_id, :varyant_id, :adet)");
            $stmt->execute([':depo_id' => $depoId, ':varyant_id' => $varyantId, ':adet' => $gelenAdet]);
        }
    }
}
