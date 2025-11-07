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

    public function odemeBaslat(int $kullaniciId, array $veri): array
    {
        $gerekliAlanlar = ['teslimat_adresi_id', 'fatura_adresi_id', 'kargo_id', 'sepet'];
        foreach ($gerekliAlanlar as $alan) {
            if (empty($veri[$alan])) return ['basarili' => false, 'kod' => 400, 'mesaj' => "$alan alanı zorunludur."];
        }

        try {
            // 1. Gerekli tüm verileri veritabanından güvenli bir şekilde al
            $kullanici = $this->pdo->prepare("SELECT * FROM kullanicilar WHERE id = ?")->execute([$kullaniciId])->fetch();
            $teslimatAdresi = $this->pdo->prepare("SELECT * FROM kullanici_adresleri WHERE adres_id = ? AND kullanici_id = ?")->execute([$veri['teslimat_adresi_id'], $kullaniciId])->fetch();
            $faturaAdresi = $this->pdo->prepare("SELECT * FROM kullanici_adresleri WHERE adres_id = ? AND kullanici_id = ?")->execute([$veri['fatura_adresi_id'], $kullaniciId])->fetch();
            $kargo = $this->pdo->prepare("SELECT * FROM kargo_secenekleri WHERE kargo_id = ?")->execute([$veri['kargo_id']])->fetch();

            if (!$kullanici || !$teslimatAdresi || !$faturaAdresi || !$kargo) {
                return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Kullanıcı, adres veya kargo bilgileri bulunamadı.'];
            }

            // 2. Sepet tutarını ve sepet öğelerini sunucu tarafında hesapla
            $sepetTutar = 0;
            $basketItems = [];
            foreach ($veri['sepet'] as $item) {
                $stmt = $this->pdo->prepare("SELECT p.urun_adi, v.fiyat, v.stok_adedi FROM urun_varyantlari v JOIN urunler p ON v.urun_id = p.urun_id WHERE v.varyant_id = ?");
                $stmt->execute([$item['varyant_id']]);
                $varyant = $stmt->fetch();
                if (!$varyant || $varyant['stok_adedi'] < $item['adet']) {
                    return ['basarili' => false, 'kod' => 400, 'mesaj' => "Stokta yeterli ürün yok veya ürün bulunamadı: {$varyant['urun_adi'] ?? ''}"];
                }
                $itemPrice = $varyant['fiyat'] * $item['adet'];
                $sepetTutar += $itemPrice;

                $basketItem = new BasketItem();
                $basketItem->setId((string)$item['varyant_id']);
                $basketItem->setName($varyant['urun_adi']);
                $basketItem->setCategory1("Kategori"); // Geliştirilebilir
                $basketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
                $basketItem->setPrice($itemPrice);
                $basketItems[] = $basketItem;
            }

            $genelTutar = $sepetTutar + $kargo['ucret'];
            $indirimTutari = 0;
            $kuponKodu = $veri['kupon_kodu'] ?? null;

            // Kupon varsa doğrula ve uygula
            if ($kuponKodu) {
                $kuponSonuc = $this->couponService->kuponDogrula($kuponKodu, $sepetTutar);
                if ($kuponSonuc['gecerli']) {
                    $indirimTutari = $kuponSonuc['indirim_tutari'];
                    $genelTutar = max(0, $genelTutar - $indirimTutari);
                } else {
                    return ['basarili' => false, 'kod' => 400, 'mesaj' => $kuponSonuc['mesaj']];
                }
            }

            // 3. Iyzico isteğini oluştur
            $conversationId = "CONV-" . $kullaniciId . "-" . uniqid();
            $request = new CreateCheckoutFormInitializeRequest();
            $request->setLocale(Locale::TR);
            $request->setConversationId($conversationId);
            $request->setPrice($sepetTutar);
            $request->setPaidPrice($genelTutar);
            $request->setCurrency(Currency::TL);
            $request->setBasketId("BASKET-" . $kullaniciId . "-" . uniqid());
            $request->setPaymentGroup(PaymentGroup::PRODUCT);
            $request->setCallbackUrl("http://localhost/ProSiparis_API/public/api/odeme/callback/iyzico"); // URL'yi dinamik yap
            $request->setEnabledInstallments([2, 3, 6, 9]);

            $buyer = new Buyer();
            $buyer->setId((string)$kullanici['id']);
            $buyer->setName($kullanici['ad_soyad']);
            $buyer->setSurname($kullanici['ad_soyad']); // Iyzico ad/soyad ayrı istiyor
            $buyer->setGsmNumber("+905555555555"); // Veritabanından gelmeli
            $buyer->setEmail($kullanici['eposta']);
            $buyer->setIdentityNumber("11111111111"); // TC Kimlik No
            $buyer->setRegistrationAddress($faturaAdresi['adres_satiri']);
            $buyer->setIp($_SERVER['REMOTE_ADDR']);
            $buyer->setCity($faturaAdresi['il']);
            $buyer->setCountry("Turkey");
            $buyer->setZipCode($faturaAdresi['posta_kodu']);
            $request->setBuyer($buyer);

            $shippingAddress = new Address();
            $shippingAddress->setContactName($teslimatAdresi['ad_soyad']);
            $shippingAddress->setCity($teslimatAdresi['il']);
            $shippingAddress->setCountry("Turkey");
            $shippingAddress->setAddress($teslimatAdresi['adres_satiri']);
            $shippingAddress->setZipCode($teslimatAdresi['posta_kodu']);
            $request->setShippingAddress($shippingAddress);

            $billingAddress = new Address();
            $billingAddress->setContactName($faturaAdresi['ad_soyad']);
            $billingAddress->setCity($faturaAdresi['il']);
            $billingAddress->setCountry("Turkey");
            $billingAddress->setAddress($faturaAdresi['adres_satiri']);
            $billingAddress->setZipCode($faturaAdresi['posta_kodu']);
            $request->setBillingAddress($billingAddress);

            $request->setBasketItems($basketItems);

            // Ödeme formunu başlat
            // 4. Ödeme seansını veritabanına kaydet
            $seansSql = "INSERT INTO odeme_seanslari (conversation_id, kullanici_id, sepet_verisi, adres_verisi, kargo_id, kullanilan_kupon_kodu, indirim_tutari) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $seansStmt = $this->pdo->prepare($seansSql);
            $seansStmt->execute([
                $conversationId,
                $kullaniciId,
                json_encode($veri['sepet']),
                json_encode(['teslimat_adresi_id' => $veri['teslimat_adresi_id'], 'fatura_adresi_id' => $veri['fatura_adresi_id']]),
                $veri['kargo_id'],
                $kuponKodu,
                $indirimTutari
            ]);

            // 5. Iyzico ile ödeme formunu başlat
            $checkoutFormInitialize = CheckoutFormInitialize::create($request, $this->options);

            if ($checkoutFormInitialize->getStatus() == 'success') {
                return ['basarili' => true, 'kod' => 200, 'veri' => ['checkoutFormContent' => $checkoutFormInitialize->getCheckoutFormContent()]];
            } else {
                return ['basarili' => false, 'kod' => 400, 'mesaj' => $checkoutFormInitialize->getErrorMessage()];
            }

        } catch (\Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Ödeme başlatılırken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    public function callbackDogrula(array $iyzicoResponse): array
    {
        if (empty($iyzicoResponse['token'])) {
            return ['basarili' => false, 'mesaj' => 'Geçersiz callback: Token eksik.'];
        }

        $request = new RetrieveCheckoutFormRequest();
        $request->setLocale(Locale::TR);
        $request->setToken($iyzicoResponse['token']);
        $checkoutForm = CheckoutForm::retrieve($request, $this->options);

        if ($checkoutForm->getPaymentStatus() !== 'SUCCESS') {
            return ['basarili' => false, 'mesaj' => 'Iyzico ödemesi başarısız oldu: ' . $checkoutForm->getErrorMessage()];
        }

        $conversationId = $checkoutForm->getConversationId();
        $stmt = $this->pdo->prepare("SELECT * FROM odeme_seanslari WHERE conversation_id = ? AND durum = 'baslatildi'");
        $stmt->execute([$conversationId]);
        $seans = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$seans) {
            return ['basarili' => false, 'mesaj' => 'Geçerli bir ödeme seansı bulunamadı veya zaten işlenmiş.'];
        }

        $this->pdo->prepare("UPDATE odeme_seanslari SET durum = 'tamamlandi' WHERE id = ?")->execute([$seans['id']]);

        return [
            'basarili' => true,
            'veri' => [
                'kullanici_id' => (int)$seans['kullanici_id'],
                'sepet' => json_decode($seans['sepet_verisi'], true),
                'adresler' => json_decode($seans['adres_verisi'], true),
                'kargo_id' => (int)$seans['kargo_id'],
            ]
        ];
    }
}
