<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class TedarikService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // --- Tedarikçi CRUD ---

    public function listeleTedarikciler(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM tedarikciler ORDER BY firma_adi ASC");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function olusturTedarikci(array $veri): array
    {
        $sql = "INSERT INTO tedarikciler (firma_adi, yetkili_kisi, eposta, telefon) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$veri['firma_adi'], $veri['yetkili_kisi'] ?? null, $veri['eposta'] ?? null, $veri['telefon'] ?? null]);
        return ['basarili' => true, 'kod' => 201, 'veri' => ['tedarikci_id' => $this->pdo->lastInsertId()]];
    }

    public function guncelleTedarikci(int $id, array $veri): array
    {
        $sql = "UPDATE tedarikciler SET firma_adi = ?, yetkili_kisi = ?, eposta = ?, telefon = ? WHERE tedarikci_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$veri['firma_adi'], $veri['yetkili_kisi'] ?? null, $veri['eposta'] ?? null, $veri['telefon'] ?? null, $id]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Tedarikçi güncellendi.'];
    }

    public function silTedarikci(int $id): array
    {
        $stmt = $this->pdo->prepare("DELETE FROM tedarikciler WHERE tedarikci_id = ?");
        $stmt->execute([$id]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Tedarikçi silindi.'];
    }

    // --- Satın Alma Siparişi (PO) CRUD ---

    public function listeleSatinAlmaSiparisleri(): array
    {
        $sql = "SELECT po.*, t.firma_adi FROM satin_alma_siparisleri po JOIN tedarikciler t ON po.tedarikci_id = t.tedarikci_id ORDER BY po.siparis_tarihi DESC";
        $stmt = $this->pdo->query($sql);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function olusturSatinAlmaSiparisi(array $veri): array
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO satin_alma_siparisleri (tedarikci_id, siparis_tarihi, beklenen_teslim_tarihi, durum) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$veri['tedarikci_id'], $veri['siparis_tarihi'], $veri['beklenen_teslim_tarihi'] ?? null, $veri['durum'] ?? 'Taslak']);
            $poId = $this->pdo->lastInsertId();

            if (!empty($veri['urunler']) && is_array($veri['urunler'])) {
                foreach ($veri['urunler'] as $urun) {
                    $sqlUrun = "INSERT INTO satin_alma_siparis_urunleri (po_id, varyant_id, siparis_edilen_adet, maliyet_fiyati) VALUES (?, ?, ?, ?)";
                    $stmtUrun = $this->pdo->prepare($sqlUrun);
                    $stmtUrun->execute([$poId, $urun['varyant_id'], $urun['adet'], $urun['maliyet']]);
                }
            }
            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 201, 'veri' => ['po_id' => $poId]];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Satın alma siparişi oluşturulurken hata: ' . $e->getMessage()];
        }
    }
     public function guncelleSatinAlmaSiparisi(int $poId, array $veri): array
    {
        // Not: Bu basit bir güncellemedir. Gerçek bir senaryoda, ürün ekleme/çıkarma/güncelleme işlemleri
        // için daha detaylı bir mantık gerekebilir.
        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE satin_alma_siparisleri SET tedarikci_id = ?, siparis_tarihi = ?, beklenen_teslim_tarihi = ?, durum = ? WHERE po_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$veri['tedarikci_id'], $veri['siparis_tarihi'], $veri['beklenen_teslim_tarihi'], $veri['durum'], $poId]);

            // Mevcut ürünleri silip yenilerini eklemek en basit yöntemdir
            $this->pdo->prepare("DELETE FROM satin_alma_siparis_urunleri WHERE po_id = ?")->execute([$poId]);
             if (!empty($veri['urunler']) && is_array($veri['urunler'])) {
                foreach ($veri['urunler'] as $urun) {
                    $sqlUrun = "INSERT INTO satin_alma_siparis_urunleri (po_id, varyant_id, siparis_edilen_ adet, maliyet_fiyati) VALUES (?, ?, ?, ?)";
                    $stmtUrun = $this->pdo->prepare($sqlUrun);
                    $stmtUrun->execute([$poId, $urun['varyant_id'], $urun['adet'], $urun['maliyet']]);
                }
            }

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Satın alma siparişi güncellendi.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Satın alma siparişi güncellenirken hata: ' . $e->getMessage()];
        }
    }
}
