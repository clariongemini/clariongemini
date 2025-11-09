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

    /**
     * Event Bus'taki envanterle ilgili olayları işler.
     * Bu metod, bir cron job veya worker tarafından periyodik olarak çağrılmalıdır.
     */
    public function olaylariIsle(): array
    {
        $olayTipleri = ['siparis.kargolandi', 'tedarik.mal_kabul_yapildi', 'iade.stoga_geri_alindi'];
        $placeholders = rtrim(str_repeat('?,', count($olayTipleri)), ',');

        $sql = "SELECT olay_id, olay_tipi, veri FROM olay_gunlugu WHERE olay_tipi IN ($placeholders) AND islendi = 0 ORDER BY olay_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($olayTipleri);
        $olaylar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $islenen_olay_sayisi = 0;
        foreach ($olaylar as $olay) {
            $this->pdo->beginTransaction();
            try {
                $veri = json_decode($olay['veri'], true);

                switch ($olay['olay_tipi']) {
                    case 'siparis.kargolandi':
                        foreach ($veri['urunler'] as $urun) {
                            $this->stokGuncelle($urun['varyant_id'], 'satis', -$urun['adet'], $veri['siparis_id'], (float)$urun['maliyet_fiyati'], null);
                        }
                        break;

                    case 'tedarik.mal_kabul_yapildi':
                        foreach ($veri['urunler'] as $urun) {
                            // Bu işlem Ağırlıklı Ortalama Maliyeti de güncellemeli
                            $this->stokGuncelle($urun['varyant_id'], 'satin_alma', $urun['gelen_adet'], $veri['po_id'], (float)$urun['maliyet'], $veri['kullanici_id']);
                        }
                        break;

                    case 'iade.stoga_geri_alindi':
                         foreach ($veri['urunler'] as $urun) {
                            // İade edilen ürünün maliyeti, son bilinen AOM olabilir.
                            $this->stokGuncelle($urun['varyant_id'], 'iade_giris', $urun['adet'], $veri['iade_id'], null, null);
                        }
                        break;
                }

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
