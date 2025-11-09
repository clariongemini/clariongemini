<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class PaymentService
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function odemeBaslat(int $kullaniciId, int $fiyatListesiId, array $veri): array
    {
        try {
            // 1. Fiyatları Katalog-Servisi'nden doğrula
            $varyantIds = array_column($veri['sepet'], 'varyant_id');
            $katalogApiUrl = "http://localhost/ProSiparis_API/servisler/katalog-servisi/public/internal/katalog/varyantlar?ids=" . implode(',', $varyantIds) . "&fiyatListesiId=" . $fiyatListesiId;
            $fiyatlarVerisi = $this->internalApiCall($katalogApiUrl);
            $dogrulanmisFiyatlar = $fiyatlarVerisi['veri'];

            // Sepet tutarını doğrulanmış fiyatlar üzerinden HESAPLA
            $sepetTutari = 0;
            $sepetMiktarlari = array_column($veri['sepet'], 'adet', 'varyant_id');
            foreach ($dogrulanmisFiyatlar as $varyant) {
                $sepetTutari += $varyant['fiyat'] * $sepetMiktarlari[$varyant['id']];
            }

            // 2. Kuponu Ana Monolith'ten doğrula
            $indirimTutari = 0;
            if (!empty($veri['kupon_kodu'])) {
                $kuponApiUrl = "http://localhost/ProSiparis_API/public/index.legacy.php/internal/legacy/kupon-dogrula";
                $kuponSonuc = $this->internalApiCall($kuponApiUrl, 'POST', [
                    'kupon_kodu' => $veri['kupon_kodu'],
                    'sepet_tutari' => $sepetTutari
                ]);
                $indirimTutari = $kuponSonuc['veri']['indirim_tutari'];
            }

            // ... (Iyzico işlemleri ve sepet tutarı yeniden hesaplama)

            return ['basarili' => true, 'kod' => 200, 'veri' => ['checkoutFormContent' => '...']];
        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ödeme başlatılırken hata: ' . $e->getMessage()];
        }
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
            throw new Exception("Internal API çağrısı başarısız oldu: $url - HTTP $httpCode - Yanıt: $response");
        }
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Internal API'den gelen yanıt JSON formatında değil.");
        }
        return $decoded;
    }

    // ... (callbackDogrula metodu)
}
