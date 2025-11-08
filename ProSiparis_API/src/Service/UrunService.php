<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class UrunService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Tüm ürünleri, belirtilen fiyat listesine göre minimum fiyatlarıyla listeler.
     * @param int $fiyatListesiId
     * @return array
     */
    public function tumunuGetir(int $fiyatListesiId): array
    {
        try {
            $sql = "
                SELECT
                    u.urun_id, u.urun_adi, u.ortalama_puan, u.degerlendirme_sayisi, k.kategori_adi,
                    (SELECT MIN(vf.fiyat)
                     FROM varyant_fiyatlari vf
                     JOIN urun_varyantlari uv ON vf.varyant_id = uv.varyant_id
                     WHERE uv.urun_id = u.urun_id AND vf.fiyat_listesi_id = ?) as min_fiyat
                FROM urunler u
                LEFT JOIN kategoriler k ON u.kategori_id = k.kategori_id
                ORDER BY u.urun_id DESC
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$fiyatListesiId]);
            return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürünler listelenirken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    /**
     * Bir ürünün tüm detaylarını, belirtilen fiyat listesine göre getirir.
     * @param int $id Ürün ID'si
     * @param int $fiyatListesiId
     * @param int|null $kullaniciId
     * @return array
     */
    public function idIleGetir(int $id, int $fiyatListesiId, ?int $kullaniciId = null): array
    {
        try {
            // Ana Ürün Bilgileri
            $sql = "SELECT u.*, k.kategori_adi FROM urunler u LEFT JOIN kategoriler k ON u.kategori_id = k.kategori_id WHERE u.urun_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $urun = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$urun) {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Ürün bulunamadı.'];
            }

            // Varyantlar ve Fiyatlar
            $sql = "
                SELECT uv.varyant_id, uv.varyant_sku, uv.stok_adedi, uv.raf_kodu, vf.fiyat
                FROM urun_varyantlari uv
                JOIN varyant_fiyatlari vf ON uv.varyant_id = vf.varyant_id
                WHERE uv.urun_id = ? AND vf.fiyat_listesi_id = ?
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id, $fiyatListesiId]);
            $varyantlarListesi = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($varyantlarListesi)) {
                 return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Bu ürün için belirtilen fiyat listesinde hiç fiyat bulunamadı.'];
            }

            // (Diğer detaylar: nitelikler, favori durumu vb. aynı kalabilir)

            $kullanicininFavorisiMi = false;
            if ($kullaniciId) {
                // Favori kontrolü...
            }

            $response = [
                'urun_id' => (int)$urun['urun_id'],
                'urun_adi' => $urun['urun_adi'],
                'aciklama' => $urun['aciklama'],
                'ortalama_puan' => (float)$urun['ortalama_puan'],
                'degerlendirme_sayisi' => (int)$urun['degerlendirme_sayisi'],
                'kullanicinin_favorisi_mi' => $kullanicininFavorisiMi,
                'varyantlar' => $varyantlarListesi
            ];

            return ['basarili' => true, 'kod' => 200, 'veri' => $response];

        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün detayları getirilirken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    /**
     * Yeni bir ürün, varyantları ve fiyatları ile birlikte oluşturur.
     * @param array $veri
     * @return array
     */
    public function urunOlustur(array $veri): array
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Ürün oluştur
            $sql = "INSERT INTO urunler (urun_adi, aciklama, kategori_id) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$veri['urun_adi'], $veri['aciklama'] ?? null, $veri['kategori_id']]);
            $urunId = $this->pdo->lastInsertId();

            // 2. Varyantları ve Fiyatları oluştur
            if (!empty($veri['varyantlar']) && is_array($veri['varyantlar'])) {
                foreach ($veri['varyantlar'] as $varyantData) {
                    $sql = "INSERT INTO urun_varyantlari (urun_id, varyant_sku, stok_adedi, raf_kodu) VALUES (?, ?, ?, ?)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$urunId, $varyantData['sku'], $varyantData['stok'], $varyantData['raf_kodu'] ?? null]);
                    $varyantId = $this->pdo->lastInsertId();

                    // Fiyatları ekle
                    if (!empty($varyantData['fiyatlar']) && is_array($varyantData['fiyatlar'])) {
                        foreach ($varyantData['fiyatlar'] as $fiyatData) {
                            $sqlFiyat = "INSERT INTO varyant_fiyatlari (varyant_id, fiyat_listesi_id, fiyat) VALUES (?, ?, ?)";
                            $stmtFiyat = $this->pdo->prepare($sqlFiyat);
                            $stmtFiyat->execute([$varyantId, $fiyatData['liste_id'], $fiyatData['fiyat']]);
                        }
                    }
                }
            }

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 201, 'veri' => ['urun_id' => $urunId]];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün oluşturulurken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    // Diğer metodlar (guncelle, sil, favori işlemleri vb.) benzer şekilde güncellenmelidir.
    // Şimdilik ana odak listeleme ve detay getirme üzerindedir.

     public function urunSil(int $id): array
    {
        try {
            $this->pdo->beginTransaction();
            // İlişkili fiyatları sil
            $stmtFiyat = $this->pdo->prepare("DELETE vf FROM varyant_fiyatlari vf JOIN urun_varyantlari uv ON vf.varyant_id = uv.varyant_id WHERE uv.urun_id = ?");
            $stmtFiyat->execute([$id]);
            // Varyantları sil
            $stmtVaryant = $this->pdo->prepare("DELETE FROM urun_varyantlari WHERE urun_id = ?");
            $stmtVaryant->execute([$id]);
            // Ana ürünü sil
            $stmtUrun = $this->pdo->prepare("DELETE FROM urunler WHERE urun_id = ?");
            $stmtUrun->execute([$id]);

            $this->pdo->commit();

            if ($stmtUrun->rowCount() > 0) {
                return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Ürün ve ilişkili tüm veriler başarıyla silindi.'];
            } else {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Silinecek ürün bulunamadı.'];
            }
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün silinirken bir hata oluştu: ' . $e->getMessage()];
        }
    }
}
