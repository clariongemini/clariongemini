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
     * Belirli bir kullanıcının sipariş geçmişini getirir.
     * @param int $kullaniciId
     * @return array
     */
    public function kullaniciSiparisleriniGetir(int $kullaniciId): array
    {
        try {
            $sql = "SELECT id, siparis_tarihi, toplam_tutar, durum FROM siparisler WHERE kullanici_id = :kullanici_id ORDER BY siparis_tarihi DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':kullanici_id' => $kullaniciId]);
            $siparisler = $stmt->fetchAll();
            return ['basarili' => true, 'kod' => 200, 'veri' => $siparisler];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş geçmişi getirilirken bir veritabanı hatası oluştu.'];
        }
    }

    /**
     * Yeni bir sipariş oluşturur.
     * @param int $kullaniciId
     * @param array $veri ['toplam_tutar', 'sepet']
     * @return array
     */
    public function siparisOlustur(int $kullaniciId, array $veri): array
    {
        if (!isset($veri['toplam_tutar']) || !isset($veri['sepet']) || !is_array($veri['sepet']) || empty($veri['sepet'])) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Toplam tutar ve sepet bilgileri zorunludur.'];
        }

        try {
            $this->pdo->beginTransaction();

            $sql_siparis = "INSERT INTO siparisler (kullanici_id, toplam_tutar) VALUES (:kullanici_id, :toplam_tutar)";
            $stmt_siparis = $this->pdo->prepare($sql_siparis);
            $stmt_siparis->execute([
                'kullanici_id' => $kullaniciId,
                'toplam_tutar' => $veri['toplam_tutar']
            ]);
            $siparis_id = $this->pdo->lastInsertId();

            $sql_detay = "INSERT INTO siparis_detaylari (siparis_id, urun_id, adet, birim_fiyat) VALUES (:siparis_id, :urun_id, :adet, :birim_fiyat)";
            $stmt_detay = $this->pdo->prepare($sql_detay);

            foreach ($veri['sepet'] as $urun) {
                $stmt_detay->execute([
                    'siparis_id' => $siparis_id,
                    'urun_id' => $urun['urun_id'],
                    'adet' => $urun['adet'],
                    'birim_fiyat' => $urun['birim_fiyat']
                ]);
            }

            $this->pdo->commit();

            return ['basarili' => true, 'kod' => 201, 'veri' => ['siparis_id' => $siparis_id], 'mesaj' => 'Siparişiniz başarıyla alındı.'];
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş oluşturulurken bir hata oluştu.'];
        }
    }

    /**
     * Tüm siparişleri (admin için) getirir.
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
            $siparisler = $stmt->fetchAll();
            return ['basarili' => true, 'kod' => 200, 'veri' => $siparisler];
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
            $sql = "UPDATE siparisler SET durum = :durum WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':durum' => $yeniDurum, ':id' => $siparisId]);

            if ($stmt->rowCount() > 0) {
                return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sipariş durumu başarıyla güncellendi.'];
            } else {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Güncellenecek sipariş bulunamadı.'];
            }
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş durumu güncellenirken bir hata oluştu.'];
        }
    }
}
