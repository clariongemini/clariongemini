<?php
namespace ProSiparis\Siparis;

use PDO;
use Exception;

class SiparisService
{
    private PDO $pdo;
    // ...

    /**
     * v4.0 Refaktör: Sipariş oluşturmadan önce stok optimizasyonu yapar.
     */
    public function siparisOlustur(array $veri): array
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Stok Optimizasyonu
            $varyantIds = array_column($veri['sepet'], 'varyant_id');
            $atananDepoId = $this->findUygunDepo($varyantIds, $veri['sepet']);
            if ($atananDepoId === null) {
                return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Siparişinizdeki ürünler için uygun stok bulunamadı.'];
            }

            // 2. Siparişi oluştur ve depoyu ata
            $sql = "INSERT INTO siparisler (kullanici_id, toplam_tutar, durum, atanan_depo_id) VALUES (?, ?, 'Odendi', ?)";
            $this->pdo->prepare($sql)->execute([$veri['kullanici_id'], $veri['toplam_tutar'], $atananDepoId]);
            $siparisId = $this->pdo->lastInsertId();

            // ... (siparis_detaylari'nı yazma)

            // 3. Olay Yayınla
            $this->publishEvent('siparis.basarili', [
                'siparis_id' => $siparisId,
                'kullanici_eposta' => $veri['kullanici_eposta'],
                'atanan_depo_id' => $atananDepoId
            ]);

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 201, 'veri' => ['siparis_id' => $siparisId, 'atanan_depo_id' => $atananDepoId]];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            // ...
        }
    }

    /**
     * v4.0 Refaktör: Siparişi belirli bir depodan, hibrit takip yöntemiyle kargoya verir.
     */
    public function kargoyaVer(int $depoId, int $siparisId, array $kargoVerisi): array
    {
        // ... (siparişin bu depoya ait olup olmadığını kontrol et)

        // Olay verisini hazırla
        $eventPayloadUrunler = [];
        foreach ($kargoVerisi['urunler'] as $urun) {
            // Ürünün takip yöntemini bilmek için Katalog-Servisi'ne çağrı yapılabilir,
            // ancak bu örnekte gelen veriye güveniyoruz.
            if (isset($urun['taranan_seri_no'])) {
                 $eventPayloadUrunler[] = [
                    'varyant_id' => $urun['varyant_id'],
                    'seri_no' => $urun['taranan_seri_no']
                ];
            } else {
                 $eventPayloadUrunler[] = [
                    'varyant_id' => $urun['varyant_id'],
                    'adet' => $urun['adet']
                ];
            }
        }

        // Sipariş durumunu güncelle
        $this->pdo->prepare("UPDATE siparisler SET durum = 'Kargoya Verildi' WHERE id = ?")->execute([$siparisId]);

        // v4.0 Olayını Yayınla
        $this->publishEvent('siparis.kargolandi', [
            'siparis_id' => $siparisId,
            'depo_id' => $depoId, // Yeni alan
            'urunler' => $eventPayloadUrunler // Hibrit yapı
        ]);

        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sipariş kargoya verildi ve WMS envanter olayı yayınlandı.'];
    }

    private function findUygunDepo(array $varyantIds, array $sepet): ?int
    {
        $url = 'http://envanter-servisi/internal/stok-durumu?varyant_ids=' . implode(',', $varyantIds);
        $stokDurumuVerisi = @json_decode(file_get_contents($url), true);

        if (!$stokDurumuVerisi || !($stokDurumuVerisi['basarili'] ?? false)) {
            return null; // Envanter servisine ulaşılamadı.
        }
        $stokDurumu = $stokDurumuVerisi['veri'];

        // Sepetteki her ürün için potansiyel depoları ve stok miktarlarını haritala.
        $urunDepoStoklari = [];
        foreach ($sepet as $urun) {
            $urunDepoStoklari[$urun['varyant_id']] = [];
            if (isset($stokDurumu[$urun['varyant_id']])) {
                foreach ($stokDurumu[$urun['varyant_id']] as $depoStok) {
                    $urunDepoStoklari[$urun['varyant_id']][$depoStok['depo_id']] = $depoStok['stok'];
                }
            }
        }

        // Tüm ürünleri karşılayabilecek bir depo ara.
        $potansiyelDepolar = array_keys($urunDepoStoklari[$sepet[0]['varyant_id']] ?? []);
        foreach ($potansiyelDepolar as $depoId) {
            $buDepoUygun = true;
            foreach ($sepet as $urun) {
                $istenenAdet = $urun['adet'];
                $mevcutAdet = $urunDepoStoklari[$urun['varyant_id']][$depoId] ?? 0;
                if ($mevcutAdet < $istenenAdet) {
                    $buDepoUygun = false;
                    break;
                }
            }
            if ($buDepoUygun) {
                return $depoId; // İlk uygun depoyu bulduk.
            }
        }

        return null; // Uygun depo bulunamadı.
    }

    /**
     * v4.0: Belirli bir depoya atanan ve hazırlanma aşamasında olan siparişleri listeler.
     */
    public function getHazirlanacakSiparisler(int $depoId): array
    {
        $sql = "SELECT id, kullanici_id, toplam_tutar FROM siparisler WHERE atanan_depo_id = ? AND durum = 'Odendi'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$depoId]);
        $siparisler = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['basarili' => true, 'kod' => 200, 'veri' => $siparisler];
    }

    private function publishEvent(string $eventType, array $data): void {
        // ...
    }
}
