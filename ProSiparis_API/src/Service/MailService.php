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
        // Hassas bilgileri artık config/ayarlar.php dosyasından güvenli bir şekilde oku
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
     * @param string $kullaniciEposta
     * @param array $siparisDetaylari
     */
    public function sendOrderConfirmation(string $kullaniciEposta, array $siparisDetaylari): void
    {
        $htmlContent = "<h1>Siparişiniz Alındı!</h1><p>Sipariş Numaranız: {$siparisDetaylari['id']}</p>";
        // Geliştirme Notu: Burada sipariş detaylarını daha zengin bir HTML şablonuna basmak gerekir.

        $email = (new Email())
            ->from(new Address(SMTP_FROM_ADDRESS, SMTP_FROM_NAME))
            ->to($kullaniciEposta)
            ->subject('ProSiparis - Siparişiniz Başarıyla Alındı')
            ->html($htmlContent);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // E-posta gönderimi başarısız olursa, bunu logla ama süreci durdurma.
            error_log("E-posta gönderim hatası: " . $e->getMessage());
        }
    }

    /**
     * Kargoya verildi bildirim e-postası gönderir.
     * @param string $kullaniciEposta
     * @param array $siparisDetaylari
     */
    public function sendShippingConfirmation(string $kullaniciEposta, array $siparisDetaylari): void
    {
        $htmlContent = "<h1>Siparişiniz Kargoya Verildi!</h1>
                        <p>Sipariş Numaranız: {$siparisDetaylari['id']}</p>
                        <p>Kargo Firması: {$siparisDetaylari['kargo_firmasi']}</p>
                        <p>Takip Kodu: {$siparisDetaylari['kargo_takip_kodu']}</p>";

        $email = (new Email())
            ->from(new Address(SMTP_FROM_ADDRESS, SMTP_FROM_NAME))
            ->to($kullaniciEposta)
            ->subject('ProSiparis - Siparişiniz Kargoya Verildi')
            ->html($htmlContent);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log("E-posta gönderim hatası: " . $e->getMessage());
        }
    }
}
