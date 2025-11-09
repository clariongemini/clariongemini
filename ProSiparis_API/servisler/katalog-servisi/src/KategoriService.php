<?php
namespace ProSiparis\Service;

use PDO;

class KategoriService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Tüm kategorileri getirir.
     * @return array
     */
    public function tumunuGetir(): array
    {
        try {
            $sql = "SELECT kategori_id, kategori_adi, ust_kategori_id FROM kategoriler ORDER BY kategori_adi ASC";
            $stmt = $this->pdo->query($sql);
            return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll()];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Kategoriler getirilirken bir hata oluştu.'];
        }
    }

    /**
     * Yeni bir kategori oluşturur.
     * @param array $veri
     * @return array
     */
    public function olustur(array $veri): array
    {
        if (empty($veri['kategori_adi'])) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Kategori adı zorunludur.'];
        }

        try {
            $sql = "INSERT INTO kategoriler (kategori_adi, ust_kategori_id) VALUES (:kategori_adi, :ust_kategori_id)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':kategori_adi' => $veri['kategori_adi'],
                ':ust_kategori_id' => $veri['ust_kategori_id'] ?? null
            ]);
            return ['basarili' => true, 'kod' => 201, 'veri' => ['kategori_id' => $this->pdo->lastInsertId()]];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Kategori oluşturulurken bir hata oluştu.'];
        }
    }

    /**
     * Bir kategoriyi günceller.
     * @param int $id
     * @param array $veri
     * @return array
     */
    public function guncelle(int $id, array $veri): array
    {
        if (empty($veri['kategori_adi'])) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Kategori adı zorunludur.'];
        }

        try {
            $sql = "UPDATE kategoriler SET kategori_adi = :kategori_adi, ust_kategori_id = :ust_kategori_id WHERE kategori_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':kategori_adi' => $veri['kategori_adi'],
                ':ust_kategori_id' => $veri['ust_kategori_id'] ?? null
            ]);
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Kategori başarıyla güncellendi.'];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Kategori güncellenirken bir hata oluştu.'];
        }
    }

    /**
     * Bir kategoriyi siler.
     * @param int $id
     * @return array
     */
    public function sil(int $id): array
    {
        try {
            $sql = "DELETE FROM kategoriler WHERE kategori_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() > 0) {
                return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Kategori başarıyla silindi.'];
            }
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Silinecek kategori bulunamadı.'];
        } catch (\PDOException $e) {
            // Foreign key constraint hatası olabilir
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Kategori silinirken bir hata oluştu. Bu kategoriye bağlı ürünler olabilir.'];
        }
    }
}
