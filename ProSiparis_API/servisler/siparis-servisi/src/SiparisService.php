<?php
namespace ProSiparis\Siparis;

use PDO;
use Exception;

class SiparisService
{
    // ...

    public function odemeBaslat(array $veri): array
    {
        // ... (sepet, adres vb. verileri al)

        $indirimTutari = 0;
        if (!empty($veri['kupon_kodu'])) {
            $kuponSonuc = $this->kuponDogrula($veri['kupon_kodu']);
            if (!$kuponSonuc['basarili']) {
                return ['basarili' => false, 'kod' => 400, 'mesaj' => $kuponSonuc['mesaj']];
            }
            // İndirim hesaplama mantığı...
            // $indirimTutari = ...
        }

        // Sipariş oluşturma ve ödeme seansı başlatma...
        // ...

        return ['basarili' => true, /* ... */];
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
