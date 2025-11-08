<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class CmsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // --- Herkese Açık Metodlar ---

    public function getSayfaBySlug(string $slug): array
    {
        $sql = "SELECT baslik, slug, icerik, meta_baslik, meta_aciklama FROM sayfalar WHERE slug = ? AND aktif_mi = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$slug]);
        $sayfa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sayfa) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Sayfa bulunamadı.'];
        }
        return ['basarili' => true, 'kod' => 200, 'veri' => $sayfa];
    }

    public function getBannerlarByKonum(string $konum): array
    {
        $sql = "SELECT baslik, resim_url_mobil, hedef_url FROM bannerlar WHERE konum = ? AND aktif_mi = 1 ORDER BY sira ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$konum]);
        $bannerlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['basarili' => true, 'kod' => 200, 'veri' => $bannerlar];
    }

    // --- Admin CRUD Metodları ---

    // Sayfalar
    public function listeleSayfalar(): array
    {
        $stmt = $this->pdo->query("SELECT sayfa_id, baslik, slug, aktif_mi, guncellenme_tarihi FROM sayfalar ORDER BY baslik ASC");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function olusturSayfa(array $veri): array
    {
        $sql = "INSERT INTO sayfalar (baslik, slug, icerik, meta_baslik, meta_aciklama, aktif_mi) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $veri['baslik'],
            $veri['slug'],
            $veri['icerik'] ?? '',
            $veri['meta_baslik'] ?? null,
            $veri['meta_aciklama'] ?? null,
            $veri['aktif_mi'] ?? true
        ]);
        return ['basarili' => true, 'kod' => 201, 'veri' => ['sayfa_id' => $this->pdo->lastInsertId()]];
    }

    public function guncelleSayfa(int $id, array $veri): array
    {
        $sql = "UPDATE sayfalar SET baslik = ?, slug = ?, icerik = ?, meta_baslik = ?, meta_aciklama = ?, aktif_mi = ? WHERE sayfa_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $veri['baslik'],
            $veri['slug'],
            $veri['icerik'] ?? '',
            $veri['meta_baslik'] ?? null,
            $veri['meta_aciklama'] ?? null,
            $veri['aktif_mi'] ?? true,
            $id
        ]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sayfa başarıyla güncellendi.'];
    }

    public function silSayfa(int $id): array
    {
        $stmt = $this->pdo->prepare("DELETE FROM sayfalar WHERE sayfa_id = ?");
        $stmt->execute([$id]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sayfa başarıyla silindi.'];
    }

    // Bannerlar
    public function listeleBannerlar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM bannerlar ORDER BY konum, sira ASC");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function olusturBanner(array $veri): array
    {
        $sql = "INSERT INTO bannerlar (baslik, resim_url_mobil, hedef_url, sira, konum, aktif_mi) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $veri['baslik'] ?? null,
            $veri['resim_url_mobil'],
            $veri['hedef_url'] ?? null,
            $veri['sira'] ?? 0,
            $veri['konum'] ?? 'anasayfa_ust',
            $veri['aktif_mi'] ?? true
        ]);
        return ['basarili' => true, 'kod' => 201, 'veri' => ['banner_id' => $this->pdo->lastInsertId()]];
    }

    public function guncelleBanner(int $id, array $veri): array
    {
       $sql = "UPDATE bannerlar SET baslik = ?, resim_url_mobil = ?, hedef_url = ?, sira = ?, konum = ?, aktif_mi = ? WHERE banner_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $veri['baslik'] ?? null,
            $veri['resim_url_mobil'],
            $veri['hedef_url'] ?? null,
            $veri['sira'] ?? 0,
            $veri['konum'] ?? 'anasayfa_ust',
            $veri['aktif_mi'] ?? true,
            $id
        ]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Banner başarıyla güncellendi.'];
    }

    public function silBanner(int $id): array
    {
        $stmt = $this->pdo->prepare("DELETE FROM bannerlar WHERE banner_id = ?");
        $stmt->execute([$id]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Banner başarıyla silindi.'];
    }
}
