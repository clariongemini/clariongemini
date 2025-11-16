<?php
namespace ProSiparis\Envanter;

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
     * v5.1: RabbitMQ'dan gelen tek bir olayı işler.
     * Bu metod, bir worker/consumer tarafından tetiklenir.
     *
     * @param string $olayTipi Olayın adı (örn: "siparis.kargolandi")
     * @param array $veri Olayın payload'u
     */
    public function tekOlayIsle(string $olayTipi, array $veri): void
    {
        $this->pdo->beginTransaction();
        try {
            // depo_id, olay verisinden her zaman alınmalıdır.
            $depoId = $veri['depo_id'] ?? null;
            if ($depoId === null) {
                throw new Exception("Olay verisinde 'depo_id' bulunamadı.");
            }

            switch ($olayTipi) {
                case 'tedarik.mal_kabul_yapildi':
                    foreach ($veri['urunler'] as $urun) {
                        if (isset($urun['seri_numaralari'])) {
                            $this->seriNoGirisiYap($depoId, $urun['varyant_id'], $urun['seri_numaralari'], $veri['po_id']);
                        } else {
                            $this->stokGuncelleAdetBazli($depoId, $urun['varyant_id'], $urun['gelen_adet'], 'satin_alma', $veri['po_id'], $veri['kullanici_id'], $urun['maliyet']);
                        }
                    }
                    break;

                case 'siparis.kargolandi':
                    foreach ($veri['urunler'] as $urun) {
                        // Not: siparis.kargolandi olayında depo_id'nin payload'a eklenmesi gerekecek.
                        // Şimdilik, her ürünün kendi depo_id'si olduğunu varsayıyoruz (eğer varsa).
                        $urunDepoId = $urun['depo_id'] ?? $depoId;
                        if (isset($urun['seri_no'])) {
                            $this->seriNoDurumGuncelle($urun['seri_no'], 'satildi', ['siparis_id' => $veri['siparis_id']]);
                        } else {
                            $this->stokGuncelleAdetBazli($urunDepoId, $urun['varyant_id'], -$urun['adet'], 'satis', $veri['siparis_id'], null, null);
                        }
                    }
                    break;

                case 'iade.stoga_geri_alindi':
                    foreach ($veri['urunler'] as $urun) {
                        if (isset($urun['seri_no'])) {
                            $this->seriNoDurumGuncelle($urun['seri_no'], 'iade_satilabilir', ['iade_id' => $veri['iade_id']]);
                        } else {
                            $this->stokGuncelleAdetBazli($depoId, $urun['varyant_id'], $urun['adet'], 'iade_giris', $veri['iade_id'], null, null);
                        }
                    }
                    break;
            }

            $this->pdo->commit();
            echo "Olay başarıyla işlendi: $olayTipi" . PHP_EOL;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            // Gerçek bir RabbitMQ entegrasyonunda bu, olayı 'dead-letter queue'ye gönderebilir.
            error_log("Envanter olayı işlenirken hata ($olayTipi): " . $e->getMessage());
        }
    }

    /**
     * Dahili API: Verilen varyantların depo bazlı stok durumunu döndürür.
     */
    public function getDepoStokDurumu(array $varyantIds): array
    {
        $placeholders = rtrim(str_repeat('?,', count($varyantIds)), ',');
        $response = [];

        // Adet bazlı stokları çek
        $sqlAdet = "SELECT varyant_id, depo_id, adet FROM depo_stoklari WHERE varyant_id IN ($placeholders)";
        $stmtAdet = $this->pdo->prepare($sqlAdet);
        $stmtAdet->execute($varyantIds);
        while ($row = $stmtAdet->fetch(PDO::FETCH_ASSOC)) {
            $response[$row['varyant_id']][] = ['depo_id' => $row['depo_id'], 'stok' => (int)$row['adet']];
        }

        // Seri no bazlı stokları çek
        $sqlSeriNo = "SELECT varyant_id, depo_id, COUNT(*) as adet FROM envanter_seri_numaralari WHERE durum = 'stokta' AND varyant_id IN ($placeholders) GROUP BY varyant_id, depo_id";
        $stmtSeriNo = $this->pdo->prepare($sqlSeriNo);
        $stmtSeriNo->execute($varyantIds);
         while ($row = $stmtSeriNo->fetch(PDO::FETCH_ASSOC)) {
            $response[$row['varyant_id']][] = ['depo_id' => $row['depo_id'], 'stok' => (int)$row['adet']];
        }

        return ['basarili' => true, 'veri' => $response];
    }

    /**
     * Dahili API: Verilen bir sepeti karşılayabilecek depoların listesini bulur.
     */
    public function findUygunDepo(array $sepet): array
    {
        if (empty($sepet)) {
            return ['basarili' => true, 'kod' => 200, 'veri' => ['depo_idler' => []]];
        }

        $varyantIds = array_column($sepet, 'varyant_id');
        $stokDurumu = $this->getDepoStokDurumu($varyantIds)['veri'];

        $urunDepoStoklari = [];
        foreach ($sepet as $urun) {
            $urunDepoStoklari[$urun['varyant_id']] = [];
            if (isset($stokDurumu[$urun['varyant_id']])) {
                foreach ($stokDurumu[$urun['varyant_id']] as $depoStok) {
                    $urunDepoStoklari[$urun['varyant_id']][$depoStok['depo_id']] = $depoStok['stok'];
                }
            }
        }

        $uygunDepolar = [];
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
                $uygunDepolar[] = $depoId;
            }
        }

        return ['basarili' => true, 'kod' => 200, 'veri' => ['depo_idler' => $uygunDepolar]];
    }

    private function seriNoGirisiYap(int $depoId, int $varyantId, array $seriNumaralari, int $poId): void
    {
        $sql = "INSERT INTO envanter_seri_numaralari (seri_no, varyant_id, depo_id, po_id, durum) VALUES (?, ?, ?, ?, 'stokta')";
        $stmt = $this->pdo->prepare($sql);
        foreach ($seriNumaralari as $seriNo) {
            $stmt->execute([$seriNo, $varyantId, $depoId, $poId]);
        }
    }

    private function seriNoDurumGuncelle(string $seriNo, string $yeniDurum, array $referanslar = []): void
    {
        $setClauses = ['durum = :yeniDurum'];
        $params = ['yeniDurum' => $yeniDurum, 'seri_no' => $seriNo];

        if (isset($referanslar['siparis_id'])) {
            $setClauses[] = 'siparis_id = :siparisId';
            $params['siparisId'] = $referanslar['siparis_id'];
        }
        if (isset($referanslar['iade_id'])) {
            $setClauses[] = 'iade_id = :iadeId';
            $params['iadeId'] = $referanslar['iade_id'];
        }

        $sql = "UPDATE envanter_seri_numaralari SET " . implode(', ', $setClauses) . " WHERE seri_no = :seri_no";
        $this->pdo->prepare($sql)->execute($params);
    }

    private function stokGuncelleAdetBazli(int $depoId, int $varyantId, int $adetDegisimi, string $hareketTipi, ?int $referansId, ?int $kullaniciId, ?float $maliyet): void
    {
        // depo_stoklari tablosunu güncelle veya yeni kayıt ekle
        $sql = "INSERT INTO depo_stoklari (depo_id, varyant_id, adet) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE adet = adet + VALUES(adet)";
        $this->pdo->prepare($sql)->execute([$depoId, $varyantId, $adetDegisimi]);

        // Güncel stok adedini al
        $sonStok = (int)$this->pdo->prepare("SELECT adet FROM depo_stoklari WHERE depo_id = ? AND varyant_id = ?")->execute([$depoId, $varyantId])->fetchColumn();

        // Hareketleri (ledger) kaydet
        $sqlHareket = "INSERT INTO envanter_hareketleri (varyant_id, depo_id, hareket_tipi, adet_degisimi, son_stok, referans_id, maliyet, kullanici_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $this->pdo->prepare($sqlHareket)->execute([$varyantId, $depoId, $hareketTipi, $adetDegisimi, $sonStok, $referansId, $maliyet, $kullaniciId]);

        // AOM'yi güncelle (sadece mal girişlerinde)
        if ($hareketTipi === 'satin_alma' && $maliyet !== null) {
            $this->agirlikliOrtalamaMaliyetGuncelle($depoId, $varyantId, $adetDegisimi, $maliyet, $sonStok);
        }
    }

    private function agirlikliOrtalamaMaliyetGuncelle(int $depoId, int $varyantId, int $gelenAdet, float $gelenMaliyet, int $yeniToplamStok): void
    {
        $eskiMaliyetStmt = $this->pdo->prepare("SELECT agirlikli_ortalama_maliyet FROM depo_stok_maliyetleri WHERE depo_id = ? AND varyant_id = ?");
        $eskiMaliyetStmt->execute([$depoId, $varyantId]);
        $eskiAom = (float)$eskiMaliyetStmt->fetchColumn();

        $eskiStok = $yeniToplamStok - $gelenAdet;
        $eskiToplamDeger = $eskiAom * $eskiStok;
        $gelenToplamDeger = $gelenMaliyet * $gelenAdet;

        $yeniAom = ($eskiToplamDeger + $gelenToplamDeger) / $yeniToplamStok;

        $sql = "INSERT INTO depo_stok_maliyetleri (depo_id, varyant_id, agirlikli_ortalama_maliyet) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE agirlikli_ortalama_maliyet = VALUES(agirlikli_ortalama_maliyet)";
        $this->pdo->prepare($sql)->execute([$depoId, $varyantId, $yeniAom]);
    }
}
