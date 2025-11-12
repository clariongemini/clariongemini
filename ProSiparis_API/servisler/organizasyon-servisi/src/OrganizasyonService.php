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

    private function yetkiKontrol(string $gerekliYetki): bool
    {
        $yetkiler = $_SERVER['HTTP_X_PERMISSIONS'] ?? '';
        return in_array($gerekliYetki, explode(',', $yetkiler));
    }

    // Public endpoint - Yetki gerektirmez
    public function listeleAktifDepolar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM depolar WHERE aktif = 1 ORDER BY depo_adi ASC");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    // Admin endpoint - Yetki gerektirir
    public function listeleTumDepolar(): array
    {
        if (!$this->yetkiKontrol('depo_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }
        $stmt = $this->pdo->query("SELECT * FROM depolar ORDER BY depo_adi ASC");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function getDepo(int $id): array
    {
        if (!$this->yetkiKontrol('depo_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }
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
        if (!$this->yetkiKontrol('depo_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }
        $sql = "INSERT INTO depolar (depo_adi, depo_kodu, adres, il, ilce) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$veri['depo_adi'], $veri['depo_kodu'], $veri['adres'] ?? null, $veri['il'] ?? null, $veri['ilce'] ?? null]);
        $id = $this->pdo->lastInsertId();
        return ['basarili' => true, 'kod' => 201, 'veri' => ['depo_id' => $id]];
    }

    public function guncelleDepo(int $id, array $veri): array
    {
        if (!$this->yetkiKontrol('depo_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }
        $sql = "UPDATE depolar SET depo_adi = ?, depo_kodu = ?, adres = ?, il = ?, ilce = ? WHERE depo_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$veri['depo_adi'], $veri['depo_kodu'], $veri['adres'] ?? null, $veri['il'] ?? null, $veri['ilce'] ?? null, $id]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Depo güncellendi.'];
    }

    public function silDepo(int $id): array
    {
        if (!$this->yetkiKontrol('depo_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }
        $stmt = $this->pdo->prepare("DELETE FROM depolar WHERE depo_id = ?");
        $stmt->execute([$id]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Depo başarıyla silindi.'];
    }

    // --- v5.2 API Anahtar Kasası Metodları ---

    private const CIPHER_METHOD = 'aes-256-cbc';

    private function encrypt(string $value, string &$iv): string
    {
        $key = getenv('ENCRYPTION_KEY');
        if (empty($key)) throw new \Exception("Şifreleme anahtarı bulunamadı.");
        $iv = openssl_random_pseudo_bytes(16);
        return openssl_encrypt($value, self::CIPHER_METHOD, $key, 0, $iv);
    }

    private function decrypt(string $encryptedValue, string $iv): string
    {
        $key = getenv('ENCRYPTION_KEY');
        if (empty($key)) throw new \Exception("Şifreleme anahtarı bulunamadı.");
        return openssl_decrypt($encryptedValue, self::CIPHER_METHOD, $key, 0, $iv);
    }

    public function listeleAnahtarlar(): array
    {
        $stmt = $this->pdo->query("SELECT anahtar_id, servis_adi, anahtar_adi, aciklama, olusturma_tarihi FROM entegrasyon_anahtarlari ORDER BY servis_adi ASC");
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function olusturAnahtar(array $veri): array
    {
        $encryptedValue = $this->encrypt($veri['anahtar_degeri'], $iv);

        $sql = "INSERT INTO entegrasyon_anahtarlari (servis_adi, anahtar_adi, anahtar_degeri, iv, aciklama) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$veri['servis_adi'], $veri['anahtar_adi'], $encryptedValue, $iv, $veri['aciklama'] ?? null]);

        return ['basarili' => true, 'kod' => 201, 'mesaj' => 'Entegrasyon anahtarı başarıyla oluşturuldu.'];
    }

    public function silAnahtar(int $id): array
    {
        $stmt = $this->pdo->prepare("DELETE FROM entegrasyon_anahtarlari WHERE anahtar_id = ?");
        $stmt->execute([$id]);
        return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Entegrasyon anahtarı silindi.'];
    }

    public function getAnahtar(?string $servis, ?string $anahtar): array
    {
        if (empty($servis) || empty($anahtar)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => '`servis` and `anahtar` parametreleri zorunludur.'];
        }

        $stmt = $this->pdo->prepare("SELECT anahtar_degeri, iv FROM entegrasyon_anahtarlari WHERE servis_adi = ? AND anahtar_adi = ?");
        $stmt->execute([$servis, $anahtar]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Belirtilen anahtar bulunamadı.'];
        }

        $decryptedValue = $this->decrypt($result['anahtar_degeri'], $result['iv']);

        return ['basarili' => true, 'kod' => 200, 'veri' => ['anahtar_degeri' => $decryptedValue]];
    }
}
