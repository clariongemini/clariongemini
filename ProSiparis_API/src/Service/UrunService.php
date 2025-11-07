<?php
namespace ProSiparis\Service;

use PDO;

class UrunService
{
    private PDO $pdo;
    private FileUploadService $fileUploadService;

    public function __construct(PDO $pdo, ?FileUploadService $fileUploadService = null)
    {
        $this->pdo = $pdo;
        $this->fileUploadService = $fileUploadService ?: new FileUploadService();
    }

    public function tumunuGetir(): array
    {
        try {
            // Ürün listesine puan ve değerlendirme sayısını ekle
            $sql = "SELECT u.urun_id, u.urun_adi, u.resim_url, u.ortalama_puan, u.degerlendirme_sayisi, k.kategori_adi
                    FROM urunler u
                    LEFT JOIN kategoriler k ON u.kategori_id = k.kategori_id
                    ORDER BY u.urun_id DESC";
            $stmt = $this->pdo->query($sql);
            return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (\Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürünler listelenirken bir hata oluştu.'];
        }
    }

    /**
     * Bir ürünün tüm detaylarını (kategori, nitelikler, varyantlar, puan, favori durumu) getirir.
     * @param int $id Ürün ID'si
     * @param int|null $kullaniciId Mevcut kullanıcı ID'si (favori durumunu kontrol etmek için)
     * @return array
     */
    public function idIleGetir(int $id, ?int $kullaniciId = null): array
    {
        try {
            // 1. Ana Ürün Bilgileri, Kategori ve Puan
            $sql = "SELECT u.urun_id, u.urun_adi, u.aciklama, u.resim_url as ana_resim, u.ortalama_puan, u.degerlendirme_sayisi,
                           k.kategori_id, k.kategori_adi
                    FROM urunler u
                    LEFT JOIN kategoriler k ON u.kategori_id = k.kategori_id
                    WHERE u.urun_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $urun = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$urun) {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Ürün bulunamadı.'];
            }

            // 2. Ürüne Ait Tüm Varyantlar
            $sql = "SELECT varyant_id, varyant_sku, fiyat, stok_adedi, resim_url FROM urun_varyantlari WHERE urun_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $varyantlar = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

            // 3. Varyantların Niteliklerini Topla
            $sql = "SELECT vdi.varyant_id, un.nitelik_adi, und.deger_adi
                    FROM varyant_deger_iliskisi vdi
                    JOIN urun_nitelik_degerleri und ON vdi.deger_id = und.deger_id
                    JOIN urun_nitelikleri un ON und.nitelik_id = un.nitelik_id
                    WHERE vdi.varyant_id IN (" . implode(',', array_keys($varyantlar)) . ")";
            $stmt = $this->pdo->query($sql);
            $nitelikler = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

            // 4. Veriyi İstenen Formatta Birleştir
            $sonucVaryantlar = [];
            foreach ($varyantlar as $varyantId => $varyantData) {
                $varyantData = $varyantData[0]; // FETCH_GROUP'tan dolayı
                $varyantData['secili_nitelikler'] = $nitelikler[$varyantId] ?? [];
                $sonucVaryantlar[] = $varyantData;
            }

            // 5. Ürünün Sahip Olduğu Tüm Olası Nitelikleri ve Değerlerini Bul
            $olasiNitelikler = $this->urunOlasıNitelikleriniGetir($id);

            // 6. Favori Durumunu Kontrol Et
            $kullanicininFavorisiMi = false;
            if ($kullaniciId) {
                $favSql = "SELECT COUNT(*) FROM kullanici_favorileri WHERE kullanici_id = ? AND urun_id = ?";
                $favStmt = $this->pdo->prepare($favSql);
                $favStmt->execute([$kullaniciId, $id]);
                if ($favStmt->fetchColumn() > 0) {
                    $kullanicininFavorisiMi = true;
                }
            }

            $response = [
                'urun_id' => (int)$urun['urun_id'],
                'urun_adi' => $urun['urun_adi'],
                'aciklama' => $urun['aciklama'],
                'ortalama_puan' => (float)$urun['ortalama_puan'],
                'degerlendirme_sayisi' => (int)$urun['degerlendirme_sayisi'],
                'kullanicinin_favorisi_mi' => $kullanicininFavorisiMi,
                'kategori' => [
                    'kategori_id' => (int)$urun['kategori_id'],
                    'kategori_adi' => $urun['kategori_adi']
                ],
                'resimler' => [$urun['ana_resim']],
                'nitelikler' => $olasiNitelikler,
                'varyantlar' => $sonucVaryantlar
            ];

            return ['basarili' => true, 'kod' => 200, 'veri' => $response];

        } catch (\Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün detayları getirilirken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    private function urunOlasıNitelikleriniGetir(int $urunId): array
    {
        $sql = "SELECT DISTINCT un.nitelik_adi, und.deger_adi
                FROM urun_varyantlari uv
                JOIN varyant_deger_iliskisi vdi ON uv.varyant_id = vdi.varyant_id
                JOIN urun_nitelik_degerleri und ON vdi.deger_id = und.deger_id
                JOIN urun_nitelikleri un ON und.nitelik_id = un.nitelik_id
                WHERE uv.urun_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$urunId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $nitelikler = [];
        foreach ($rows as $row) {
            $nitelikler[$row['nitelik_adi']][] = $row['deger_adi'];
        }

        $sonuc = [];
        foreach ($nitelikler as $ad => $degerler) {
            $sonuc[] = ['nitelik_adi' => $ad, 'degerler' => array_unique($degerler)];
        }
        return $sonuc;
    }

    public function urunOlustur(array $veri, array $dosyalar): array
    {
        if (empty($veri['urun_adi']) || empty($veri['kategori_id'])) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Ürün adı ve kategori zorunludur.'];
        }

        $anaResimYol = null;
        if (!empty($dosyalar['ana_resim'])) {
            $sonuc = $this->fileUploadService->handle($dosyalar['ana_resim']);
            if (!$sonuc['basarili']) {
                return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Ana resim yüklenemedi: ' . $sonuc['mesaj']];
            }
            $anaResimYol = $sonuc['yol'];
        }

        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO urunler (urun_adi, aciklama, kategori_id, resim_url, stok_kodu) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$veri['urun_adi'], $veri['aciklama'] ?? null, $veri['kategori_id'], $anaResimYol, $veri['stok_kodu'] ?? null]);
            $urunId = $this->pdo->lastInsertId();

