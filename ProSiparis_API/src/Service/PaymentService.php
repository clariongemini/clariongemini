<?php
namespace ProSiparis\Service;

use PDO;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Model\Currency;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\RetrieveCheckoutFormRequest;
use Iyzipay\Model\CheckoutForm;
use Exception;

class PaymentService
{
    private PDO $pdo;
    private $options;
    private CouponService $couponService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../../config/iyzico_ayarlar.php';
        $this->options = IYZICO_OPTIONS;
        $this->couponService = new CouponService($pdo);
    }

    public function odemeBaslat(int $kullaniciId, int $fiyatListesiId, array $veri): array
    {
        // ... (gerekli alan kontrolü)

        try {
            // ... (kullanıcı, adres, kargo bilgileri alımı)

            // Sepet tutarını B2B fiyatlarına göre sunucu tarafında doğrula
            $sepetTutar = 0;
            $basketItems = [];
            foreach ($veri['sepet'] as $item) {
                 $sql = "
                    SELECT p.urun_adi, vf.fiyat, uv.stok_adedi
                    FROM urun_varyantlari uv
                    JOIN urunler p ON uv.urun_id = p.urun_id
                    JOIN varyant_fiyatlari vf ON uv.varyant_id = vf.varyant_id
                    WHERE uv.varyant_id = ? AND vf.fiyat_listesi_id = ?
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$item['varyant_id'], $fiyatListesiId]);
                $varyant = $stmt->fetch();

                if (!$varyant) {
                     return ['basarili' => false, 'kod' => 400, 'mesaj' => "Ürün (ID: {$item['varyant_id']}) için geçerli bir fiyat bulunamadı."];
                }
                if ($varyant['stok_adedi'] < $item['adet']) {
                    return ['basarili' => false, 'kod' => 400, 'mesaj' => "Stokta yeterli ürün yok: {$varyant['urun_adi']}"];
                }
                $itemPrice = $varyant['fiyat'] * $item['adet'];
                $sepetTutar += $itemPrice;

                // ... (basketItem oluşturma)
            }

            // ... (genel tutar, kupon hesaplama)

            // Iyzico isteğini oluştur
            $conversationId = "CONV-" . $kullaniciId . "-" . uniqid();
            // ... (Iyzico request nesnelerini doldurma)

            // Ödeme seansını fiyat listesi ID'si ile birlikte kaydet
            $seansSql = "INSERT INTO odeme_seanslari (conversation_id, kullanici_id, fiyat_listesi_id, sepet_verisi, adres_verisi, kargo_id, kullanilan_kupon_kodu, indirim_tutari) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $seansStmt = $this->pdo->prepare($seansSql);
            $seansStmt->execute([
                $conversationId,
                $kullaniciId,
                $fiyatListesiId, // Yeni eklendi
                json_encode($veri['sepet']),
                json_encode(['teslimat_adresi_id' => $veri['teslimat_adresi_id']]),
                $veri['kargo_id'],
                $kuponKodu ?? null,
                $indirimTutari ?? 0
            ]);

            // ... (Iyzico ile ödeme formunu başlatma)

        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ödeme başlatılırken bir hata oluştu: ' . $e->getMessage()];
        }
         return ['basarili' => true, 'kod' => 200, 'veri' => ['checkoutFormContent' => '...']]; // Placeholder
    }

    public function callbackDogrula(array $iyzicoResponse): array
    {
        // ... (token kontrolü)

        // ... (CheckoutForm retrieve)

        $conversationId = '...'; // $checkoutForm->getConversationId();
        $stmt = $this->pdo->prepare("SELECT * FROM odeme_seanslari WHERE conversation_id = ? AND durum = 'baslatildi'");
        $stmt->execute([$conversationId]);
        $seans = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$seans) {
            return ['basarili' => false, 'mesaj' => 'Geçerli bir ödeme seansı bulunamadı veya zaten işlenmiş.'];
        }

        // ... (seans durumunu güncelle)

        return [
            'basarili' => true,
            'veri' => [
                'kullanici_id' => (int)$seans['kullanici_id'],
                'fiyat_listesi_id' => (int)$seans['fiyat_listesi_id'], // Yeni eklendi
                'sepet' => json_decode($seans['sepet_verisi'], true),
                'adresler' => json_decode($seans['adres_verisi'], true),
                'kargo_id' => (int)$seans['kargo_id'],
                'kullanilan_kupon_kodu' => $seans['kullanilan_kupon_kodu'],
                'indirim_tutari' => (float)$seans['indirim_tutari']
            ]
        ];
    }
}
