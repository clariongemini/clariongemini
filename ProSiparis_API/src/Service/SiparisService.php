<?php
namespace ProSiparis\Service;

use PDO;

class SiparisService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Belirli bir kullanıcının sipariş geçmişini, detaylarıyla birlikte getirir.
     * @param int $kullaniciId
     * @return array
     */
    public function kullaniciSiparisleriniGetir(int $kullaniciId): array
    {
        try {
            $sql = "SELECT id, siparis_tarihi, toplam_tutar, durum FROM siparisler WHERE kullanici_id = ? ORDER BY siparis_tarihi DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kullaniciId]);
            $siparisler = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Her siparişin detayını ekle (opsiyonel, ama iyi bir pratik)
            // ...

            return ['basarili' => true, 'kod' => 200, 'veri' => $siparisler];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş geçmişi getirilirken bir veritabanı hatası oluştu.'];
        }
    }

    /**
     * Tüm siparişleri (admin için), detaylarıyla getirir.
     * @return array
     */
    public function tumSiparisleriGetir(): array
    {
        try {
            $sql = "SELECT s.id, s.siparis_tarihi, s.toplam_tutar, s.durum, k.ad_soyad, k.eposta
                    FROM siparisler s
                    JOIN kullanicilar k ON s.kullanici_id = k.id
                    ORDER BY s.siparis_tarihi DESC";
            $stmt = $this->pdo->query($sql);
            return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Tüm siparişler getirilirken bir hata oluştu.'];
        }
    }

    /**
     * Bir siparişin durumunu günceller (admin için).
     * @param int $siparisId
     * @param string $yeniDurum
     * @return array
     */
    public function siparisDurumGuncelle(int $siparisId, string $yeniDurum): array
    {
        if (empty($yeniDurum)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Yeni durum belirtilmelidir.'];
        }

        try {
            $sql = "UPDATE siparisler SET durum = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$yeniDurum, $siparisId]);

            if ($stmt->rowCount() > 0) {
                return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sipariş durumu başarıyla güncellendi.'];
            } else {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Güncellenecek sipariş bulunamadı.'];
            }
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş durumu güncellenirken bir hata oluştu.'];
        }
    }

    /**
     * Yeni bir sipariş oluşturur ve stoktan düşer.
     * @param int $kullaniciId
     * @param array $sepet
     * @param int $teslimatAdresiId
     * @param int $kargoId
     * @return array
     */
    public function siparisOlustur(int $kullaniciId, array $sepet, int $teslimatAdresiId, int $kargoId): array
    {
        if (empty($sepet)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Sepet bilgileri zorunludur ve boş olamaz.'];
        }

        try {
            $this->pdo->beginTransaction();

            $toplamTutar = 0;
            foreach ($sepet as $item) {
                $stmt = $this->pdo->prepare("SELECT varyant_sku, fiyat, stok_adedi FROM urun_varyantlari WHERE varyant_id = ? FOR UPDATE");
                $stmt->execute([$item['varyant_id']]);
                $varyant = $stmt->fetch();

                if (!$varyant) {
                    throw new \Exception("Sepetteki bir ürün bulunamadı (Varyant ID: {$item['varyant_id']}).");
                }
                if ($varyant['stok_adedi'] < $item['adet']) {
                    throw new \Exception("Stokta yeterli ürün yok: {$varyant['varyant_sku']}. Stok: {$varyant['stok_adedi']}, İstenen: {$item['adet']}");
                }
                $toplamTutar += $varyant['fiyat'] * $item['adet'];
            }

            $sql = "INSERT INTO siparisler (kullanici_id, toplam_tutar, teslimat_adresi_id, kargo_id, durum) VALUES (?, ?, ?, ?, 'Hazirlaniyor')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kullaniciId, $toplamTutar, $teslimatAdresiId, $kargoId]);
            $siparisId = $this->pdo->lastInsertId();

            foreach ($sepet as $item) {
                 $stmt = $this->pdo->prepare("SELECT fiyat FROM urun_varyantlari WHERE varyant_id = ?");
                 $stmt->execute([$item['varyant_id']]);
                 $birimFiyat = $stmt->fetchColumn();

                $sql = "INSERT INTO siparis_detaylari (siparis_id, varyant_id, adet, birim_fiyat) VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$siparisId, $item['varyant_id'], $item['adet'], $birimFiyat]);

                $sql = "UPDATE urun_varyantlari SET stok_adedi = stok_adedi - ? WHERE varyant_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$item['adet'], $item['varyant_id']]);
            }

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 201, 'veri' => ['siparis_id' => $siparisId], 'mesaj' => 'Siparişiniz başarıyla alındı.'];

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $mesaj = strpos($e->getMessage(), 'Stokta yeterli ürün yok') !== false
                ? $e->getMessage()
                : 'Sipariş oluşturulurken bir hata oluştu.';
            return ['basarili' => false, 'kod' => 400, 'mesaj' => $mesaj];
        }
    }
}
