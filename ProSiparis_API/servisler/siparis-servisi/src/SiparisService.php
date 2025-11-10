<?php
namespace ProSiparis\Siparis;

require_once __DIR__ . '/../../../core/EventBusService.php';

use PDO;
use Exception;
use ProSiparis\Core\EventBusService;

class SiparisService
{
    private PDO $pdo;
    private EventBusService $eventBus;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->eventBus = new EventBusService();
    }

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

        // ... (stok optimizasyonu, siparişi veritabanına yazma)

        // v6.1 Zengin Olay Yayınlama
        $siparisDetaylari = $this->pdo->query("SELECT * FROM siparis_detaylari WHERE siparis_id = $siparisId")->fetchAll(PDO::FETCH_ASSOC);
        $zenginUrunler = $this->urunDetaylariniZenginlestir($siparisDetaylari);

        $this->eventBus->publish('siparis.basarili', [
            'siparis_id' => $siparisId,
            'kullanici_id' => $veri['kullanici_id'],
            'kullanici_eposta' => $veri['kullanici_eposta'], // Bu verinin $veri'de geldiğini varsayıyoruz
            'toplam_tutar' => $toplamTutar,
            'kullanilan_kupon_kodu' => $veri['kupon_kodu'] ?? null,
            'urunler' => $zenginUrunler
        ]);

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

    private function urunDetaylariniZenginlestir(array $urunler): array
    {
        $varyantIds = array_column($urunler, 'varyant_id');
        if (empty($varyantIds)) {
            return $urunler;
        }

        $idString = implode(',', $varyantIds);
        $katalogVerisi = $this->internalApiCall("http://katalog-servisi/internal/varyant-detaylari?ids={$idString}");

        $zenginUrunler = [];
        foreach ($urunler as $urun) {
            $urunId = $urun['varyant_id'];
            if (isset($katalogVerisi[$urunId])) {
                $urun['urun_adi'] = $katalogVerisi[$urunId]['urun_adi'];
                $urun['varyant_sku'] = $katalogVerisi[$urunId]['varyant_sku'];
                $urun['kategori_adi'] = $katalogVerisi[$urunId]['kategori_adi'];
            }
            $zenginUrunler[] = $urun;
        }

        return $zenginUrunler;
    }

    private function internalApiCall(string $url): ?array
    {
        $responseJson = @file_get_contents($url);
        if ($responseJson === false) {
            error_log("Dahili Siparis Servisi API çağrısı başarısız oldu: $url");
            return null;
        }
        $response = json_decode($responseJson, true);
        return ($response && isset($response['basarili']) && $response['basarili']) ? $response['veri'] : null;
    }
}
