<?php
namespace FulcrumOS\Service;

use PDO;
use Exception;

class SepetService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Kullanıcının veritabanındaki sepetini getirir.
     * @param int $kullaniciId
     * @return array
     */
    public function sepetiGetir(int $kullaniciId): array
    {
        $sql = "
            SELECT su.varyant_id, su.adet, u.urun_adi, uv.fiyat, uv.resim_url
            FROM sepetler s
            JOIN sepet_urunleri su ON s.sepet_id = su.sepet_id
            JOIN urun_varyantlari uv ON su.varyant_id = uv.varyant_id
            JOIN urunler u ON uv.urun_id = u.urun_id
            WHERE s.kullanici_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kullaniciId]);
        $urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['basarili' => true, 'kod' => 200, 'veri' => ['urunler' => $urunler]];
    }

    /**
     * Kullanıcının sepetini günceller (ürün ekler, çıkarır, adedi değiştirir).
     * @param int $kullaniciId
     * @param array $urunler ['varyant_id' => adet] formatında. Adet 0 ise ürün silinir.
     * @return array
     */
    public function sepetiGuncelle(int $kullaniciId, array $urunler): array
    {
        if (empty($urunler)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Güncellenecek ürün bilgisi bulunamadı.'];
        }

        $this->pdo->beginTransaction();
        try {
            // 1. Kullanıcının sepetini bul veya oluştur
            $sepetId = $this->getOrCreateSepet($kullaniciId);

            foreach ($urunler as $varyantId => $adet) {
                $varyantId = (int)$varyantId;
                $adet = (int)$adet;

                // 2. Stok kontrolü yap
                $stok = $this->pdo->prepare("SELECT stok_adedi FROM urun_varyantlari WHERE varyant_id = ?");
                $stok->execute([$varyantId]);
                $stokAdedi = $stok->fetchColumn();

                if ($stokAdedi === false) {
                    throw new Exception("Varyant ID {$varyantId} bulunamadı.");
                }
                if ($adet > $stokAdedi) {
                    throw new Exception("Varyant ID {$varyantId} için yeterli stok yok. İstenen: {$adet}, Mevcut: {$stokAdedi}.");
                }

                if ($adet > 0) {
                    // Ekle veya güncelle (INSERT ... ON DUPLICATE KEY UPDATE)
                    $sql = "
                        INSERT INTO sepet_urunleri (sepet_id, varyant_id, adet)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE adet = ?
                    ";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$sepetId, $varyantId, $adet, $adet]);
                } else {
                    // Adet 0 ise ürünü sepetten sil
                    $sql = "DELETE FROM sepet_urunleri WHERE sepet_id = ? AND varyant_id = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$sepetId, $varyantId]);
                }
            }

            // Sepetin guncellenme_tarihi'ni tetikle
            $this->pdo->prepare("UPDATE sepetler SET guncellenme_tarihi = CURRENT_TIMESTAMP WHERE sepet_id = ?")->execute([$sepetId]);

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sepet başarıyla güncellendi.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sepet güncellenirken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    /**
     * Kullanıcı için sepet ID'sini alır, yoksa yeni bir sepet oluşturur.
     * @param int $kullaniciId
     * @return int
     */
    private function getOrCreateSepet(int $kullaniciId): int
    {
        $stmt = $this->pdo->prepare("SELECT sepet_id FROM sepetler WHERE kullanici_id = ?");
        $stmt->execute([$kullaniciId]);
        $sepetId = $stmt->fetchColumn();

        if (!$sepetId) {
            $this->pdo->prepare("INSERT INTO sepetler (kullanici_id) VALUES (?)")->execute([$kullaniciId]);
            $sepetId = $this->pdo->lastInsertId();
        }
        return (int)$sepetId;
    }
}
