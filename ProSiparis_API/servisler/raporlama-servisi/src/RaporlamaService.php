<?php
namespace ProSiparis\Raporlama;

use PDO;
use Exception;

class RaporlamaService
{
    private PDO $pdo;
    private PDO $corePdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->corePdo = new PDO('mysql:host=db;dbname=prosiparis_core', 'user', 'password');
        $this->corePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function olaylariIsle(): array
    {
        $olayTipleri = ['siparis.kargolandi', 'tedarik.mal_kabul_yapildi', 'iade.stoga_geri_alindi'];
        $placeholders = rtrim(str_repeat('?,', count($olayTipleri)), ',');

        $sql = "SELECT olay_id, olay_tipi, veri FROM olay_gunlugu WHERE olay_tipi IN ($placeholders) AND islendi_raporlama = 0 ORDER BY olay_id ASC LIMIT 100";
        $stmt = $this->corePdo->prepare($sql);
        $stmt->execute($olayTipleri);
        $olaylar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $islenen_olay_sayisi = 0;
        foreach ($olaylar as $olay) {
            $this->pdo->beginTransaction();
            try {
                $veri = json_decode($olay['veri'], true);

                if ($olay['olay_tipi'] === 'siparis.kargolandi') {
                    $this->satisOzetiniIsle($veri);
                }

                $this->corePdo->prepare("UPDATE olay_gunlugu SET islendi_raporlama = 1 WHERE olay_id = ?")->execute([$olay['olay_id']]);
                $this->pdo->commit();
                $islenen_olay_sayisi++;
            } catch (Exception $e) {
                $this->pdo->rollBack();
                error_log("Raporlama olayı işlenirken hata (ID: {$olay['olay_id']}): " . $e->getMessage());
            }
        }
        return ['islenen_olay_sayisi' => $islenen_olay_sayisi];
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
