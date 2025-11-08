<?php
namespace ProSiparis\Service;

use PDO;

class ReportService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function olusturKarZararRaporu(array $filtreler): array
    {
        $select = "SELECT SUM(sd.adet * sd.birim_fiyat) as ciro, SUM(sd.adet * sd.maliyet_fiyati) as maliyet";
        $from = " FROM siparis_detaylari sd JOIN siparisler s ON sd.siparis_id = s.id";
        $where = ["1=1"];
        $params = [];

        if (!empty($filtreler['baslangic_tarihi'])) {
            $where[] = "s.siparis_tarihi >= ?";
            $params[] = $filtreler['baslangic_tarihi'];
        }
        if (!empty($filtreler['bitis_tarihi'])) {
            $where[] = "s.siparis_tarihi <= ?";
            $params[] = $filtreler['bitis_tarihi'];
        }

        $sql = $select . $from . " WHERE " . implode(" AND ", $where);

        // Gruplama (groupBy) mantığı burada eklenebilir.

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $sonuclar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ... (Sonuçları formatla)

        return ['basarili' => true, 'kod' => 200, 'veri' => $sonuclar];
    }
}
