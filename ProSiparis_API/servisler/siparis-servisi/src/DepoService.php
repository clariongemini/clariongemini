<?php
namespace ProSiparis\Service;

require_once __DIR__ . '/../../../core/EventBusService.php';

use PDO;
use Exception;
use ProSiparis\Core\EventBusService;

class DepoService
{
    private PDO $pdo;
    private EventBusService $eventBus;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->eventBus = new EventBusService();
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

            // E-posta için kullanıcı bilgisini al
            $kullaniciApiUrl = "http://localhost/ProSiparis_API/servisler/auth-servisi/public/internal/kullanici/" . $kullaniciId;
            $kullaniciVerisi = $this->internalApiCall($kullaniciApiUrl);
            $kullaniciEposta = $kullaniciVerisi['veri']['eposta'];

            // Olayı yayınla
            $siparisDetaylari = $this->pdo->query("SELECT * FROM siparis_detaylari WHERE siparis_id = $siparisId")->fetchAll(PDO::FETCH_ASSOC);
            $this->olayYayinla('siparis.kargolandi', [
                'siparis_id' => $siparisId,
                'kullanici_id' => $kullaniciId,
                'kullanici_eposta' => $kullaniciEposta,
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
        $this->eventBus->publish($olayTipi, $veri);
    }

    private function internalApiCall(string $url, string $method = 'GET', ?array $data = null): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("Dahili API çağrısı başarısız oldu: $url - HTTP $httpCode - Yanıt: $response");
        }
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Dahili API'den gelen yanıt JSON formatında değil.");
        }
        return $decoded;
    }

    // ... (diğer depo metodları)
}
