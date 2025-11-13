<?php
namespace FulcrumOS\Service;

use PDO;
use Exception;

class DestekService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Belirli bir kullanıcının tüm destek taleplerini listeler.
     * @param int $kullaniciId
     * @return array
     */
    public function kullaniciTalepleriniListele(int $kullaniciId): array
    {
        $sql = "SELECT talep_id, konu, durum, olusturma_tarihi, guncellenme_tarihi FROM destek_talepleri WHERE kullanici_id = ? ORDER BY guncellenme_tarihi DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kullaniciId]);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Admin için tüm destek taleplerini filtreleyerek listeler.
     * @param array $filtreler ['durum' => 'acik']
     * @return array
     */
    public function tumTalepleriListele(array $filtreler = []): array
    {
        $sql = "SELECT dt.talep_id, dt.konu, dt.durum, k.ad_soyad as kullanici_adi, dt.guncellenme_tarihi
                FROM destek_talepleri dt
                JOIN kullanicilar k ON dt.kullanici_id = k.id";

        $where = [];
        $params = [];
        if (!empty($filtreler['durum'])) {
            $where[] = "dt.durum = ?";
            $params[] = $filtreler['durum'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY dt.guncellenme_tarihi DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Yeni bir destek talebi ve ilk mesajı oluşturur.
     * @param int $kullaniciId
     * @param array $veri ['siparis_id', 'konu', 'mesaj']
     * @return array
     */
    public function talepOlustur(int $kullaniciId, array $veri): array
    {
        if (empty($veri['konu']) || empty($veri['mesaj'])) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Konu ve mesaj alanları zorunludur.'];
        }

        $this->pdo->beginTransaction();
        try {
            // 1. Talep oluştur
            $sqlTalep = "INSERT INTO destek_talepleri (kullanici_id, siparis_id, konu) VALUES (?, ?, ?)";
            $stmtTalep = $this->pdo->prepare($sqlTalep);
            $stmtTalep->execute([$kullaniciId, $veri['siparis_id'] ?? null, $veri['konu']]);
            $talepId = $this->pdo->lastInsertId();

            // 2. İlk mesajı ekle
            $sqlMesaj = "INSERT INTO destek_mesajlari (talep_id, gonderen_kullanici_id, mesaj_icerigi) VALUES (?, ?, ?)";
            $stmtMesaj = $this->pdo->prepare($sqlMesaj);
            $stmtMesaj->execute([$talepId, $kullaniciId, $veri['mesaj']]);

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 201, 'veri' => ['talep_id' => $talepId]];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Destek talebi oluşturulurken bir hata oluştu.'];
        }
    }

    /**
     * Bir talebin tüm mesajlaşma geçmişini getirir.
     * @param int $talepId
     * @return array
     */
    public function talepDetaylariniGetir(int $talepId): array
    {
        // Talep bilgilerini al
        $sqlTalep = "SELECT dt.*, k.ad_soyad FROM destek_talepleri dt JOIN kullanicilar k ON dt.kullanici_id = k.id WHERE talep_id = ?";
        $stmtTalep = $this->pdo->prepare($sqlTalep);
        $stmtTalep->execute([$talepId]);
        $talep = $stmtTalep->fetch(PDO::FETCH_ASSOC);

        if (!$talep) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Destek talebi bulunamadı.'];
        }

        // Mesajları al
        $sqlMesajlar = "SELECT dm.*, k.ad_soyad as gonderen_adi FROM destek_mesajlari dm LEFT JOIN kullanicilar k ON dm.gonderen_kullanici_id = k.id OR dm.gonderen_admin_id = k.id WHERE talep_id = ? ORDER BY tarih ASC";
        $stmtMesajlar = $this->pdo->prepare($sqlMesajlar);
        $stmtMesajlar->execute([$talepId]);
        $mesajlar = $stmtMesajlar->fetchAll(PDO::FETCH_ASSOC);

        $talep['mesajlar'] = $mesajlar;
        return ['basarili' => true, 'kod' => 200, 'veri' => $talep];
    }

    /**
     * Mevcut bir talebe yeni bir mesaj ekler.
     * @param int $talepId
     * @param int $gonderenId
     * @param string $mesaj
     * @param bool $isAdmin
     * @return array
     */
    public function mesajaEkle(int $talepId, int $gonderenId, string $mesaj, bool $isAdmin = false): array
    {
        if (empty($mesaj)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Mesaj içeriği boş olamaz.'];
        }

        $this->pdo->beginTransaction();
        try {
            $gonderenKullaniciKolonu = $isAdmin ? 'gonderen_admin_id' : 'gonderen_kullanici_id';

            $sqlMesaj = "INSERT INTO destek_mesajlari (talep_id, {$gonderenKullaniciKolonu}, mesaj_icerigi) VALUES (?, ?, ?)";
            $stmtMesaj = $this->pdo->prepare($sqlMesaj);
            $stmtMesaj->execute([$talepId, $gonderenId, $mesaj]);

            // Admin cevap veriyorsa talebin durumunu güncelle
            if ($isAdmin) {
                $sqlDurum = "UPDATE destek_talepleri SET durum = 'cevaplandi' WHERE talep_id = ?";
                $stmtDurum = $this->pdo->prepare($sqlDurum);
                $stmtDurum->execute([$talepId]);
            }

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 201, 'mesaj' => 'Mesaj başarıyla gönderildi.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Mesaj gönderilirken bir hata oluştu.'];
        }
    }
}
