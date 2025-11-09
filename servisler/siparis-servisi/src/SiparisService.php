<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class SiparisService
{
    private PDO $pdo;
    private EnvanterService $envanterService; // Bu artık Envanter-Servisi'ne bir olayla bildirilecek

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function siparisOlustur(int $kullaniciId, int $fiyatListesiId, array $sepet, /*...diğer parametreler...*/): array
    {
        $this->pdo->beginTransaction();
        try {
            // Dahili API çağrısı ile kullanıcı e-postasını al
            $kullaniciApiUrl = "http://localhost/ProSiparis_API/servisler/auth-servisi/public/internal/kullanici/" . $kullaniciId;
            $kullaniciVerisi = $this->internalApiCall($kullaniciApiUrl);
            $kullaniciEposta = $kullaniciVerisi['veri']['eposta'];

            // ... (mevcut sipariş oluşturma mantığı: toplam tutar hesaplama, siparisler ve siparis_detaylari tablolarına yazma)

            // E-posta göndermek veya stok düşmek yerine, OLAY YAYINLA
            $this->olayYayinla('siparis.basarili', [
                'siparis_id' => $siparisId,
                'kullanici_id' => $kullaniciId,
                'kullanici_eposta' => $kullaniciEposta,
                'tutar' => $toplamTutar,
                'urunler' => $sepet // Envanter servisinin stok düşmesi için
            ]);

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 201, 'veri' => ['siparis_id' => $siparisId]];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş oluşturulurken hata: ' . $e->getMessage()];
        }
    }

    private function olayYayinla(string $olayTipi, array $veri): void
    {
        // Bu metodun, merkezi bir olay günlüğü veritabanına yazdığını varsayıyoruz.
        // Bu, farklı servislerin veritabanlarını ayırdığımızda önemli olacak.
        $sql = "INSERT INTO olay_gunlugu (olay_tipi, veri) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$olayTipi, json_encode($veri)]);
    }

    // ... (diğer sipariş metodları)
}
