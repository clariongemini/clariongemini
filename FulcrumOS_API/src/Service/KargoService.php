<?php
namespace FulcrumOS\Service;

use PDO;

class KargoService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Tüm aktif kargo seçeneklerini getirir.
     * @return array
     */
    public function tumunuGetir(): array
    {
        try {
            $sql = "SELECT kargo_id, firma_adi, aciklama, ucret FROM kargo_secenekleri";
            $stmt = $this->pdo->query($sql);
            return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Kargo seçenekleri getirilirken bir hata oluştu.'];
        }
    }
}
