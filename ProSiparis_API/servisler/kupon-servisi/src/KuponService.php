<?php
namespace ProSiparis\Kupon;

use PDO;

class KuponService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Dahili API: Bir kuponun geçerliliğini ve indirim detaylarını kontrol eder.
     */
    public function dogrula(string $kuponKodu): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM kuponlar WHERE kupon_kodu = ? AND aktif = 1");
        $stmt->execute([$kuponKodu]);
        $kupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$kupon) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Kupon bulunamadı veya aktif değil.'];
        }
        if ($kupon['son_kullanim_tarihi'] && strtotime($kupon['son_kullanim_tarihi']) < time()) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Kuponun kullanım süresi dolmuş.'];
        }
        if ($kupon['kac_kez_kullanildi'] >= $kupon['kullanim_limiti']) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Kupon kullanım limitine ulaşmış.'];
        }

        return ['basarili' => true, 'kod' => 200, 'veri' => [
            'indirim_tipi' => $kupon['indirim_tipi'],
            'indirim_degeri' => $kupon['indirim_degeri']
        ]];
    }

    /**
     * Event Listener: Sipariş başarılı olduğunda kuponun kullanım sayacını artırır.
     */
    public function kullanimSayaciniArtir(string $kuponKodu, int $siparisId, int $kullaniciId): void
    {
        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare("SELECT kupon_id FROM kuponlar WHERE kupon_kodu = ?");
        $stmt->execute([$kuponKodu]);
        $kuponId = $stmt->fetchColumn();

        if ($kuponId) {
            $this->pdo->prepare("UPDATE kuponlar SET kac_kez_kullanildi = kac_kez_kullanildi + 1 WHERE kupon_id = ?")
                      ->execute([$kuponId]);

            $this->pdo->prepare("INSERT INTO kupon_kullanim_loglari (kupon_id, siparis_id, kullanici_id) VALUES (?, ?, ?)")
                      ->execute([$kuponId, $siparisId, $kullaniciId]);
        }

        $this->pdo->commit();
    }

    public function olaylariIsle(): array
    {
        $corePdo = new PDO('mysql:host=db;dbname=prosiparis_core', 'user', 'password');
        $stmt = $corePdo->prepare("SELECT olay_id, veri FROM olay_gunlugu WHERE olay_tipi = 'siparis.basarili' AND islendi_kupon = 0 ORDER BY olay_id ASC");
        $stmt->execute();
        $olaylar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $islenen_sayac = 0;
        foreach ($olaylar as $olay) {
            $veri = json_decode($olay['veri'], true);
            if (!empty($veri['kullanilan_kupon_kodu'])) {
                $this->kullanimSayaciniArtir($veri['kullanilan_kupon_kodu'], $veri['siparis_id'], $veri['kullanici_id']);
                $islenen_sayac++;
            }
            $corePdo->prepare("UPDATE olay_gunlugu SET islendi_kupon = 1 WHERE olay_id = ?")->execute([$olay['olay_id']]);
        }
        return ['islenen_kupon_olay_sayisi' => $islenen_sayac];
    }
    // ... Admin CRUD metodları (listele, olustur, guncelle, sil)
}
