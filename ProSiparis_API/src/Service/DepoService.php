<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class DepoService
{
    private PDO $pdo;
    private MailService $mailService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->mailService = new MailService();
    }

    public function hazirlanacakSiparisleriListele(): array { /* ... (Tamamlanmış Kod) ... */ }
    public function toplamaListesiGetir(int $siparisId): array { /* ... (Tamamlanmış Kod) ... */ }
    public function kargoyaVer(int $siparisId, string $kargoFirmasi, string $kargoTakipKodu): array { /* ... (Tamamlanmış Kod) ... */ }

    public function beklenenTeslimatlariListele(): array
    {
        $sql = "
            SELECT po.po_id, po.siparis_tarihi, po.beklenen_teslim_tarihi, t.firma_adi
            FROM satin_alma_siparisleri po
            JOIN tedarikciler t ON po.tedarikci_id = t.tedarikci_id
            WHERE po.durum IN ('Gönderildi', 'Kısmen Teslim Alındı')
            ORDER BY po.beklenen_teslim_tarihi ASC
        ";
        $stmt = $this->pdo->query($sql);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function teslimatAl(int $poId, array $urunler): array
    {
        if (empty($urunler)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Teslim alınan ürün listesi boş olamaz.'];
        }

        $this->pdo->beginTransaction();
        try {
            foreach ($urunler as $urun) {
                $varyantId = $urun['varyant_id'];
                $gelenAdet = $urun['gelen_adet'];
                if ($gelenAdet <= 0) continue;

                $this->pdo->prepare("UPDATE satin_alma_siparis_urunleri SET teslim_alinan_adet = teslim_alinan_adet + ? WHERE po_id = ? AND varyant_id = ?")
                          ->execute([$gelenAdet, $poId, $varyantId]);

                $this->pdo->prepare("UPDATE urun_varyantlari SET stok_adedi = stok_adedi + ? WHERE varyant_id = ?")
                          ->execute([$gelenAdet, $varyantId]);
            }
            $this->guncelleSatinAlmaSiparisiDurumu($poId);
            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Teslimat başarıyla alındı ve stoklar güncellendi.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Teslimat alınırken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    private function guncelleSatinAlmaSiparisiDurumu(int $poId): void
    {
        $stmt = $this->pdo->prepare("SELECT SUM(siparis_edilen_adet) as toplam_siparis, SUM(teslim_alinan_adet) as toplam_teslimat FROM satin_alma_siparis_urunleri WHERE po_id = ?");
        $stmt->execute([$poId]);
        $sonuclar = $stmt->fetch(PDO::FETCH_ASSOC);
        $yeniDurum = 'Kısmen Teslim Alındı';
        if ($sonuclar['toplam_teslimat'] >= $sonuclar['toplam_siparis']) {
            $yeniDurum = 'Tamamlandı';
        }
        $this->pdo->prepare("UPDATE satin_alma_siparisleri SET durum = ? WHERE po_id = ?")->execute([$yeniDurum, $poId]);
    }
}
