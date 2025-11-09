<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class DepoService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function kargoyaVer(int $siparisId, array $kargoBilgileri, int $kullaniciId): array
    {
        $this->pdo->beginTransaction();
        try {
            // Sipariş durumunu güncelle
            $stmt = $this->pdo->prepare("UPDATE siparisler SET durum = 'Kargoya Verildi', kargo_firmasi = ?, kargo_takip_kodu = ? WHERE id = ?");
            $stmt->execute([$kargoBilgileri['firma'], $kargoBilgileri['takip_kodu'], $siparisId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Güncellenecek sipariş bulunamadı.");
            }

            // Olayı yayınla
            $siparisDetaylari = $this->pdo->query("SELECT * FROM siparis_detaylari WHERE siparis_id = $siparisId")->fetchAll(PDO::FETCH_ASSOC);
            $this->olayYayinla('siparis.kargolandi', [
                'siparis_id' => $siparisId,
                'kullanici_id' => $kullaniciId, // Bu bilgi sipariş tablosundan da alınabilir
                'kullanici_eposta' => '... eposta bilgisi ...',
                'kargo_firmasi' => $kargoBilgileri['firma'],
                'takip_kodu' => $kargoBilgileri['takip_kodu'],
                'urunler' => $siparisDetaylari
            ]);

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sipariş kargoya verildi olarak güncellendi.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş kargolanırken hata: ' . $e->getMessage()];
        }
    }

    private function olayYayinla(string $olayTipi, array $veri): void
    {
        $sql = "INSERT INTO olay_gunlugu (olay_tipi, veri) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$olayTipi, json_encode($veri)]);
    }

    // ... (diğer depo metodları)
}
