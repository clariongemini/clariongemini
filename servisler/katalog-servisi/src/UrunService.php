<?php
namespace ProSiparis\Service; // Bu namespace, dosyanın yeni yerini yansıtacak şekilde güncellenmeli

use PDO;
use Exception;

class UrunService // KatalogService olarak düşünülebilir
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ... (mevcut tumunuGetir, idIleGetir gibi ürün listeleme metodları)

    /**
     * Event Bus'taki (olay_gunlugu) stok güncelleme olaylarını işler.
     * Bu metod, bir cron job veya worker tarafından periyodik olarak çağrılmalıdır.
     */
    public function olaylariIsle(): array
    {
        // Gerçek bir senaryoda, son işlenen olayın ID'si bir yerde tutulur.
        // Şimdilik basitçe son 1 saatteki olayları alıyoruz.
        $stmt = $this->pdo->prepare("SELECT olay_id, veri FROM olay_gunlugu WHERE olay_tipi = 'stok_guncellendi' AND olusturma_tarihi > NOW() - INTERVAL 1 HOUR ORDER BY olay_id ASC");
        $stmt->execute();
        $olaylar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $islenen_olay_sayisi = 0;
        foreach ($olaylar as $olay) {
            $veri = json_decode($olay['veri'], true);
            if (!isset($veri['varyant_id']) || !isset($veri['yeni_stok'])) {
                continue;
            }

            // Katalog servisinin kendi veritabanındaki stok adedini güncelle
            $sqlUpdate = "UPDATE urun_varyantlari SET stok_adedi = ? WHERE varyant_id = ?";
            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([$veri['yeni_stok'], $veri['varyant_id']]);
            $islenen_olay_sayisi++;

            // Gerçek bir sistemde, işlenen olaylar silinir veya 'isaretlenir'.
        }
        return ['islenen_olay_sayisi' => $islenen_olay_sayisi];
    }
}
