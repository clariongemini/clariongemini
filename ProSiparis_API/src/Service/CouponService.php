<?php
namespace ProSiparis\Service;

use PDO;

class CouponService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Bir kupon kodunu doğrular ve indirim hesaplar.
     * @param string $kuponKodu
     * @param float $sepetTutari
     * @return array
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

        if ($kupon['kullanim_limiti'] && $kupon['kac_kez_kullanildi'] >= $kupon['kullanim_limiti']) {
            return ['gecerli' => false, 'mesaj' => 'Bu kupon kullanım limitine ulaşmış.'];
        }

        if ($sepetTutari < $kupon['minimum_sepet_tutari']) {
            return ['gecerli' => false, 'mesaj' => "Bu kuponu kullanmak için minimum sepet tutarı {$kupon['minimum_sepet_tutari']} TL olmalıdır."];
        }

        // İndirimi hesapla
        $indirimTutari = 0;
        if ($kupon['indirim_tipi'] === 'yuzde') {
            $indirimTutari = ($sepetTutari * $kupon['indirim_degeri']) / 100;
        } else { // sabit_tutar
            $indirimTutari = $kupon['indirim_degeri'];
        }

        $yeniSepetTutari = max(0, $sepetTutari - $indirimTutari);

        return [
            'gecerli' => true,
            'mesaj' => 'Kupon başarıyla uygulandı!',
            'indirim_tutari' => $indirimTutari,
            'yeni_sepet_tutari' => $yeniSepetTutari,
            'kupon_kodu' => $kupon['kupon_kodu']
        ];
    }

    /**
     * Bir kuponun kullanım sayısını artırır.
     * @param string $kuponKodu
     */
    public function kuponKullaniminiArtir(string $kuponKodu): void
    {
        $sql = "UPDATE kuponlar SET kac_kez_kullanildi = kac_kez_kullanildi + 1 WHERE kupon_kodu = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kuponKodu]);
    }
}
