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

    /**
     * v5.2: robots.txt içeriğini veritabanından alır ve basar.
     */
    public function getRobotsTxt(): void
    {
        $stmt = $this->pdo->prepare("SELECT ayar_degeri FROM site_ayarlari WHERE ayar_anahtari = 'robots_txt_icerigi'");
        $stmt->execute();
        $icerik = $stmt->fetchColumn();

        header("Content-Type: text/plain; charset=utf-8");
        echo $icerik ?: ''; // Değer yoksa boş string bas
    }

    /**
     * v5.2: robots.txt içeriğini günceller.
     */
    public function updateRobotsTxt(string $icerik): array
    {
        $sql = "UPDATE site_ayarlari SET ayar_degeri = ? WHERE ayar_anahtari = 'robots_txt_icerigi'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$icerik]);

        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'robots.txt başarıyla güncellendi.'];
    }

    /**
     * v5.2: Dahili kullanım için tüm sayfaların slug'larını listeler.
     */
    public function listeleSayfaSluglari(): array
    {
        $stmt = $this->pdo->query("SELECT slug FROM sayfalar");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
}
