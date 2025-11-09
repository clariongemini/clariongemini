<?php
namespace ProSiparis\Organizasyon;

use PDO;

class OrganizasyonService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listeleDepolar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM depolar WHERE aktif = 1 ORDER BY depo_adi ASC");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function getDepo(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM depolar WHERE depo_id = ?");
        $stmt->execute([$id]);
        $depo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$depo) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Depo bulunamadı.'];
        }
        return ['basarili' => true, 'kod' => 200, 'veri' => $depo];
    }

    public function olusturDepo(array $veri): array
    {
        $sql = "INSERT INTO depolar (depo_adi, depo_kodu, adres) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$veri['depo_adi'], $veri['depo_kodu'], $veri['adres'] ?? null]);
        $id = $this->pdo->lastInsertId();
        return ['basarili' => true, 'kod' => 201, 'veri' => ['depo_id' => $id]];
    }

    public function guncelleDepo(int $id, array $veri): array
    {
        $sql = "UPDATE depolar SET depo_adi = ?, depo_kodu = ?, adres = ?, aktif = ? WHERE depo_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$veri['depo_adi'], $veri['depo_kodu'], $veri['adres'] ?? null, $veri['aktif'] ?? true, $id]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Depo güncellendi.'];
    }

    public function silDepo(int $id): array
    {
        // Gerçek bir uygulamada, bir deponun silinip silinemeyeceğini kontrol eden
        // iş kuralları olmalıdır (örn: içinde stok var mı?).
        // Bu basit örnekte, depoyu pasif hale getiriyoruz.
        $stmt = $this->pdo->prepare("UPDATE depolar SET aktif = 0 WHERE depo_id = ?");
        $stmt->execute([$id]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Depo pasif hale getirildi.'];
    }
}
