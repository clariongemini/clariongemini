<?php
namespace ProSiparis\Service;

use PDO;
use Exception;

class DepoService
{
    private PDO $pdo;
    private MailService $mailService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->mailService = new MailService();
    }

    /**
     * Durumu 'Odendi' veya 'Hazirlaniyor' olan siparişleri listeler.
     * @return array
     */
    public function hazirlanacakSiparisleriListele(): array
    {
        $sql = "
            SELECT s.id, s.siparis_tarihi, k.ad_soyad, s.durum
            FROM siparisler s
            JOIN kullanicilar k ON s.kullanici_id = k.id
            WHERE s.durum IN ('Odendi', 'Hazirlaniyor')
            ORDER BY s.siparis_tarihi ASC
        ";
        $stmt = $this->pdo->query($sql);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Belirli bir sipariş için ürün toplama listesi oluşturur.
     * @param int $siparisId
     * @return array
     */
    public function toplamaListesiGetir(int $siparisId): array
    {
        $sql = "
            SELECT
                sd.adet,
                CONCAT(p.urun_adi, ' - ', GROUP_CONCAT(und.deger_adi SEPARATOR ' ')) as urun_adi,
                uv.raf_kodu
            FROM siparis_detaylari sd
            JOIN urun_varyantlari uv ON sd.varyant_id = uv.varyant_id
            JOIN urunler p ON uv.urun_id = p.urun_id
            LEFT JOIN varyant_deger_iliskisi vdi ON uv.varyant_id = vdi.varyant_id
            LEFT JOIN urun_nitelik_degerleri und ON vdi.deger_id = und.deger_id
            WHERE sd.siparis_id = ?
            GROUP BY sd.id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$siparisId]);
        $urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($urunler)) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Bu siparişe ait ürün bulunamadı.'];
        }

        // Siparişin genel bilgilerini de ekleyelim
        $siparisSql = "SELECT s.id as siparis_id, k.ad_soyad as musteri_adi FROM siparisler s JOIN kullanicilar k ON s.kullanici_id = k.id WHERE s.id = ?";
        $siparisStmt = $this->pdo->prepare($siparisSql);
        $siparisStmt->execute([$siparisId]);
        $siparisInfo = $siparisStmt->fetch(PDO::FETCH_ASSOC);

        $response = array_merge($siparisInfo, ['urunler' => $urunler]);
        return ['basarili' => true, 'kod' => 200, 'veri' => $response];
    }

    /**
     * Bir siparişi kargoya verilmiş olarak işaretler ve müşteriye e-posta gönderir.
     * @param int $siparisId
     * @param string $kargoFirmasi
     * @param string $kargoTakipKodu
     * @return array
     */
    public function kargoyaVer(int $siparisId, string $kargoFirmasi, string $kargoTakipKodu): array
    {
        if (empty($kargoFirmasi) || empty($kargoTakipKodu)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Kargo firması ve takip kodu zorunludur.'];
        }

        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE siparisler SET durum = 'Kargoya Verildi', kargo_firmasi = ?, kargo_takip_kodu = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kargoFirmasi, $kargoTakipKodu, $siparisId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Güncellenecek sipariş bulunamadı veya durum zaten güncel.');
            }

            // Müşteriye e-posta gönder
            $siparis = $this->pdo->query("SELECT s.*, k.eposta FROM siparisler s JOIN kullanicilar k ON s.kullanici_id = k.id WHERE s.id = $siparisId")->fetch(PDO::FETCH_ASSOC);
            if ($siparis) {
                $this->mailService->sendShippingConfirmation($siparis['eposta'], $siparis);
            }

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Sipariş kargoya verildi olarak güncellendi ve müşteriye bildirim gönderildi.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Sipariş güncellenirken bir hata oluştu: ' . $e->getMessage()];
        }
    }
}
