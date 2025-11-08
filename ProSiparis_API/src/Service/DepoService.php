<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class DepoService
{
    private PDO $pdo;
    private MailService $mailService;
    private EnvanterService $envanterService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->mailService = new MailService();
        $this->envanterService = new EnvanterService($pdo);
    }

    public function beklenenTeslimatlariListele(): array { /* ... */ }

    public function teslimatAl(int $poId, array $urunler, int $kullaniciId): array
    {
        $this->pdo->beginTransaction();
        try {
            foreach ($urunler as $urun) {
                $stmtMaliyet = $this->pdo->prepare("SELECT maliyet_fiyati FROM satin_alma_siparis_urunleri WHERE po_id = ? AND varyant_id = ?");
                $stmtMaliyet->execute([$poId, $urun['varyant_id']]);
                $maliyet = $stmtMaliyet->fetchColumn();
                if ($maliyet === false) throw new Exception("Satın alma siparişinde ürün bulunamadı.");

                $this->envanterService->stokGuncelle($urun['varyant_id'], 'satin_alma', $urun['gelen_adet'], $poId, (float)$maliyet, $kullaniciId);

                $this->pdo->prepare("UPDATE satin_alma_siparis_urunleri SET teslim_alinan_adet = teslim_alinan_adet + ? WHERE po_id = ? AND varyant_id = ?")
                          ->execute([$urun['gelen_adet'], $poId, $urun['varyant_id']]);
            }
            $this->guncelleSatinAlmaSiparisiDurumu($poId);
            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Teslimat alındı ve envanter kaydı oluşturuldu.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Teslimat alınırken hata: ' . $e->getMessage()];
        }
    }

    public function kargoyaVer(int $siparisId, array $kargoBilgileri, int $kullaniciId): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmtDetay = $this->pdo->prepare("SELECT varyant_id, adet FROM siparis_detaylari WHERE siparis_id = ?");
            $stmtDetay->execute([$siparisId]);
            $urunler = $stmtDetay->fetchAll(PDO::FETCH_ASSOC);

            foreach ($urunler as $urun) {
                $stmtAom = $this->pdo->prepare("SELECT agirlikli_ortalama_maliyet FROM urun_varyantlari WHERE varyant_id = ?");
                $stmtAom->execute([$urun['varyant_id']]);
                $aom = $stmtAom->fetchColumn();

                $this->pdo->prepare("UPDATE siparis_detaylari SET maliyet_fiyati = ? WHERE siparis_id = ? AND varyant_id = ?")
                          ->execute([$aom, $siparisId, $urun['varyant_id']]);

                $this->envanterService->stokGuncelle($urun['varyant_id'], 'satis', -$urun['adet'], $siparisId, (float)$aom, $kullaniciId);
            }

            $this->pdo->prepare("UPDATE siparisler SET durum = 'Kargoya Verildi', kargo_firmasi = ?, kargo_takip_kodu = ? WHERE id = ?")
                      ->execute([$kargoBilgileri['firma'], $kargoBilgileri['takip_kodu'], $siparisId]);

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sipariş kargoya verildi ve envanter hareketleri kaydedildi.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş kargolanırken hata: ' . $e->getMessage()];
        }
    }

    private function guncelleSatinAlmaSiparisiDurumu(int $poId): void { /* ... */ }
}
