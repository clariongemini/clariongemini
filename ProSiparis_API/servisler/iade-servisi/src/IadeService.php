<?php
namespace ProSiparis\Iade;

require_once __DIR__ . '/../../../core/EventBusService.php';

use PDO;
use Exception;
use ProSiparis\Core\EventBusService;

class IadeService
{
    private PDO $pdo;
    private EventBusService $eventBus;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->eventBus = new EventBusService();
    }

    private function publishEvent(string $eventType, array $data): void
    {
        $this->eventBus->publish($eventType, $data);
    }

    private function getTakipYontemi(int $varyantId): ?string
    {
        $url = 'http://katalog-servisi/internal/urun-takip-yontemi?varyant_id=' . $varyantId;

        try {
            $responseJson = @file_get_contents($url);
            if ($responseJson === false) {
                return null;
            }

            $response = json_decode($responseJson, true);
            return ($response['basarili'] && isset($response['veri']['takip_yontemi'])) ? $response['veri']['takip_yontemi'] : null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * v4.0 Refaktör: İadeyi belirli bir depoya, hibrit (adet/seri_no) takip yöntemiyle teslim alır.
     */
    public function iadeTeslimAl(int $depoId, int $iadeId, array $gelenVeri, int $kullaniciId): array
    {
        $this->pdo->beginTransaction();
        try {
            $stogaAlinacaklarEvent = [];

            foreach ($gelenVeri['urunler'] as $urun) {
                $varyantId = $urun['varyant_id'];
                $takipYontemi = $this->getTakipYontemi($varyantId);

                if ($takipYontemi === 'adet') {
                    if ($urun['durum'] === 'Satılabilir') {
                        $stogaAlinacaklarEvent[] = [
                            'varyant_id' => $varyantId,
                            'adet' => $urun['adet']
                        ];
                    }
                } elseif ($takipYontemi === 'seri_no') {
                     if ($urun['durum'] === 'Satılabilir' || $urun['durum'] === 'Kusurlu') {
                        $stogaAlinacaklarEvent[] = [
                            'varyant_id' => $varyantId,
                            'seri_no' => $urun['taranan_seri_no'],
                            'durum' => ($urun['durum'] === 'Satılabilir') ? 'iade_satilabilir' : 'iade_kusurlu'
                        ];
                    }
                }

                // İade ürününün durumunu kendi veritabanında güncelle
                $this->pdo->prepare("UPDATE iade_urunleri SET durum = ? WHERE iade_id = ? AND varyant_id = ?")
                          ->execute([$urun['durum'], $iadeId, $varyantId]);
            }

            $this->pdo->prepare("UPDATE iade_talepleri SET durum = 'Depoya Ulaştı' WHERE iade_id = ?")->execute([$iadeId]);

            // Sadece stoğa geri alınacak ürün varsa olay yayınla
            if (!empty($stogaAlinacaklarEvent)) {
                $this->publishEvent('iade.stoga_geri_alindi', [
                    "depo_id" => $depoId, // Yeni alan
                    "iade_id" => $iadeId,
                    "urunler" => $stogaAlinacaklarEvent // Hibrit veri yapısı
                ]);
            }

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'İade teslim alındı ve WMS envanter olayı yayınlandı.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'İade teslim alınırken hata: ' . $e->getMessage()];
        }
    }

    // ... Diğer IadeService metodları (iadeTalebiOlustur, iadeOdemeYap vb.) burada yer alır.
    // iadeTalebiOlustur'daki checkOrderStatus metodunun da kalması gerekir.
}
