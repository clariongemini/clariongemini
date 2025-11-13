<?php
namespace FulcrumOS\Service;

use PDO;
use Exception;

class AutomationService
{
    private PDO $pdo;
    private MailService $mailService;
    private CouponService $couponService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Bu servislerin de başlatılması gerekir. Gerçek bir DI container burada yardımcı olurdu.
        $this->mailService = new MailService();
        $this->couponService = new CouponService($pdo);
    }

    /**
     * Tüm otomasyon görevlerini sırayla çalıştırır.
     * @return array
     */
    public function runAllTasks(): array
    {
        $sonuclar = [];
        try {
            $sonuclar['terk_edilmis_sepet'] = $this->runTerkEdilmisSepetHatirlaticisi();
            $sonuclar['pasif_kullanici'] = $this->runPasifKullaniciHatirlaticisi();
            return ['basarili' => true, 'kod' => 200, 'veri' => $sonuclar];
        } catch (Exception $e) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Otomasyon görevleri çalıştırılırken bir hata oluştu: ' . $e->getMessage()];
        }
    }

    /**
     * Terk edilmiş sepetleri bulur ve kullanıcılara hatırlatma e-postası gönderir.
     */
    public function runTerkEdilmisSepetHatirlaticisi(): array
    {
        // Son 24-25 saat içinde güncellenmiş ve siparişe dönüşmemiş sepetleri bul
        $sql = "
            SELECT s.kullanici_id, k.eposta, k.ad_soyad
            FROM sepetler s
            JOIN kullanicilar k ON s.kullanici_id = k.id
            WHERE s.guncellenme_tarihi BETWEEN NOW() - INTERVAL 25 HOUR AND NOW() - INTERVAL 24 HOUR
            AND NOT EXISTS (
                SELECT 1 FROM siparisler sp WHERE sp.kullanici_id = s.kullanici_id AND sp.siparis_tarihi > s.guncellenme_tarihi
            )
        ";
        $stmt = $this->pdo->query($sql);
        $kullanicilar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $gonderilen_eposta_sayisi = 0;
        foreach ($kullanicilar as $kullanici) {
            // Bu kullanıcı için tekrar e-posta gönderilip gönderilmediğini kontrol et (loglama sistemi olmalı)
            // Şimdilik basitçe gönderiyoruz.
            $this->mailService->sendTerkEdilmisSepetEmail($kullanici['eposta'], $kullanici['ad_soyad']);
            $gonderilen_eposta_sayisi++;
        }

        return ['islem_tamamlandi' => true, 'bulunan_sepet_sayisi' => count($kullanicilar), 'gonderilen_eposta_sayisi' => $gonderilen_eposta_sayisi];
    }

    /**
     * Uzun süredir sipariş vermemiş kullanıcılara özel kupon gönderir.
     */
    public function runPasifKullaniciHatirlaticisi(): array
    {
        // Son 60 günde sipariş vermemiş ama daha önce vermiş kullanıcıları bul
        $sql = "
            SELECT k.id as kullanici_id, k.eposta, k.ad_soyad, MAX(s.siparis_tarihi) as son_siparis_tarihi
            FROM kullanicilar k
            JOIN siparisler s ON k.id = s.kullanici_id
            GROUP BY k.id
            HAVING son_siparis_tarihi < NOW() - INTERVAL 60 DAY
        ";
        $stmt = $this->pdo->query($sql);
        $kullanicilar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $olusturulan_kupon_sayisi = 0;
        foreach ($kullanicilar as $kullanici) {
            // Kupon oluştur
            $kuponKodu = 'SENI-OZLEDIK-' . strtoupper(bin2hex(random_bytes(4)));
            $kuponData = [
                'kullanici_id' => $kullanici['kullanici_id'],
                'kupon_kodu' => $kuponKodu,
                'indirim_tipi' => 'yuzde',
                'indirim_degeri' => 10,
                'son_kullanma_tarihi' => date('Y-m-d H:i:s', strtotime('+7 days')),
                'kullanim_limiti' => 1,
                'aktif_mi' => true
            ];
            $this->couponService->olustur($kuponData);

            // E-posta gönder
            $this->mailService->sendSeniOzledikEmail($kullanici['eposta'], $kullanici['ad_soyad'], $kuponKodu);
            $olusturulan_kupon_sayisi++;
        }

        return ['islem_tamamlandi' => true, 'bulunan_kullanici_sayisi' => count($kullanicilar), 'olusturulan_kupon_sayisi' => $olusturulan_kupon_sayisi];
    }
}
