<?php
namespace ProSiparis\Service;

use PDO;

class UrunService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Tüm ürünleri veritabanından getirir.
     * @return array
     */
    public function tumunuGetir(): array
    {
        try {
            $sql = "SELECT id, urun_adi, aciklama, fiyat, resim_url FROM urunler ORDER BY id DESC";
            $stmt = $this->pdo->query($sql);
            $urunler = $stmt->fetchAll();
            return ['basarili' => true, 'kod' => 200, 'veri' => $urunler];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürünler getirilirken bir veritabanı hatası oluştu.'];
        }
    }

    /**
     * ID'ye göre tek bir ürünü getirir.
     * @param int $id
     * @return array
     */
    public function idIleGetir(int $id): array
    {
        try {
            $sql = "SELECT id, urun_adi, aciklama, fiyat, resim_url FROM urunler WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $urun = $stmt->fetch();

            if ($urun) {
                return ['basarili' => true, 'kod' => 200, 'veri' => $urun];
            } else {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Belirtilen ID\'ye sahip ürün bulunamadı.'];
            }
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün detayı getirilirken bir veritabanı hatası oluştu.'];
        }
    }

    /**
     * Yeni bir ürün oluşturur.
     * @param array $veri
     * @return array
     */
    public function urunOlustur(array $veri): array
    {
        if (empty($veri['urun_adi']) || !isset($veri['fiyat'])) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Ürün adı ve fiyat alanları zorunludur.'];
        }

        try {
            $sql = "INSERT INTO urunler (urun_adi, aciklama, fiyat, resim_url) VALUES (:urun_adi, :aciklama, :fiyat, :resim_url)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':urun_adi' => $veri['urun_adi'],
                ':aciklama' => $veri['aciklama'] ?? null,
                ':fiyat' => $veri['fiyat'],
                ':resim_url' => $veri['resim_url'] ?? null,
            ]);
            return ['basarili' => true, 'kod' => 201, 'veri' => ['id' => $this->pdo->lastInsertId()]];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün oluşturulurken bir hata oluştu.'];
        }
    }

    /**
     * Mevcut bir ürünü günceller.
     * @param int $id
     * @param array $veri
     * @return array
     */
    public function urunGuncelle(int $id, array $veri): array
    {
        // Önce ürünün var olup olmadığını kontrol et
        $mevcutUrun = $this->idIleGetir($id);
        if (!$mevcutUrun['basarili']) {
            return $mevcutUrun; // Ürün bulunamadı hatası döndür
        }

        if (empty($veri)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Güncellenecek veri bulunamadı.'];
        }

        // Sadece gönderilen alanları güncelle
        $urun_adi = $veri['urun_adi'] ?? $mevcutUrun['veri']['urun_adi'];
        $aciklama = $veri['aciklama'] ?? $mevcutUrun['veri']['aciklama'];
        $fiyat = $veri['fiyat'] ?? $mevcutUrun['veri']['fiyat'];
        $resim_url = $veri['resim_url'] ?? $mevcutUrun['veri']['resim_url'];

        try {
            $sql = "UPDATE urunler SET urun_adi = :urun_adi, aciklama = :aciklama, fiyat = :fiyat, resim_url = :resim_url WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':urun_adi' => $urun_adi,
                ':aciklama' => $aciklama,
                ':fiyat' => $fiyat,
                ':resim_url' => $resim_url,
            ]);
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Ürün başarıyla güncellendi.'];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün güncellenirken bir hata oluştu.'];
        }
    }

    /**
     * Bir ürünü siler.
     * @param int $id
     * @return array
     */
    public function urunSil(int $id): array
    {
        try {
            $sql = "DELETE FROM urunler WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() > 0) {
                return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Ürün başarıyla silindi.'];
            } else {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Silinecek ürün bulunamadı.'];
            }
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün silinirken bir hata oluştu.'];
        }
    }
}