            if (!empty($veri['varyantlar']) && is_array($veri['varyantlar'])) {
                foreach ($veri['varyantlar'] as $varyantData) {
                    $sql = "INSERT INTO urun_varyantlari (urun_id, varyant_sku, fiyat, stok_adedi) VALUES (?, ?, ?, ?)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$urunId, $varyantData['sku'], $varyantData['fiyat'], $varyantData['stok']]);
                    $varyantId = $this->pdo->lastInsertId();

                    foreach ($varyantData['nitelikler'] as $nitelik) {
                        $nitelikId = $this->nitelikIdGetir($nitelik['nitelik_adi']);
                        $degerId = $this->nitelikDegerIdGetir($nitelikId, $nitelik['deger_adi']);
                        $sql = "INSERT INTO varyant_deger_iliskisi (varyant_id, deger_id) VALUES (?, ?)";
                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute([$varyantId, $degerId]);
                    }
                }
            }

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 201, 'veri' => ['urun_id' => $urunId]];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün oluşturulurken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    public function urunGuncelle(int $id, array $veri): array
    {
        // Not: Bu basit bir güncellemedir. Gerçek bir senaryoda, varyantların
        // tek tek güncellenmesi, silinmesi veya eklenmesi için daha karmaşık bir mantık gerekir.
        if (empty($veri)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Güncellenecek veri bulunamadı.'];
        }

        try {
            $sql = "UPDATE urunler SET urun_adi = ?, aciklama = ?, kategori_id = ? WHERE urun_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $veri['urun_adi'] ?? null,
                $veri['aciklama'] ?? null,
                $veri['kategori_id'] ?? null,
                $id
            ]);
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Ürün başarıyla güncellendi.'];
        } catch (\Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün güncellenirken bir hata oluştu.'];
        }
    }

    public function urunSil(int $id): array
    {
        try {
            // CASCADE sayesinde, bu ürüne bağlı tüm varyantlar ve ilişki kayıtları da silinecektir.
            $sql = "DELETE FROM urunler WHERE urun_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Ürün başarıyla silindi.'];
            } else {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Silinecek ürün bulunamadı.'];
            }
        } catch (\PDOException $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ürün silinirken bir hata oluştu.'];
        }
    }

    /**
     * Belirli bir kategoriye ait ürünleri getirir.
     * @param int $kategoriId
     * @return array
     */
    public function kategoriyeGoreGetir(int $kategoriId): array
    {
        try {
            $sql = "SELECT urun_id, urun_adi, aciklama, resim_url FROM urunler WHERE kategori_id = ? ORDER BY urun_adi ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kategoriId]);
            $urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['basarili' => true, 'kod' => 200, 'veri' => $urunler];
        } catch (\Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Kategoriye göre ürünler getirilirken bir hata oluştu.'];
        }
    }

    public function favoriyeEkle(int $kullaniciId, int $urunId): array
    {
        try {
            $sql = "INSERT INTO kullanici_favorileri (kullanici_id, urun_id) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kullaniciId, $urunId]);
            return ['basarili' => true, 'kod' => 201, 'mesaj' => 'Ürün favorilere eklendi.'];
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) { // Unique constraint
                return ['basarili' => false, 'kod' => 409, 'mesaj' => 'Bu ürün zaten favorilerinizde.'];
            }
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Favorilere eklenirken bir hata oluştu.'];
        }
    }

    public function favoridenCikar(int $kullaniciId, int $urunId): array
    {
        $sql = "DELETE FROM kullanici_favorileri WHERE kullanici_id = ? AND urun_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kullaniciId, $urunId]);

        if ($stmt->rowCount() > 0) {
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Ürün favorilerden kaldırıldı.'];
        }
        return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Kaldırılacak ürün favori listenizde bulunamadı.'];
    }

    public function favorileriListele(int $kullaniciId): array
    {
        $sql = "SELECT p.* FROM urunler p JOIN kullanici_favorileri f ON p.urun_id = f.urun_id WHERE f.kullanici_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kullaniciId]);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
}
