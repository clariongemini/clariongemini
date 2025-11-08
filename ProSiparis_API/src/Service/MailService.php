<?php
namespace ProSiparis\Service;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class MailService
{
    private Mailer $mailer;

    public function __construct()
    {
        $dsn = sprintf(
            "smtp://%s:%s@%s:%d",
            urlencode(SMTP_USER),
            urlencode(SMTP_PASS),
            SMTP_HOST,
            SMTP_PORT
        );

        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
    }

    /**
     * Sipariş onay e-postası gönderir.
     */
    public function sendOrderConfirmation(string $kullaniciEposta, array $siparisDetaylari): void
    {
        $htmlContent = "<h1>Siparişiniz Alındı!</h1><p>Sipariş Numaranız: {$siparisDetaylari['id']}</p>";
        $this->sendEmail(
            $kullaniciEposta,
            'ProSiparis - Siparişiniz Başarıyla Alındı',
            $htmlContent
        );
    }

    /**
     * Kargoya verildi bildirim e-postası gönderir.
     */
    public function sendShippingConfirmation(string $kullaniciEposta, array $siparisDetaylari): void
    {
        $htmlContent = "<h1>Siparişiniz Kargoya Verildi!</h1>
                        <p>Sipariş Numaranız: {$siparisDetaylari['id']}</p>
                        <p>Kargo Firması: {$siparisDetaylari['kargo_firmasi']}</p>
                        <p>Takip Kodu: {$siparisDetaylari['kargo_takip_kodu']}</p>";
        $this->sendEmail(
            $kullaniciEposta,
            'ProSiparis - Siparişiniz Kargoya Verildi',
            $htmlContent
        );
    }

    /**
     * Terk edilmiş sepet hatırlatma e-postası gönderir.
     */
    public function sendTerkEdilmisSepetEmail(string $kullaniciEposta, string $kullaniciAdi): void
    {
        $htmlContent = "<h1>Merhaba {$kullaniciAdi},</h1>
                        <p>Sepetinizde harika ürünler unuttunuz! Alışverişinize devam etmek için uygulamamızı ziyaret edin.</p>";
        $this->sendEmail(
            $kullaniciEposta,
            '🛒 Sepetinizdeki Ürünler Sizi Bekliyor!',
            $htmlContent
        );
    }

    /**
     * Pasif kullanıcılar için "Seni Özledik" kupon e-postası gönderir.
     */
    public function sendSeniOzledikEmail(string $kullaniciEposta, string $kullaniciAdi, string $kuponKodu): void
    {
        $htmlContent = "<h1>Merhaba {$kullaniciAdi}, sizi özledik!</h1>
                        <p>Alışverişlerinizde kullanabileceğiniz size özel %10 indirim kuponu tanımladık.</p>
                        <p><strong>Kupon Kodunuz: {$kuponKodu}</strong></p>
                        <p>İyi alışverişler dileriz!</p>";
        $this->sendEmail(
            $kullaniciEposta,
            '🎁 Size Özel Bir Hediyemiz Var!',
            $htmlContent
        );
    }

    /**
     * E-posta göndermek için merkezi bir metod.
     */
    private function sendEmail(string $to, string $subject, string $htmlContent): void
    {
        try {
            $email = (new Email())
                ->from(new Address(SMTP_FROM_ADDRESS, SMTP_FROM_NAME))
                ->to($to)
                ->subject($subject)
                ->html($htmlContent);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // E-posta gönderimi başarısız olursa, bunu logla ama süreci durdurma.
            error_log("E-posta gönderim hatası (Alıcı: {$to}): " . $e->getMessage());
        }
    }
}
