<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class DashboardService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Anahtar Performans Göstergelerini (KPI) özet olarak döndürür.
     * @return array
     */
    public function getKpiOzet(): array
    {
        try {
            // Birden çok sorguyu tek bir transaction içinde veya ayrı ayrı çalıştır
            $bugunku_satis_tutari = $this->pdo->query("SELECT SUM(toplam_tutar) as toplam FROM siparisler WHERE DATE(siparis_tarihi) = CURDATE()")->fetchColumn();
            $bugunku_siparis_adedi = $this->pdo->query("SELECT COUNT(id) FROM siparisler WHERE DATE(siparis_tarihi) = CURDATE()")->fetchColumn();
            $bekleyen_siparisler = $this->pdo->query("SELECT COUNT(id) FROM siparisler WHERE durum = 'Hazırlanıyor'")->fetchColumn();
            $toplam_kullanici_sayisi = $this->pdo->query("SELECT COUNT(id) FROM kullanicilar")->fetchColumn();
            $stoktaki_urun_cesidi = $this->pdo->query("SELECT COUNT(DISTINCT urun_id) FROM urun_varyantlari WHERE stok_adedi > 0")->fetchColumn();

            $veri = [
                'bugunku_satis_tutari' => (float)($bugunku_satis_tutari ?? 0),
                'bugunku_siparis_adedi' => (int)($bugunku_siparis_adedi ?? 0),
                'bekleyen_siparisler' => (int)($bekleyen_siparisler ?? 0),
                'toplam_kullanici_sayisi' => (int)($toplam_kullanici_sayisi ?? 0),
                'stoktaki_urun_cesidi' => (int)($stoktaki_urun_cesidi ?? 0)
            ];

            return ['basarili' => true, 'kod' => 200, 'veri' => $veri];

        } catch (Exception $e) {
            // Gerçek bir uygulamada burada loglama yapılmalıdır.
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'KPI verileri alınırken bir sunucu hatası oluştu.'];
        }
    }

    /**
     * Son 30 günün günlük satış verilerini grafik için hazırlar.
     * @return array
     */
    public function getSatisGrafigi(): array
    {
        try {
            $sql = "
                SELECT
                    DATE(siparis_tarihi) as tarih,
                    SUM(toplam_tutar) as tutar
                FROM siparisler
                WHERE siparis_tarihi >= CURDATE() - INTERVAL 30 DAY
                GROUP BY DATE(siparis_tarihi)
                ORDER BY tarih ASC;
            ";
            $stmt = $this->pdo->query($sql);
            $sonuclar = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['basarili' => true, 'kod' => 200, 'veri' => ['gunluk_veriler' => $sonuclar]];

        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Satış grafiği verileri alınırken bir sunucu hatası oluştu.'];
        }
    }

    /**
     * Tüm zamanların en çok satan 10 ürününü listeler.
     * @return array
     */
    public function getEnCokSatilanUrunler(): array
    {
        try {
            $sql = "
                SELECT
                    u.urun_id,
                    u.urun_adi,
                    SUM(sd.adet) as toplam_satis_adedi
                FROM siparis_detaylari sd
                JOIN urun_varyantlari uv ON sd.varyant_id = uv.varyant_id
                JOIN urunler u ON uv.urun_id = u.urun_id
                GROUP BY u.urun_id, u.urun_adi
                ORDER BY toplam_satis_adedi DESC
                LIMIT 10;
            ";
            $stmt = $this->pdo->query($sql);
            $sonuclar = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['basarili' => true, 'kod' => 200, 'veri' => $sonuclar];

        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'En çok satan ürünler listesi alınırken bir sunucu hatası oluştu.'];
        }
    }

    /**
     * Son 5 sipariş ve son 5 yorumu hızlı bir bakış için listeler.
     * @return array
     */
    public function getSonFaaliyetler(): array
    {
        try {
            $sonSiparislerSql = "
                SELECT s.id, k.ad_soyad, s.toplam_tutar, s.durum, s.siparis_tarihi
                FROM siparisler s
                JOIN kullanicilar k ON s.kullanici_id = k.id
                ORDER BY s.siparis_tarihi DESC
                LIMIT 5;
            ";
            $sonSiparisler = $this->pdo->query($sonSiparislerSql)->fetchAll(PDO::FETCH_ASSOC);

            $sonYorumlarSql = "
                SELECT ud.degerlendirme_id, k.ad_soyad, u.urun_adi, ud.puan, LEFT(ud.yorum, 50) as kisa_yorum, ud.tarih
                FROM urun_degerlendirmeleri ud
                JOIN kullanicilar k ON ud.kullanici_id = k.id
                JOIN urunler u ON ud.urun_id = u.urun_id
                ORDER BY ud.tarih DESC
                LIMIT 5;
            ";
            $sonYorumlar = $this->pdo->query($sonYorumlarSql)->fetchAll(PDO::FETCH_ASSOC);

            $veri = [
                'son_siparisler' => $sonSiparisler,
                'son_yorumlar' => $sonYorumlar
            ];

            return ['basarili' => true, 'kod' => 200, 'veri' => $veri];

        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Son faaliyetler alınırken bir sunucu hatası oluştu.'];
        }
    }
}
