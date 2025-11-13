<?php
namespace FulcrumOS\Service;

use PDO;
use Exception;

class EnvanterService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Event Bus'taki envanterle ilgili olayları işler.
     * Bu metod, bir cron job veya worker tarafından periyodik olarak çağrılmalıdır.
     */
    public function olaylariIsle(): array
    {
        // Sadece 'siparis.kargolandi' olaylarını dinle
        $stmt = $this->pdo->prepare("SELECT olay_id, veri FROM olay_gunlugu WHERE olay_tipi = 'siparis.kargolandi' AND islendi = 0 ORDER BY olay_id ASC");
        $stmt->execute();
        $olaylar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $islenen_olay_sayisi = 0;
        foreach ($olaylar as $olay) {
            $this->pdo->beginTransaction();
            try {
                $veri = json_decode($olay['veri'], true);
                $siparisId = $veri['siparis_id'];
                $urunler = $veri['urunler'];

                foreach ($urunler as $urun) {
                    $this->stokGuncelle($urun['varyant_id'], 'satis', -$urun['adet'], $siparisId, (float)$urun['maliyet_fiyati'], null);
                }

                // İşlenen olayı işaretle
                $this->pdo->prepare("UPDATE olay_gunlugu SET islendi = 1 WHERE olay_id = ?")->execute([$olay['olay_id']]);

                $this->pdo->commit();
                $islenen_olay_sayisi++;
            } catch (Exception $e) {
                $this->pdo->rollBack();
                error_log("Envanter olayı işlenirken hata (ID: {$olay['olay_id']}): " . $e->getMessage());
            }
        }
        return ['islenen_olay_sayisi' => $islenen_olay_sayisi];
    }

    // ... (stokGuncelle ve diğer metodlar)
}
