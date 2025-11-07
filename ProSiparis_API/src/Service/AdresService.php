<?php
namespace ProSiparis\Service;

use PDO;

class AdresService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function kullaniciAdresleriniGetir(int $kullaniciId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM kullanici_adresleri WHERE kullanici_id = ?");
        $stmt->execute([$kullaniciId]);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function olustur(int $kullaniciId, array $veri): array
    {
        // Gerekli alanların doğrulaması
        $gerekliAlanlar = ['adres_basligi', 'ad_soyad', 'telefon', 'adres_satiri', 'il', 'ilce'];
        foreach ($gerekliAlanlar as $alan) {
            if (empty($veri[$alan])) {
                return ['basarili' => false, 'kod' => 400, 'mesaj' => "$alan alanı zorunludur."];
            }
        }

        try {
            $sql = "INSERT INTO kullanici_adresleri (kullanici_id, adres_basligi, ad_soyad, telefon, adres_satiri, il, ilce, posta_kodu) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $kullaniciId, $veri['adres_basligi'], $veri['ad_soyad'], $veri['telefon'],
                $veri['adres_satiri'], $veri['il'], $veri['ilce'], $veri['posta_kodu'] ?? null
            ]);
            return ['basarili' => true, 'kod' => 201, 'veri' => ['adres_id' => $this->pdo->lastInsertId()]];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Adres oluşturulurken bir hata oluştu.'];
        }
    }

    public function guncelle(int $adresId, int $kullaniciId, array $veri): array
    {
        $gerekliAlanlar = ['adres_basligi', 'ad_soyad', 'telefon', 'adres_satiri', 'il', 'ilce'];
        foreach ($gerekliAlanlar as $alan) {
            if (empty($veri[$alan])) {
                return ['basarili' => false, 'kod' => 400, 'mesaj' => "$alan alanı zorunludur."];
            }
        }

        try {
            $sql = "UPDATE kullanici_adresleri SET
                        adres_basligi = ?,
                        ad_soyad = ?,
                        telefon = ?,
                        adres_satiri = ?,
                        il = ?,
                        ilce = ?,
                        posta_kodu = ?
                    WHERE adres_id = ? AND kullanici_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $veri['adres_basligi'], $veri['ad_soyad'], $veri['telefon'],
                $veri['adres_satiri'], $veri['il'], $veri['ilce'], $veri['posta_kodu'] ?? null,
                $adresId, $kullaniciId
            ]);

            if ($stmt->rowCount() > 0) {
                return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Adres başarıyla güncellendi.'];
            }
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Güncellenecek adres bulunamadı veya bu adrese erişim yetkiniz yok.'];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Adres güncellenirken bir hata oluştu.'];
        }
    }

    public function sil(int $adresId, int $kullaniciId): array
    {
        try {
            // Güvenlik: Kullanıcının sadece kendi adresini sildiğinden emin ol
            $sql = "DELETE FROM kullanici_adresleri WHERE adres_id = ? AND kullanici_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$adresId, $kullaniciId]);

            if ($stmt->rowCount() > 0) {
                return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Adres başarıyla silindi.'];
            }
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Silinecek adres bulunamadı veya bu adrese erişim yetkiniz yok.'];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Adres silinirken bir hata oluştu.'];
        }
    }
}
