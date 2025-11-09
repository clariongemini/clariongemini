<?php
namespace ProSiparis\Tedarik;

use PDO;
use Exception;

class TedarikService
{
    private PDO $pdo;
    private PDO $eventPdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Olay günlüğü için merkezi veritabanına ayrı bir bağlantı.
        // Bu bilgiler normalde bir konfigürasyon dosyasından gelir.
        $this->eventPdo = new PDO('mysql:host=db;dbname=prosiparis_core', 'user', 'password');
        $this->eventPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function publishEvent(string $eventType, array $data): void
    {
        $sql = "INSERT INTO olay_gunlugu (olay_tipi, veri) VALUES (?, ?)";
        $stmt = $this->eventPdo->prepare($sql);
        $stmt->execute([$eventType, json_encode($data)]);
    }

    // --- Tedarikçi CRUD ---
    // (Metodlar monolitteki ile aynı kalır, namespace değişikliği yapılır)
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

    // --- Satın Alma Siparişi (PO) ---
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
            $stmt->execute([$veri['tedarikci_id'], $veri['siparis_tarihi'], $veri['beklenen_teslim_tarihi'] ?? null, 'Bekleniyor']);
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
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Hata: ' . $e->getMessage()];
        }
    }

    public function guncelleSatinAlmaSiparisi(int $poId, array $veri): array
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE satin_alma_siparisleri SET tedarikci_id = ?, siparis_tarihi = ?, beklenen_teslim_tarihi = ?, durum = ? WHERE po_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$veri['tedarikci_id'], $veri['siparis_tarihi'], $veri['beklenen_teslim_tarihi'], $veri['durum'], $poId]);

            $this->pdo->prepare("DELETE FROM satin_alma_siparis_urunleri WHERE po_id = ?")->execute([$poId]);

            if (!empty($veri['urunler']) && is_array($veri['urunler'])) {
                foreach ($veri['urunler'] as $urun) {
                    $sqlUrun = "INSERT INTO satin_alma_siparis_urunleri (po_id, varyant_id, siparis_edilen_adet, maliyet_fiyati) VALUES (?, ?, ?, ?)";
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


    // --- Depo Operasyonları ---

    public function listeleBeklenenTeslimatlar(): array
    {
        $sql = "SELECT po.po_id, po.siparis_tarihi, t.firma_adi FROM satin_alma_siparisleri po JOIN tedarikciler t ON po.tedarikci_id = t.tedarikci_id WHERE po.durum = 'Bekleniyor' ORDER BY po.beklenen_teslim_tarihi ASC";
        $stmt = $this->pdo->query($sql);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function teslimatAl(int $poId, array $gelenUrunler, int $kullaniciId): array
    {
        // Not: Gelen ürünlerin doğruluğu (gelen_adet <= siparis_edilen_adet gibi)
        // bu basit örnekte kontrol edilmemiştir.
        $this->pdo->beginTransaction();
        try {
            // PO durumunu güncelle
            $stmt = $this->pdo->prepare("UPDATE satin_alma_siparisleri SET durum = 'Tamamlandı', teslim_alinma_tarihi = NOW() WHERE po_id = ?");
            $stmt->execute([$poId]);

            // Event verisini hazırla
            $eventData = [
                "kullanici_id" => $kullaniciId,
                "po_id" => $poId,
                "urunler" => []
            ];
            foreach ($gelenUrunler as $urun) {
                 $eventData['urunler'][] = [
                    "varyant_id" => $urun['varyant_id'],
                    "gelen_adet" => $urun['gelen_adet'],
                    "maliyet" => $urun['maliyet']
                ];
            }

            // Olayı yayınla
            $this->publishEvent('tedarik.mal_kabul_yapildi', $eventData);

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Teslimat başarıyla alındı ve stok güncelleme olayı yayınlandı.'];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Teslimat alınırken hata: ' . $e->getMessage()];
        }
    }
}
