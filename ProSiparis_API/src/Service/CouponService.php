<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class CouponService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // --- Admin CRUD Metodları ---

    public function listele(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM kuponlar ORDER BY son_kullanma_tarihi DESC");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Yeni bir kupon oluşturur.
     * @param array $veri Kupon verileri
     * @return array
     */
    public function olustur(array $veri): array
    {
        $sql = "INSERT INTO kuponlar (kullanici_id, kupon_kodu, indirim_tipi, indirim_degeri, son_kullanma_tarihi, minimum_sepet_tutari, kullanim_limiti, aktif_mi)
                VALUES (:kullanici_id, :kupon_kodu, :indirim_tipi, :indirim_degeri, :son_kullanma_tarihi, :minimum_sepet_tutari, :kullanim_limiti, :aktif_mi)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':kullanici_id' => $veri['kullanici_id'] ?? null,
                ':kupon_kodu' => $veri['kupon_kodu'],
                ':indirim_tipi' => $veri['indirim_tipi'],
                ':indirim_degeri' => $veri['indirim_degeri'],
                ':son_kullanma_tarihi' => $veri['son_kullanma_tarihi'] ?? null,
                ':minimum_sepet_tutari' => $veri['minimum_sepet_tutari'] ?? 0.00,
                ':kullanim_limiti' => $veri['kullanim_limiti'] ?? null,
                ':aktif_mi' => $veri['aktif_mi'] ?? true
            ]);
            return ['basarili' => true, 'kod' => 201, 'veri' => ['kupon_id' => $this->pdo->lastInsertId()]];
        } catch (Exception $e) {
             return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Kupon oluşturulurken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    public function guncelle(int $id, array $veri): array
    {
        $sql = "UPDATE kuponlar SET
                    kullanici_id = :kullanici_id,
                    kupon_kodu = :kupon_kodu,
                    indirim_tipi = :indirim_tipi,
                    indirim_degeri = :indirim_degeri,
                    son_kullanma_tarihi = :son_kullanma_tarihi,
                    minimum_sepet_tutari = :minimum_sepet_tutari,
                    kullanim_limiti = :kullanim_limiti,
                    aktif_mi = :aktif_mi
                WHERE kupon_id = :kupon_id";

        try {
            $veri['kupon_id'] = $id;
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($veri);
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Kupon başarıyla güncellendi.'];
        } catch (Exception $e) {
             return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Kupon güncellenirken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    public function sil(int $id): array
    {
        $stmt = $this->pdo->prepare("DELETE FROM kuponlar WHERE kupon_id = ?");
        $stmt->execute([$id]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Kupon başarıyla silindi.'];
    }

    // --- Müşteri Taraflı Metodlar ---

    /**
     * Bir kupon kodunu doğrular ve indirim hesaplar.
     */
    public function kuponDogrula(string $kuponKodu, float $sepetTutari): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM kuponlar WHERE kupon_kodu = ? AND aktif_mi = TRUE");
        $stmt->execute([$kuponKodu]);
        $kupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$kupon) {
            return ['gecerli' => false, 'mesaj' => 'Geçersiz veya bulunamayan kupon kodu.'];
        }

        if ($kupon['son_kullanma_tarihi'] && new \DateTime() > new \DateTime($kupon['son_kullanma_tarihi'])) {
            return ['gecerli' => false, 'mesaj' => 'Bu kuponun süresi dolmuş.'];
        }

        if ($kupon['kullanim_limiti'] !== null && $kupon['kac_kez_kullanildi'] >= $kupon['kullanim_limiti']) {
            return ['gecerli' => false, 'mesaj' => 'Bu kupon kullanım limitine ulaşmış.'];
        }

        if ($sepetTutari < $kupon['minimum_sepet_tutari']) {
            return ['gecerli' => false, 'mesaj' => "Bu kuponu kullanmak için minimum sepet tutarı {$kupon['minimum_sepet_tutari']} TL olmalıdır."];
        }

        $indirimTutari = 0;
        if ($kupon['indirim_tipi'] === 'yuzde') {
            $indirimTutari = ($sepetTutari * $kupon['indirim_degeri']) / 100;
        } else {
            $indirimTutari = $kupon['indirim_degeri'];
        }

        return [
            'gecerli' => true,
            'mesaj' => 'Kupon başarıyla uygulandı!',
            'indirim_tutari' => round($indirimTutari, 2),
            'yeni_sepet_tutari' => max(0, $sepetTutari - $indirimTutari),
            'kupon_kodu' => $kupon['kupon_kodu']
        ];
    }

    /**
     * Bir kuponun kullanım sayısını artırır.
     */
    public function kuponKullaniminiArtir(string $kuponKodu): void
    {
        $sql = "UPDATE kuponlar SET kac_kez_kullanildi = kac_kez_kullanildi + 1 WHERE kupon_kodu = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kuponKodu]);
    }
}
