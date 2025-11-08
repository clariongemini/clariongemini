<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class SiparisService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function kullaniciSiparisleriniGetir(int $kullaniciId): array
    {
        try {
            $sql = "SELECT id, siparis_tarihi, toplam_tutar, durum FROM siparisler WHERE kullanici_id = ? ORDER BY siparis_tarihi DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kullaniciId]);
            return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş geçmişi getirilirken bir hata oluştu.'];
        }
    }

    public function idIleGetir(int $siparisId, int $kullaniciId): array
    {
        try {
            $sql = "SELECT * FROM siparisler WHERE id = ? AND kullanici_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$siparisId, $kullaniciId]);
            $siparis = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$siparis) {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Sipariş bulunamadı veya bu siparişe erişim yetkiniz yok.'];
            }

            $sql_detay = "SELECT sd.adet, sd.birim_fiyat, uv.varyant_sku, p.urun_adi
                          FROM siparis_detaylari sd
                          JOIN urun_varyantlari uv ON sd.varyant_id = uv.varyant_id
                          JOIN urunler p ON uv.urun_id = p.urun_id
                          WHERE sd.siparis_id = ?";
            $stmt_detay = $this->pdo->prepare($sql_detay);
            $stmt_detay->execute([$siparisId]);
            $siparis['urunler'] = $stmt_detay->fetchAll(PDO::FETCH_ASSOC);

            return ['basarili' => true, 'kod' => 200, 'veri' => $siparis];
        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş detayı getirilirken bir hata oluştu.'];
        }
    }

    public function tumSiparisleriGetir(): array
    {
        try {
            $sql = "SELECT s.id, s.siparis_tarihi, s.toplam_tutar, s.durum, k.ad_soyad, k.eposta
                    FROM siparisler s
                    JOIN kullanicilar k ON s.kullanici_id = k.id
                    ORDER BY s.siparis_tarihi DESC";
            $stmt = $this->pdo->query($sql);
            return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Tüm siparişler getirilirken bir hata oluştu.'];
        }
    }

    public function siparisOlustur(int $kullaniciId, int $fiyatListesiId, array $sepet, int $teslimatAdresiId, int $kargoId, ?string $kuponKodu, float $indirimTutari): array
    {
        if (empty($sepet)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Sepet boş olamaz.'];
        }

        $this->pdo->beginTransaction();
        try {
            $sepetTutar = 0;
            $dogrulanmisUrunler = [];

            foreach ($sepet as $item) {
                $sql = "
                    SELECT vf.fiyat, uv.stok_adedi
                    FROM urun_varyantlari uv
                    JOIN varyant_fiyatlari vf ON uv.varyant_id = vf.varyant_id
                    WHERE uv.varyant_id = ? AND vf.fiyat_listesi_id = ? FOR UPDATE
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$item['varyant_id'], $fiyatListesiId]);
                $varyant = $stmt->fetch();

                if (!$varyant) { throw new Exception("Ürün (ID: {$item['varyant_id']}) için geçerli bir fiyat bulunamadı."); }
                if ($varyant['stok_adedi'] < $item['adet']) { throw new Exception("Stokta yeterli ürün yok (ID: {$item['varyant_id']})."); }

                $birimFiyat = (float)$varyant['fiyat'];
                $sepetTutar += $birimFiyat * $item['adet'];
                $dogrulanmisUrunler[] = ['varyant_id' => $item['varyant_id'], 'adet' => $item['adet'], 'birim_fiyat' => $birimFiyat];
            }

            $kargoUcreti = $this->pdo->query("SELECT ucret FROM kargo_secenekleri WHERE kargo_id = $kargoId")->fetchColumn() ?: 0;
            $toplamTutar = ($sepetTutar + $kargoUcreti) - $indirimTutari;

            $sql = "INSERT INTO siparisler (kullanici_id, teslimat_adresi_id, kargo_id, toplam_tutar, durum) VALUES (?, ?, ?, ?, 'Odendi')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kullaniciId, $teslimatAdresiId, $kargoId, $toplamTutar]);
            $siparisId = $this->pdo->lastInsertId();

            foreach ($dogrulanmisUrunler as $urun) {
                $sqlDetay = "INSERT INTO siparis_detaylari (siparis_id, varyant_id, adet, birim_fiyat) VALUES (?, ?, ?, ?)";
                $stmtDetay = $this->pdo->prepare($sqlDetay);
                $stmtDetay->execute([$siparisId, $urun['varyant_id'], $urun['adet'], $urun['birim_fiyat']]);

                $sqlStok = "UPDATE urun_varyantlari SET stok_adedi = stok_adedi - ? WHERE varyant_id = ?";
                $stmtStok = $this->pdo->prepare($sqlStok);
                $stmtStok->execute([$urun['adet'], $urun['varyant_id']]);
            }

            $this->pdo->commit();

            return ['basarili' => true, 'kod' => 201, 'veri' => ['siparis_id' => $siparisId]];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş oluşturulurken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    public function adminSiparisDurumGuncelle(int $siparisId, string $yeniDurum): array
    {
        $izinVerilenDurumlar = ['Teslim Edildi', 'Iptal Edildi'];
        if (!in_array($yeniDurum, $izinVerilenDurumlar)) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => "Bu durumu güncelleme yetkiniz yok."];
        }
        try {
            $sql = "UPDATE siparisler SET durum = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$yeniDurum, $siparisId]);
            if ($stmt->rowCount() > 0) {
                return ['basarili' => true, 'kod' => 200, 'mesaj' => "Sipariş durumu '{$yeniDurum}' olarak güncellendi."];
            }
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Sipariş bulunamadı veya durum zaten aynı.'];
        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş durumu güncellenirken bir hata oluştu: ' . $e->getMessage()];
        }
    }
}
