<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class IadeService
{
    private PDO $pdo;
    private MailService $mailService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->mailService = new MailService();
    }

    public function kullaniciTalepleriniListele(int $kullaniciId): array
    {
        $sql = "SELECT iade_id, siparis_id, durum, olusturma_tarihi FROM iade_talepleri WHERE kullanici_id = ? ORDER BY olusturma_tarihi DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kullaniciId]);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function iadeTalebiOlustur(int $kullaniciId, array $veri): array
    {
        if (empty($veri['siparis_id']) || empty($veri['sebep']) || empty($veri['urunler'])) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Sipariş ID, iade sebebi ve ürün listesi zorunludur.'];
        }

        $stmt = $this->pdo->prepare("SELECT durum, siparis_tarihi FROM siparisler WHERE id = ? AND kullanici_id = ?");
        $stmt->execute([$veri['siparis_id'], $kullaniciId]);
        $siparis = $stmt->fetch();
        if (!$siparis || $siparis['durum'] !== 'Teslim Edildi') {
             return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Sadece "Teslim Edildi" durumundaki siparişler için iade talebi oluşturulabilir.'];
        }

        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO iade_talepleri (siparis_id, kullanici_id, sebep) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$veri['siparis_id'], $kullaniciId, $veri['sebep']]);
            $iadeId = $this->pdo->lastInsertId();

            foreach ($veri['urunler'] as $urun) {
                 $sqlUrun = "INSERT INTO iade_urunleri (iade_id, varyant_id, adet, durum) VALUES (?, ?, ?, 'Satılabilir')";
                 $this->pdo->prepare($sqlUrun)->execute([$iadeId, $urun['varyant_id'], $urun['adet']]);
            }

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 201, 'veri' => ['iade_id' => $iadeId]];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'İade talebi oluşturulurken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    public function listeleTalepler(array $filtreler): array
    {
        $sql = "SELECT i.*, k.ad_soyad FROM iade_talepleri i JOIN kullanicilar k ON i.kullanici_id = k.id";
        $params = [];
        if (!empty($filtreler['durum'])) {
            $sql .= " WHERE i.durum = ?";
            $params[] = $filtreler['durum'];
        }
        $sql .= " ORDER BY i.olusturma_tarihi DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function iadeDurumGuncelle(int $iadeId, string $yeniDurum): array
    {
        $izinVerilenDurumlar = ['Onaylandı', 'Reddedildi'];
        if (!in_array($yeniDurum, $izinVerilenDurumlar)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Geçersiz veya yetkiniz olmayan durum.'];
        }
        $sql = "UPDATE iade_talepleri SET durum = ? WHERE iade_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$yeniDurum, $iadeId]);
        // Müşteriye bildirim e-postası gönder
        return ['basarili' => true, 'kod' => 200, 'mesaj' => "İade durumu '{$yeniDurum}' olarak güncellendi."];
    }

    public function iadeTeslimAl(int $iadeId, array $urunler): array
    {
        $this->pdo->beginTransaction();
        try {
            foreach ($urunler as $urun) {
                $this->pdo->prepare("UPDATE iade_urunleri SET durum = ? WHERE iade_id = ? AND varyant_id = ?")->execute([$urun['durum'], $iadeId, $urun['varyant_id']]);
                if ($urun['durum'] === 'Satılabilir') {
                    $this->pdo->prepare("UPDATE urun_varyantlari SET stok_adedi = stok_adedi + ? WHERE varyant_id = ?")->execute([$urun['adet'], $urun['varyant_id']]);
                }
            }
            $this->pdo->prepare("UPDATE iade_talepleri SET durum = 'Depoya Ulaştı' WHERE iade_id = ?")->execute([$iadeId]);
            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'İade teslim alındı ve stoklar güncellendi.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'İade teslim alınırken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    public function iadeOdemeYap(int $iadeId): array
    {
        // Gerçek bir uygulamada burada IyzicoService->refund() çağrılır.
        $iadeBasarili = true;
        if (!$iadeBasarili) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Para iadesi işlemi başarısız oldu.'];
        }
        $sql = "UPDATE iade_talepleri SET durum = 'İade Tamamlandı' WHERE iade_id = ?";
        $this->pdo->prepare($sql)->execute([$iadeId]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Para iadesi yapıldı ve iade süreci tamamlandı.'];
    }
}
