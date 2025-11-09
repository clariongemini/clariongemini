<?php
namespace ProSiparis\Tedarik;

use PDO;
use Exception;

class TedarikService
{
    private PDO $pdo;
    private PDO $eventPdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->eventPdo = new PDO('mysql:host=db;dbname=prosiparis_core', 'user', 'password');
        $this->eventPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function publishEvent(string $eventType, array $data): void
    {
        $sql = "INSERT INTO olay_gunlugu (olay_tipi, veri) VALUES (?, ?)";
        $stmt = $this->eventPdo->prepare($sql);
        $stmt->execute([$eventType, json_encode($data)]);
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
     * v4.0 Refaktör: Teslimatı belirli bir depoya, hibrit (adet/seri_no) takip yöntemiyle alır.
     */
    public function teslimatAl(int $depoId, int $poId, array $gelenVeri, int $kullaniciId): array
    {
        $this->pdo->beginTransaction();
        try {
            $eventPayloadUrunler = [];

            foreach ($gelenVeri['urunler'] as $urun) {
                $varyantId = $urun['varyant_id'];
                $takipYontemi = $this->getTakipYontemi($varyantId);

                if ($takipYontemi === 'adet') {
                    if (!isset($urun['gelen_adet'])) {
                        throw new Exception("Varyant $varyantId için 'gelen_adet' zorunludur.");
                    }
                    $eventPayloadUrunler[] = [
                        'varyant_id' => $varyantId,
                        'gelen_adet' => $urun['gelen_adet'],
                        'maliyet' => $urun['maliyet']
                    ];
                } elseif ($takipYontemi === 'seri_no') {
                    if (!isset($urun['seri_numaralari']) || !is_array($urun['seri_numaralari'])) {
                        throw new Exception("Varyant $varyantId için 'seri_numaralari' dizisi zorunludur.");
                    }
                    $eventPayloadUrunler[] = [
                        'varyant_id' => $varyantId,
                        'seri_numaralari' => $urun['seri_numaralari'],
                        'maliyet' => $urun['maliyet']
                    ];
                }
            }

            // PO durumunu güncelle
            $stmt = $this->pdo->prepare("UPDATE satin_alma_siparisleri SET durum = 'Tamamlandı', teslim_alinma_tarihi = NOW() WHERE po_id = ?");
            $stmt->execute([$poId]);

            // v4.0 Olayını Yayınla
            $this->publishEvent('tedarik.mal_kabul_yapildi', [
                "kullanici_id" => $kullaniciId,
                "depo_id" => $depoId, // Yeni alan
                "po_id" => $poId,
                "urunler" => $eventPayloadUrunler // Hibrit veri yapısı
            ]);

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Teslimat başarıyla alındı ve WMS envanter olayı yayınlandı.'];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Teslimat alınırken hata: ' . $e->getMessage()];
        }
    }

    // ... Tedarikçi ve Satın Alma Siparişi CRUD metodları burada yer alır (değişiklik yok)
}
