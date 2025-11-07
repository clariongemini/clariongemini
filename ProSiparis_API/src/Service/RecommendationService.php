<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class RecommendationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Belirtilen kullanıcı için kişiselleştirilmiş ürün önerileri oluşturur.
     * @param int $kullaniciId
     * @return array
     */
    public function getOnerilenUrunler(int $kullaniciId): array
    {
        try {
            // 1. Kullanıcının satın aldığı ve favorilediği ürünlerin kategorilerini topla
            $etkilesimKategorileri = $this->getEtkilesimKategorileri($kullaniciId);

            // 2. Eğer yeterli etkileşim yoksa, genel popüler ürünleri döndür (Fallback)
            if (empty($etkilesimKategorileri)) {
                return $this->getGenelPopulerUrunler();
            }

            // 3. En çok etkileşimde bulunulan kategorileri belirle (örn: ilk 3)
            $frekanslar = array_count_values($etkilesimKategorileri);
            arsort($frekanslar);
            $enIyiKategoriler = array_slice(array_keys($frekanslar), 0, 3);

            // 4. Kullanıcının daha önce satın aldığı tüm ürün varyant ID'lerini al
            $satinAlinanVaryantlar = $this->getSatinAlinanVaryantlar($kullaniciId);

            // 5. En iyi kategorilerdeki, kullanıcının satın almadığı, en yüksek puanlı ürünleri getir
            $placeholders = rtrim(str_repeat('?,', count($enIyiKategoriler)), ',');
            $satinAlinanPlaceholders = !empty($satinAlinanVaryantlar) ? rtrim(str_repeat('?,', count($satinAlinanVaryantlar)), ',') : 'NULL';

            $sql = "
                SELECT
                    u.urun_id, u.urun_adi, u.aciklama, u.resim_url, u.ortalama_puan,
                    (SELECT MIN(fiyat) FROM urun_varyantlari WHERE urun_id = u.urun_id) as min_fiyat
                FROM urunler u
                JOIN urun_varyantlari uv ON u.urun_id = uv.urun_id
                WHERE u.kategori_id IN ($placeholders)
                AND uv.varyant_id NOT IN ($satinAlinanPlaceholders)
                GROUP BY u.urun_id
                ORDER BY u.ortalama_puan DESC
                LIMIT 10;
            ";

            $params = array_merge($enIyiKategoriler, $satinAlinanVaryantlar);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $onerilenUrunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Eğer kişiselleştirilmiş sonuç bulunamazsa yine fallback'e git
            if(empty($onerilenUrunler)){
                return $this->getGenelPopulerUrunler();
            }

            return ['basarili' => true, 'kod' => 200, 'veri' => $onerilenUrunler];

        } catch (Exception $e) {
            // Hata durumunda en azından genel popüler ürünleri göstermeye çalış
            return $this->getGenelPopulerUrunler();
        }
    }

    /**
     * Kullanıcının sipariş ve favorilerinden kategori ID'lerini çeker.
     * @param int $kullaniciId
     * @return array
     */
    private function getEtkilesimKategorileri(int $kullaniciId): array
    {
        // Favorilerden kategoriler
        $sqlFavori = "
            SELECT u.kategori_id FROM kullanici_favorileri kf
            JOIN urunler u ON kf.urun_id = u.urun_id
            WHERE kf.kullanici_id = ? AND u.kategori_id IS NOT NULL;
        ";
        $stmtFavori = $this->pdo->prepare($sqlFavori);
        $stmtFavori->execute([$kullaniciId]);
        $kategoriler = $stmtFavori->fetchAll(PDO::FETCH_COLUMN, 0);

        // Siparişlerden kategoriler
        $sqlSiparis = "
            SELECT u.kategori_id FROM siparis_detaylari sd
            JOIN siparisler s ON sd.siparis_id = s.id
            JOIN urun_varyantlari uv ON sd.varyant_id = uv.varyant_id
            JOIN urunler u ON uv.urun_id = u.urun_id
            WHERE s.kullanici_id = ? AND u.kategori_id IS NOT NULL;
        ";
        $stmtSiparis = $this->pdo->prepare($sqlSiparis);
        $stmtSiparis->execute([$kullaniciId]);
        $kategoriler = array_merge($kategoriler, $stmtSiparis->fetchAll(PDO::FETCH_COLUMN, 0));

        return $kategoriler;
    }

    /**
     * Kullanıcının satın aldığı tüm varyant ID'lerini döndürür.
     * @param int $kullaniciId
     * @return array
     */
    private function getSatinAlinanVaryantlar(int $kullaniciId): array
    {
        $sql = "
            SELECT sd.varyant_id FROM siparis_detaylari sd
            JOIN siparisler s ON sd.siparis_id = s.id
            WHERE s.kullanici_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kullaniciId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }


    /**
     * Yeterli kullanıcı verisi olmadığında çalışacak fallback metodu.
     * @return array
     */
    public function getGenelPopulerUrunler(): array
    {
        $sql = "
            SELECT
                urun_id, urun_adi, aciklama, resim_url, ortalama_puan,
                (SELECT MIN(fiyat) FROM urun_varyantlari WHERE urun_id = u.urun_id) as min_fiyat
            FROM urunler u
            ORDER BY ortalama_puan DESC, degerlendirme_sayisi DESC
            LIMIT 10;
        ";
        $stmt = $this->pdo->query($sql);
        $urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['basarili' => true, 'kod' => 200, 'veri' => $urunler, 'not' => 'Genel popüler ürünler gösteriliyor.'];
    }
}
