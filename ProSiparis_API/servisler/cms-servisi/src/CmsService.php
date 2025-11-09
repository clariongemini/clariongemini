<?php
namespace ProSiparis\Cms;

use PDO;

class CmsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getSayfaBySlug(string $slug): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM sayfalar WHERE slug = ?");
        $stmt->execute([$slug]);
        $sayfa = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['basarili' => true, 'kod' => 200, 'veri' => $sayfa];
    }

    public function listeleSayfalar(): array
    {
        $stmt = $this->pdo->query("SELECT sayfa_id, baslik, slug FROM sayfalar ORDER BY baslik ASC");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function listeleAktifBannerlar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM bannerlar WHERE aktif = 1");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    // ... Diğer CRUD metodları (olustur/guncelle/sil) buraya eklenecek.
}
