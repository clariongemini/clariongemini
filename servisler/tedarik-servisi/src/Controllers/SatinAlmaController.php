<?php
namespace ProSiparis\Tedarik\Controllers;

use PDO;
use Exception;

class SatinAlmaController
{
    // ... (diğer metodlar ve construct)

    public function teslimAl(int $poId): void
    {
        $gelenUrunler = $this->requestData['teslim_edilen_urunler'] ?? [];
        if (empty($gelenUrunler) || !is_array($gelenUrunler)) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Teslim edilen ürün bilgisi eksik veya geçersiz.'], 400);
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $aciklamaLog = "Mal kabul yapıldı. Gelen ürünler: ";
            $zenginOlayVerisi = [];

            $sqlSelectUrun = "SELECT siparis_edilen_adet, teslim_alinan_adet, maliyet_fiyati FROM tedarik_siparis_urunleri WHERE po_id = :po_id AND varyant_id = :varyant_id";
            $stmtSelectUrun = $this->pdo->prepare($sqlSelectUrun);

            $sqlUpdateUrun = "UPDATE tedarik_siparis_urunleri SET teslim_alinan_adet = teslim_alinan_adet + :gelen_adet WHERE po_id = :po_id AND varyant_id = :varyant_id";
            $stmtUpdateUrun = $this->pdo->prepare($sqlUpdateUrun);

            foreach ($gelenUrunler as $urun) {
                if (empty($urun['varyant_id']) || !isset($urun['teslim_alinan_adet']) || $urun['teslim_alinan_adet'] <= 0) {
                    throw new Exception("Geçersiz ürün verisi.");
                }

                // 1. Ürün bilgilerini ve kalan adeti kontrol et
                $stmtSelectUrun->execute([':po_id' => $poId, ':varyant_id' => $urun['varyant_id']]);
                $siparisUrunu = $stmtSelectUrun->fetch(PDO::FETCH_ASSOC);

                if (!$siparisUrunu) {
                    throw new Exception("Siparişte bulunmayan bir ürün (Varyant ID: {$urun['varyant_id']}) teslim alınamaz.");
                }

                $kalanAdet = $siparisUrunu['siparis_edilen_adet'] - $siparisUrunu['teslim_alinan_adet'];
                if ($urun['teslim_alinan_adet'] > $kalanAdet) {
                    throw new Exception("Kalan adetten ({$kalanAdet}) fazla ürün (Varyant ID: {$urun['varyant_id']}) teslim alamazsınız.");
                }

                // 2. Teslim alınan adeti güncelle
                $stmtUpdateUrun->execute([
                    ':gelen_adet' => $urun['teslim_alinan_adet'],
                    ':po_id' => $poId,
                    ':varyant_id' => $urun['varyant_id']
                ]);

                // 3. Log ve Olay için detayları topla
                $aciklamaLog .= "Varyant ID {$urun['varyant_id']} - Adet: {$urun['teslim_alinan_adet']}; ";
                $zenginOlayVerisi[] = [
                    'varyant_id' => $urun['varyant_id'],
                    'gelen_adet' => $urun['teslim_alinan_adet'],
                    'maliyet_fiyati' => $siparisUrunu['maliyet_fiyati'] // Zengin olay için maliyeti ekle
                ];
            }

            // 4. Siparişin yeni durumunu belirle
            $sqlCheckDurum = "SELECT SUM(siparis_edilen_adet) as toplam_siparis, SUM(teslim_alinan_adet) as toplam_teslim FROM tedarik_siparis_urunleri WHERE po_id = :po_id";
            $stmtCheckDurum = $this->pdo->prepare($sqlCheckDurum);
            $stmtCheckDurum->execute([':po_id' => $poId]);
            $durumBilgisi = $stmtCheckDurum->fetch(PDO::FETCH_ASSOC);

            $yeniDurum = ($durumBilgisi['toplam_teslim'] >= $durumBilgisi['toplam_siparis']) ? 'tamamlandi' : 'kismen_teslim_alindi';

            $sqlUpdateDurum = "UPDATE tedarik_siparisleri SET durum = :durum WHERE po_id = :po_id";
            $stmtUpdateDurum = $this->pdo->prepare($sqlUpdateDurum);
            $stmtUpdateDurum->execute([':durum' => $yeniDurum, ':po_id' => $poId]);

            // 5. Denetim kaydı ve Olay yayınlama
            $this->logGecmis($poId, 'MAL_KABUL', $aciklamaLog);

            // TODO: EventBusService implemente edildiğinde bu satır açılacak
            // $eventPayload = ['po_id' => $poId, 'depo_id' => $hedef_depo_id, 'teslim_alan_kullanici_id' => $this->yapanKullaniciId, 'gelen_urunler' => $zenginOlayVerisi];
            // $this->eventBus->publish('tedarik.mal_kabul_yapildi', $eventPayload);

            $this->pdo->commit();
            $this->jsonResponse(['durum' => 'basarili', 'mesaj' => 'Ürünler başarıyla teslim alındı. Sipariş durumu: ' . $yeniDurum]);

        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Teslim alma işlemi sırasında bir hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    // ... (diğer metodlar)
}
// Not: Diğer metodlar (`listele`, `olustur`, `detayGetir`, vs.) yerinde duruyor, sadece bu örnekte kısalık için çıkarıldı.
