<?php
namespace ProSiparis\Iade;

use PDO;
use ProSiparis\Core\EventBusService;

class IadeService
{
    private PDO $pdo;
    private EventBusService $eventBus;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // EventBusService'i burada başlatmak yerine, gerektiğinde çağırabiliriz
        // veya dependency injection ile alabiliriz. Şimdilik böyle kalsın.
        //$this->eventBus = new EventBusService();
    }

    private function yetkiKontrol(string $gerekliYetki): bool
    {
        $yetkiler = $_SERVER['HTTP_X_PERMISSIONS'] ?? '';
        return in_array($gerekliYetki, explode(',', $yetkiler));
    }

    private function logYaz(int $iadeId, string $eylem, string $aciklama): void
    {
        $yapanKullaniciId = $_SERVER['HTTP_X_USER_ID'] ?? null;
        $stmt = $this->pdo->prepare(
            "INSERT INTO iade_gecmisi_loglari (iade_id, yapan_kullanici_id, eylem, aciklama) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$iadeId, $yapanKullaniciId, $eylem, $aciklama]);
    }

    public function listeleIadeler(): array
    {
        if (!$this->yetkiKontrol('iade_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }
        $stmt = $this->pdo->query("
            SELECT it.iade_id, it.siparis_id, it.durum, it.talep_tarihi, k.ad_soyad
            FROM iade_talepleri it
            LEFT JOIN `auth_servis_veritabani`.kullanicilar k ON it.kullanici_id = k.id
            ORDER BY it.talep_tarihi DESC
        ");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function getIadeDetay(int $iadeId): array
    {
        if (!$this->yetkiKontrol('iade_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM iade_talepleri WHERE iade_id = ?");
        $stmt->execute([$iadeId]);
        $iade = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$iade) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'İade talebi bulunamadı.'];
        }

        $iade['urunler'] = $this->pdo->query("SELECT * FROM iade_urunleri WHERE iade_id = $iadeId")->fetchAll(PDO::FETCH_ASSOC);

        return ['basarili' => true, 'kod' => 200, 'veri' => $iade];
    }

    public function getIadeGecmisi(int $iadeId): array
    {
        if (!$this->yetkiKontrol('iade_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }
        $stmt = $this->pdo->prepare("
            SELECT igl.*, k.ad_soyad
            FROM iade_gecmisi_loglari igl
            LEFT JOIN `auth_servis_veritabani`.kullanicilar k ON igl.yapan_kullanici_id = k.id
            WHERE igl.iade_id = ?
            ORDER BY igl.tarih DESC
        ");
        $stmt->execute([$iadeId]);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function guncelleIadeDurumu(int $iadeId, array $veri): array
    {
        if (!$this->yetkiKontrol('iade_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }

        $yeniDurum = $veri['yeni_durum'] ?? null;
        if (empty($yeniDurum)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'yeni_durum alanı zorunludur.'];
        }

        $stmt = $this->pdo->prepare("SELECT durum FROM iade_talepleri WHERE iade_id = ?");
        $stmt->execute([$iadeId]);
        $mevcutDurum = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("UPDATE iade_talepleri SET durum = ? WHERE iade_id = ?");
        $stmt->execute([$yeniDurum, $iadeId]);

        $this->logYaz($iadeId, 'DURUM_GUNCELLEME', "Durum '$mevcutDurum' iken '$yeniDurum' olarak değiştirildi.");

        // Ödeme yapıldı durumunda olay yayınla
        if ($yeniDurum === 'Odeme Yapildi') {
            // ... (gerekli bilgileri topla)
            // $this->eventBus->publish('iade.odeme_basarili', [...]);
        }

        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'İade durumu güncellendi.'];
    }
}
