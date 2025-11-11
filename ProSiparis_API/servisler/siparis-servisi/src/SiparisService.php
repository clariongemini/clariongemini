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

    // --- v7.5 Admin Panel Metodları ---

    private function yetkiKontrol(string $gerekliYetki): bool
    {
        $yetkiler = $_SERVER['HTTP_X_PERMISSIONS'] ?? '';
        return in_array($gerekliYetki, explode(',', $yetkiler));
    }

    public function listeleSiparisler(): array
    {
        if (!$this->yetkiKontrol('siparis_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }
        $stmt = $this->pdo->query("
            SELECT s.siparis_id, k.ad_soyad, s.siparis_tarihi, s.toplam_tutar, s.durum
            FROM siparisler s
            JOIN `auth-veritabani`.kullanicilar k ON s.kullanici_id = k.id
            ORDER BY s.siparis_tarihi DESC
        ");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function getSiparisDetay(int $siparisId): array
    {
        if (!$this->yetkiKontrol('siparis_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM siparisler WHERE siparis_id = ?");
        $stmt->execute([$siparisId]);
        $siparis = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$siparis) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Sipariş bulunamadı.'];
        }

        // Diğer detayları da ekle (adres, ürünler vb.)
        $siparis['adres'] = $this->pdo->query("SELECT * FROM teslimat_adresleri WHERE adres_id = {$siparis['teslimat_adresi_id']}")->fetch(PDO::FETCH_ASSOC);
        $siparisDetaylari = $this->pdo->query("SELECT * FROM siparis_detaylari WHERE siparis_id = $siparisId")->fetchAll(PDO::FETCH_ASSOC);
        $siparis['urunler'] = $this->urunDetaylariniZenginlestir($siparisDetaylari);

        return ['basarili' => true, 'kod' => 200, 'veri' => $siparis];
    }

    public function guncelleSiparisDurumu(int $siparisId, array $veri): array
    {
        if (!$this->yetkiKontrol('siparis_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }

        $yeniDurum = $veri['yeni_durum'] ?? null;
        if (empty($yeniDurum)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'yeni_durum alanı zorunludur.'];
        }

        $stmt = $this->pdo->prepare("UPDATE siparisler SET durum = ? WHERE siparis_id = ?");
        $stmt->execute([$yeniDurum, $siparisId]);

        // Olay yayınla
        $this->eventBus->publish('siparis.durum.guncellendi', ['siparis_id' => $siparisId, 'yeni_durum' => $yeniDurum]);

        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sipariş durumu güncellendi.'];
    }

    public function ekleKargoBilgisi(int $siparisId, array $veri): array
    {
        if (!$this->yetkiKontrol('siparis_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }

        $kargoTasiyici = $veri['kargo_tasiyici'] ?? null;
        $takipNo = $veri['takip_no'] ?? null;
        if (empty($kargoTasiyici) || empty($takipNo)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'kargo_tasiyici ve takip_no alanları zorunludur.'];
        }

        $stmt = $this->pdo->prepare("INSERT INTO kargo_bilgileri (siparis_id, kargo_sirketi, takip_numarasi) VALUES (?, ?, ?)");
        $stmt->execute([$siparisId, $kargoTasiyici, $takipNo]);

        // Sipariş durumunu da "kargoya_verildi" olarak güncelle
        $this->guncelleSiparisDurumu($siparisId, ['yeni_durum' => 'kargoya_verildi']);

        // Zengin olay yayınla
        $stmt = $this->pdo->prepare("SELECT kullanici_id FROM siparisler WHERE siparis_id = ?");
        $stmt->execute([$siparisId]);
        $kullaniciId = $stmt->fetchColumn();

        $this->eventBus->publish('siparis.kargolandi', [
            'siparis_id' => $siparisId,
            'kullanici_id' => $kullaniciId,
            'kargo_tasiyici' => $kargoTasiyici,
            'takip_no' => $takipNo
        ]);

        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Kargo bilgisi eklendi ve sipariş durumu güncellendi.'];
    }
}
