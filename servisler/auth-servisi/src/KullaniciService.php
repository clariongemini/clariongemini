<?php
namespace FulcrumOS\Service;

use PDO;

class KullaniciService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Belirtilen ID'ye sahip kullanıcının profil bilgilerini getirir.
     * @param int $kullaniciId
     * @return array
     */
    public function profilGetir(int $kullaniciId): array
    {
        try {
            $sql = "SELECT id, ad_soyad, eposta, rol, tercih_dil, tercih_tema FROM kullanicilar WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $kullaniciId]);
            $kullanici = $stmt->fetch();

            if ($kullanici) {
                return ['basarili' => true, 'kod' => 200, 'veri' => $kullanici];
            } else {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Kullanıcı bulunamadı.'];
            }
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Profil bilgileri getirilirken bir hata oluştu.'];
        }
    }

    /**
     * Kullanıcının profil bilgilerini günceller.
     * @param int $kullaniciId
     * @param array $veri ['ad_soyad', 'tercih_dil', 'tercih_tema']
     * @return array
     */
    public function profilGuncelle(int $kullaniciId, array $veri): array
    {
        // Sadece izin verilen alanların güncellendiğinden emin ol
        $guncellenecekAlanlar = [];
        if (!empty($veri['ad_soyad'])) $guncellenecekAlanlar['ad_soyad'] = $veri['ad_soyad'];
        if (!empty($veri['tercih_dil'])) $guncellenecekAlanlar['tercih_dil'] = $veri['tercih_dil'];
        if (!empty($veri['tercih_tema'])) $guncellenecekAlanlar['tercih_tema'] = $veri['tercih_tema'];

        if (empty($guncellenecekAlanlar)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Güncellenecek en az bir alan belirtilmelidir.'];
        }

        $sqlParcalari = [];
        foreach ($guncellenecekAlanlar as $alan => $deger) {
            $sqlParcalari[] = "$alan = :$alan";
        }
        $sql = "UPDATE kullanicilar SET " . implode(', ', $sqlParcalari) . " WHERE id = :id";

        $guncellenecekAlanlar['id'] = $kullaniciId;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($guncellenecekAlanlar);
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Profil başarıyla güncellendi.'];
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Profil güncellenirken bir hata oluştu.'];
        }
    }
}
