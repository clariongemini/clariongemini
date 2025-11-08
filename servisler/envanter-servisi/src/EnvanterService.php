<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class EnvanterService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function stokGuncelle(int $varyantId, string $hareketTipi, int $degisimMiktari, ?int $referansId, ?float $maliyet, ?int $kullaniciId): void
    {
        // ... (mevcut stok güncelleme, AOM hesaplama, ledger'a yazma mantığı)

        // İşlem sonunda, olayı Event Bus'a (olay_gunlugu tablosu) yayınla
        $this->olayYayinla('stok_guncellendi', [
            'varyant_id' => $varyantId,
            'yeni_stok' => $sonrakiStok, // stokGuncelle içindeki hesaplanmış değer
            'yeni_aom' => $yeniAom      // stokGuncelle içindeki hesaplanmış değer
        ]);
    }

    private function olayYayinla(string $olayTipi, array $veri): void
    {
        $sql = "INSERT INTO olay_gunlugu (olay_tipi, veri) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$olayTipi, json_encode($veri)]);
    }
}
