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

    public function iadeOdemeYap(int $iadeId, float $tutar): array
    {
        $this->pdo->beginTransaction();
        try {
            // İade talebi bilgilerini al (kullanici_id için)
            $stmt = $this->pdo->prepare("SELECT kullanici_id FROM iade_talepleri WHERE iade_id = ?");
            $stmt->execute([$iadeId]);
            $iade = $stmt->fetch();

            if (!$iade) {
                throw new Exception("İade talebi bulunamadı.");
            }

            // Ödeme işlemini simüle et ve durumu güncelle
            $this->pdo->prepare("UPDATE iade_talepleri SET durum = 'Ödeme Tamamlandı', odeme_tutari = ? WHERE iade_id = ?")
                      ->execute([$tutar, $iadeId]);

            // E-posta için kullanıcı bilgisini al
            $kullaniciVerisi = $this->internalApiCall("http://auth-servisi/internal/kullanici/{$iade['kullanici_id']}");
            $kullaniciEposta = $kullaniciVerisi['eposta'] ?? null;

            // Zengin olayı yayınla
            $this->publishEvent('iade.odeme_basarili', [
                'iade_id' => $iadeId,
                'kullanici_id' => $iade['kullanici_id'],
                'kullanici_eposta' => $kullaniciEposta,
                'odenen_tutar' => $tutar
            ]);

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'İade ödemesi başarıyla tamamlandı.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'İade ödemesi sırasında hata: ' . $e->getMessage()];
        }
    }

    private function internalApiCall(string $url): ?array
    {
        $responseJson = @file_get_contents($url);
        if ($responseJson === false) {
            error_log("Dahili Iade Servisi API çağrısı başarısız oldu: $url");
            return null;
        }
        $response = json_decode($responseJson, true);
        return ($response && isset($response['basarili']) && $response['basarili']) ? $response['veri'] : null;
    }
}
