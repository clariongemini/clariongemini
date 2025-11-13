<?php
namespace FulcrumOS\Service;

use PDO;
use Exception;

class IadeService
{
    private PDO $pdo;
    private EnvanterService $envanterService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->envanterService = new EnvanterService($pdo);
    }

    // ... (diğer metodlar)

    public function iadeTeslimAl(int $iadeId, array $urunler, int $kullaniciId): array
    {
        $this->pdo->beginTransaction();
        try {
            foreach ($urunler as $urun) {
                if ($urun['durum'] === 'Satılabilir') {
                    $stmtAom = $this->pdo->prepare("SELECT agirlikli_ortalama_maliyet FROM urun_varyantlari WHERE varyant_id = ?");
                    $stmtAom->execute([$urun['varyant_id']]);
                    $maliyet = $stmtAom->fetchColumn();

                    $this->envanterService->stokGuncelle($urun['varyant_id'], 'iade_giris', $urun['adet'], $iadeId, (float)$maliyet, $kullaniciId);
                }

                $this->pdo->prepare("UPDATE iade_urunleri SET durum = ? WHERE iade_id = ? AND varyant_id = ?")
                          ->execute([$urun['durum'], $iadeId, $urun['varyant_id']]);
            }
            $this->pdo->prepare("UPDATE iade_talepleri SET durum = 'Depoya Ulaştı' WHERE iade_id = ?")->execute([$iadeId]);
            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'İade teslim alındı ve envanter hareketleri kaydedildi.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'İade teslim alınırken hata: ' . $e->getMessage()];
        }
    }
}
