<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class EnvanterService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Bir varyantın stoğunu günceller, envanter hareketini kaydeder ve AOM'yi yeniden hesaplar.
     * Bu metodun çağrıldığı yerin bir transaction içinde olması şiddetle tavsiye edilir.
     * @throws Exception
     */
    public function stokGuncelle(int $varyantId, string $hareketTipi, int $degisimMiktari, ?int $referansId, ?float $maliyet, ?int $kullaniciId): void
    {
        // 1. Güncel durumu kilitleyerek al (Race condition önlemi)
        $stmt = $this->pdo->prepare("SELECT stok_adedi, agirlikli_ortalama_maliyet FROM urun_varyantlari WHERE varyant_id = ? FOR UPDATE");
        $stmt->execute([$varyantId]);
        $varyant = $stmt->fetch();

        if (!$varyant) {
            throw new Exception("Envanter güncellenecek ürün varyantı bulunamadı (ID: $varyantId).");
        }

        $oncekiStok = (int)$varyant['stok_adedi'];
        $sonrakiStok = $oncekiStok + $degisimMiktari;
        $yeniAom = (float)$varyant['agirlikli_ortalama_maliyet'];

        // Güvenlik kontrolü: Stok eksiye düşemez
        if ($sonrakiStok < 0) {
            throw new Exception("İşlem sonucunda stok eksiye düşüyor (ID: $varyantId).");
        }

        // 2. Stok girişi varsa AOM'yi (Ağırlıklı Ortalama Maliyet) yeniden hesapla
        if ($degisimMiktari > 0 && $maliyet !== null) {
            $mevcutToplamMaliyet = $oncekiStok * $yeniAom;
            $gelenToplamMaliyet = $degisimMiktari * $maliyet;
            $yeniAom = ($mevcutToplamMaliyet + $gelenToplamMaliyet) / $sonrakiStok;
        }

        // 3. Envanter hareketini (ledger) kaydet
        $sqlLedger = "INSERT INTO envanter_hareketleri (varyant_id, kullanici_id, hareket_tipi, referans_id, degisim_miktari, onceki_stok, sonraki_stok, maliyet) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtLedger = $this->pdo->prepare($sqlLedger);
        $stmtLedger->execute([$varyantId, $kullaniciId, $hareketTipi, $referansId, $degisimMiktari, $oncekiStok, $sonrakiStok, $maliyet]);

        // 4. Varyant tablosunu yeni stok ve AOM ile güncelle
        $sqlVaryant = "UPDATE urun_varyantlari SET stok_adedi = ?, agirlikli_ortalama_maliyet = ? WHERE varyant_id = ?";
        $stmtVaryant = $this->pdo->prepare($sqlVaryant);
        $stmtVaryant->execute([$sonrakiStok, $yeniAom, $varyantId]);
    }

    // (stokDuzelt metodu buraya eklenebilir)
}
