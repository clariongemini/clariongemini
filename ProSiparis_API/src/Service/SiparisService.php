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

    public function kullaniciSiparisleriniGetir(int $kullaniciId): array
    {
        try {
            $sql = "SELECT id, siparis_tarihi, toplam_tutar, durum FROM siparisler WHERE kullanici_id = ? ORDER BY siparis_tarihi DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kullaniciId]);
            return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Tüm siparişler getirilirken bir hata oluştu.'];
        }
    }

    public function siparisOlustur(int $kullaniciId, array $sepet, int $teslimatAdresiId, int $kargoId, ?string $kuponKodu, float $indirimTutari): array
    {
        if (empty($sepet)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Sepet boş olamaz.'];
        }

        try {
            $this->pdo->beginTransaction();

            $sepetTutar = 0;
            foreach ($sepet as $item) {
                $stmt = $this->pdo->prepare("SELECT fiyat, stok_adedi FROM urun_varyantlari WHERE varyant_id = ? FOR UPDATE");
                $stmt->execute([$item['varyant_id']]);
                $varyant = $stmt->fetch();
                if (!$varyant || $varyant['stok_adedi'] < $item['adet']) {
                    throw new \Exception("Stokta yeterli ürün yok.");
                }
                $sepetTutar += $varyant['fiyat'] * $item['adet'];
            }

            $kargoUcreti = $this->pdo->query("SELECT ucret FROM kargo_secenekleri WHERE kargo_id = $kargoId")->fetchColumn();
            $toplamTutar = ($sepetTutar + $kargoUcreti) - $indirimTutari;

            $sql = "INSERT INTO siparisler (kullanici_id, teslimat_adresi_id, kargo_id, toplam_tutar, indirim_tutari, kullanilan_kupon_kodu, durum) VALUES (?, ?, ?, ?, ?, ?, 'Hazirlaniyor')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kullaniciId, $teslimatAdresiId, $kargoId, $toplamTutar, $indirimTutari, $kuponKodu]);
            $siparisId = $this->pdo->lastInsertId();

            foreach ($sepet as $item) {
                $stmt = $this->pdo->prepare("SELECT fiyat FROM urun_varyantlari WHERE varyant_id = ?");
                $stmt->execute([$item['varyant_id']]);
                $birimFiyat = $stmt->fetchColumn();

                $sql = "INSERT INTO siparis_detaylari (siparis_id, varyant_id, adet, birim_fiyat) VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$siparisId, $item['varyant_id'], $item['adet'], $birimFiyat]);

                $sql = "UPDATE urun_varyantlari SET stok_adedi = stok_adedi - ? WHERE varyant_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$item['adet'], $item['varyant_id']]);
            }

            if ($kuponKodu) {
                (new CouponService($this->pdo))->kuponKullaniminiArtir($kuponKodu);
            }

            $this->pdo->commit();

            $yeniSiparis = $this->idIleGetir($siparisId, $kullaniciId);
            return ['basarili' => true, 'kod' => 201, 'veri' => $yeniSiparis['veri']];

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş oluşturulurken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    public function siparisDurumGuncelle(int $siparisId, array $veri): array
    {
        if (empty($veri)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Güncellenecek veri bulunamadı.'];
        }

        try {
            $alanlar = [];
            $parametreler = [];

            if (isset($veri['durum'])) { $alanlar[] = "durum = ?"; $parametreler[] = $veri['durum']; }
            if (isset($veri['kargo_firmasi'])) { $alanlar[] = "kargo_firmasi = ?"; $parametreler[] = $veri['kargo_firmasi']; }
            if (isset($veri['kargo_takip_kodu'])) { $alanlar[] = "kargo_takip_kodu = ?"; $parametreler[] = $veri['kargo_takip_kodu']; }

            if (empty($alanlar)) return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Güncellenecek alan belirtilmedi.'];

            $sql = "UPDATE siparisler SET " . implode(', ', $alanlar) . " WHERE id = ?";
            $parametreler[] = $siparisId;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($parametreler);

            if ($stmt->rowCount() > 0) {
                $guncellenenSiparis = $this->pdo->query("SELECT * FROM siparisler WHERE id = $siparisId")->fetch();
                $kullanici = $this->pdo->query("SELECT id, eposta FROM kullanicilar WHERE id = " . $guncellenenSiparis['kullanici_id'])->fetch();

                if ($veri['durum'] === 'Kargoya Verildi' && !empty($guncellenenSiparis['kargo_takip_kodu'])) {
                    (new MailService())->sendShippingConfirmation($kullanici['eposta'], $guncellenenSiparis);
                }

                return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sipariş başarıyla güncellendi.'];
            }
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Güncellenecek sipariş bulunamadı.'];

        } catch (\Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş güncellenirken bir hata oluştu: ' . $e->getMessage()];
        }
    }
}
