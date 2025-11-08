<?php
namespace ProSiparis\Service;

use PDO;

class DashboardService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getKpiOzet(): array
    {
        $bugunkuSatis = $this->pdo->query("
            SELECT
                SUM(sd.adet * sd.birim_fiyat) as ciro,
                SUM(sd.adet * sd.maliyet_fiyati) as maliyet
            FROM siparis_detaylari sd
            JOIN siparisler s ON sd.siparis_id = s.id
            WHERE DATE(s.siparis_tarihi) = CURDATE()
        ")->fetch(PDO::FETCH_ASSOC);

        $ciro = (float)($bugunkuSatis['ciro'] ?? 0);
        $maliyet = (float)($bugunkuSatis['maliyet'] ?? 0);
        $netKar = $ciro - $maliyet;

        $veri = [
            'bugunku_satis_tutari' => $ciro,
            'bugunku_net_kar' => $netKar,
            'kar_marji' => $ciro > 0 ? round(($netKar / $ciro) * 100, 2) : 0,
            // ... (diğer KPI'lar)
        ];

        return ['basarili' => true, 'kod' => 200, 'veri' => $veri];
    }

    public function getSatisGrafigi(): array
    {
        $sql = "
            SELECT
                DATE(s.siparis_tarihi) as tarih,
                SUM(sd.adet * sd.birim_fiyat) as tutar,
                SUM(sd.adet * sd.maliyet_fiyati) as maliyet
            FROM siparis_detaylari sd
            JOIN siparisler s ON sd.siparis_id = s.id
            WHERE s.siparis_tarihi >= CURDATE() - INTERVAL 30 DAY
            GROUP BY DATE(s.siparis_tarihi)
            ORDER BY tarih ASC;
        ";
        $stmt = $this->pdo->query($sql);
        $sonuclar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $gunlukVeriler = array_map(function($gun) {
            $tutar = (float)$gun['tutar'];
            $maliyet = (float)$gun['maliyet'];
            return [
                'tarih' => $gun['tarih'],
                'tutar' => $tutar,
                'kar' => $tutar - $maliyet
            ];
        }, $sonuclar);

        return ['basarili' => true, 'kod' => 200, 'veri' => ['gunluk_veriler' => $gunlukVeriler]];
    }
}
