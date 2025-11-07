<?php
namespace ProSiparis\Service;

use PDO;

class ReviewService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Kullanıcının belirtilen ürünü satın alıp almadığını ve siparişin "Teslim Edildi" durumunda olup olmadığını kontrol eder.
     */
    public function kullaniciUrunuSatinAldiMi(int $kullaniciId, int $urunId): bool
    {
        $sql = "SELECT COUNT(*) FROM siparisler s
                JOIN siparis_detaylari sd ON s.id = sd.siparis_id
                JOIN urun_varyantlari uv ON sd.varyant_id = uv.varyant_id
                WHERE s.kullanici_id = ? AND uv.urun_id = ? AND s.durum = 'Teslim Edildi'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kullaniciId, $urunId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Bir ürünün ortalama puanını ve değerlendirme sayısını yeniden hesaplar ve urunler tablosunu günceller.
     */
    public function recalculateProductRating(int $urunId): void
    {
        $sql = "SELECT AVG(puan) as ortalama_puan, COUNT(degerlendirme_id) as degerlendirme_sayisi
                FROM urun_degerlendirmeleri WHERE urun_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$urunId]);
        $sonuclar = $stmt->fetch(PDO::FETCH_ASSOC);

        $ortalamaPuan = $sonuclar['ortalama_puan'] ?? 0;
        $degerlendirmeSayisi = $sonuclar['degerlendirme_sayisi'] ?? 0;

        $updateSql = "UPDATE urunler SET ortalama_puan = ?, degerlendirme_sayisi = ? WHERE urun_id = ?";
        $updateStmt = $this->pdo->prepare($updateSql);
        $updateStmt->execute([$ortalamaPuan, $degerlendirmeSayisi, $urunId]);
    }

    public function olustur(int $kullaniciId, int $urunId, array $veri): array
    {
        if (empty($veri['puan']) || !is_numeric($veri['puan']) || $veri['puan'] < 1 || $veri['puan'] > 5) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Puan, 1 ile 5 arasında bir sayı olmalıdır.'];
        }

        try {
            $sql = "INSERT INTO urun_degerlendirmeleri (kullanici_id, urun_id, puan, yorum) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kullaniciId, $urunId, $veri['puan'], $veri['yorum'] ?? null]);

            $this->recalculateProductRating($urunId);

            return ['basarili' => true, 'kod' => 201, 'mesaj' => 'Değerlendirmeniz başarıyla eklendi.'];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Değerlendirme eklenirken bir hata oluştu.'];
        }
    }

    public function urunDegerlendirmeleriniGetir(int $urunId): array
    {
        $sql = "SELECT d.puan, d.yorum, d.tarih, k.ad_soyad
                FROM urun_degerlendirmeleri d
                JOIN kullanicilar k ON d.kullanici_id = k.id
                WHERE d.urun_id = ? ORDER BY d.tarih DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$urunId]);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function sil(int $degerlendirmeId, int $kullaniciId, string $rol): array
    {
        // Önce değerlendirmenin urun_id'sini al, çünkü puanı yeniden hesaplamamız gerekecek.
        $stmt = $this->pdo->prepare("SELECT urun_id, kullanici_id FROM urun_degerlendirmeleri WHERE degerlendirme_id = ?");
        $stmt->execute([$degerlendirmeId]);
        $degerlendirme = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$degerlendirme) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Değerlendirme bulunamadı.'];
        }

        // Güvenlik: Kullanıcı ya admin olmalı ya da kendi yorumunu siliyor olmalı.
        if ($rol !== 'admin' && $degerlendirme['kullanici_id'] != $kullaniciId) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Bu işlemi yapma yetkiniz yok.'];
        }

        $sql = "DELETE FROM urun_degerlendirmeleri WHERE degerlendirme_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$degerlendirmeId]);

        $this->recalculateProductRating($degerlendirme['urun_id']);

        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Değerlendirme başarıyla silindi.'];
    }
}
