<?php
namespace ProSiparis\Iade;

use PDO;
use Exception;

class IadeService
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

    private function checkOrderStatus(int $orderId): bool
    {
        // Gerçek senaryoda bu, Siparis-Servisi'ne bir cURL veya Guzzle HTTP isteği olacaktır.
        // Örn: $response = $client->get('http://siparis-servisi/internal/siparisler/durum-kontrol?siparis_id=' . $orderId);
        // Bu basit örnekte, siparişin her zaman "Teslim Edildi" olduğunu varsayıyoruz.
        return true;
    }

    public function iadeTalebiOlustur(array $veri): array
    {
        if (!$this->checkOrderStatus($veri['siparis_id'])) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'İade talebi için sipariş uygun durumda değil.'];
        }

        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO iade_talepleri (kullanici_id, siparis_id, neden, durum) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$veri['kullanici_id'], $veri['siparis_id'], $veri['neden'], 'Talep Alındı']);
            $iadeId = $this->pdo->lastInsertId();

            if (!empty($veri['urunler']) && is_array($veri['urunler'])) {
                foreach ($veri['urunler'] as $urun) {
                    $sqlUrun = "INSERT INTO iade_urunleri (iade_id, varyant_id, adet, durum) VALUES (?, ?, ?, ?)";
                    $stmtUrun = $this->pdo->prepare($sqlUrun);
                    $stmtUrun->execute([$iadeId, $urun['varyant_id'], $urun['adet'], 'Bekleniyor']);
                }
            }
            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 201, 'veri' => ['iade_id' => $iadeId]];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'İade talebi oluşturulurken hata: ' . $e->getMessage()];
        }
    }

    public function iadeTeslimAl(int $iadeId, array $urunler, int $kullaniciId): array
    {
        $this->pdo->beginTransaction();
        try {
            $stogaAlinacaklar = [];
            foreach ($urunler as $urun) {
                 if ($urun['durum'] === 'Satılabilir') {
                    $stogaAlinacaklar[] = [
                        "varyant_id" => $urun['varyant_id'],
                        "adet" => $urun['adet']
                    ];
                }
                 $this->pdo->prepare("UPDATE iade_urunleri SET durum = ? WHERE iade_id = ? AND varyant_id = ?")
                          ->execute([$urun['durum'], $iadeId, $urun['varyant_id']]);
            }

            $this->pdo->prepare("UPDATE iade_talepleri SET durum = 'Depoya Ulaştı' WHERE iade_id = ?")->execute([$iadeId]);

            if (!empty($stogaAlinacaklar)) {
                $this->publishEvent('iade.stoga_geri_alindi', [
                    "iade_id" => $iadeId,
                    "urunler" => $stogaAlinacaklar
                ]);
            }

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'İade teslim alındı.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Hata: ' . $e->getMessage()];
        }
    }

    public function iadeOdemeYap(int $iadeId, array $veri): array
    {
        // Iyzico veya başka bir ödeme sağlayıcısı ile entegrasyon burada yapılır.
        // Bu basit örnekte, işlemin başarılı olduğunu varsayıyoruz.
        $this->pdo->prepare("UPDATE iade_talepleri SET durum = 'Ödeme Yapıldı', odeme_referans = ? WHERE iade_id = ?")
                  ->execute([$veri['referans_no'], $iadeId]);

        $this->publishEvent('iade.odeme_basarili', [
            "iade_id" => $iadeId,
            "kullanici_eposta" => $veri['kullanici_eposta'], // Bu bilgi normalde talepten join edilir.
            "tutar" => $veri['tutar']
        ]);

        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Ödeme başarılı ve olay yayınlandı.'];
    }
     public function listeleKullaniciIadeTalepleri(int $kullaniciId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM iade_talepleri WHERE kullanici_id = ? ORDER BY talep_tarihi DESC");
        $stmt->execute([$kullaniciId]);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function listeleTumIadeTalepleri(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM iade_talepleri ORDER BY talep_tarihi DESC");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function guncelleIadeTalebiDurumu(int $iadeId, array $veri): array
    {
        $stmt = $this->pdo->prepare("UPDATE iade_talepleri SET durum = ? WHERE iade_id = ?");
        $stmt->execute([$veri['durum'], $iadeId]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Durum güncellendi.'];
    }
}
