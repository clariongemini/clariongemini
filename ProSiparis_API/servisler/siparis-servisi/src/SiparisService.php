<?php
namespace ProSiparis\Siparis;

use PDO;
use Exception;

class SiparisService
{
    // ...

    public function siparisOlustur(array $veri): array
    {
        // ... (kupon doğrulama ve indirim hesaplama)
        if (!empty($veri['kupon_kodu'])) {
            $kuponSonuc = $this->kuponDogrula($veri['kupon_kodu']);
            if (!$kuponSonuc['basarili']) {
                return $kuponSonuc;
            }
            // ... indirim hesapla
        }

        // ... (stok optimizasyonu, siparişi veritabanına yazma, olay yayınlama)

        return ['basarili' => true, 'kod' => 201, 'veri' => ['siparis_id' => $siparisId]];
    }

    private function kuponDogrula(string $kuponKodu): array
    {
        $url = 'http://kupon-servisi/internal/kupon/dogrula';
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode(['kupon_kodu' => $kuponKodu]),
            ],
        ];
        $context  = stream_context_create($options);
        $responseJson = @file_get_contents($url, false, $context);

        if ($responseJson === false) {
            return ['basarili' => false, 'mesaj' => 'Kupon servisine ulaşılamadı.'];
        }

        return json_decode($responseJson, true);
    }

    // ... (diğer metodlar)
}
