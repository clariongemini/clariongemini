<?php
// Bildirim-Servisi - consume.php (RabbitMQ Worker Simülasyonu)

// Varsayım: MailService sınıfı bu dosya içinde veya dahil edilen bir
// yapılandırma dosyasında mevcuttur.
// require_once __DIR__ . '/src/MailService.php';

class MailService { // Simülasyon için geçici sınıf
    public function sendOrderConfirmation($eposta, $veri) { echo "Siparis onayi gonderildi: $eposta\n"; }
    public function sendShippingConfirmation($eposta, $veri) { echo "Kargo onayi gonderildi: $eposta\n"; }
    public function sendRefundConfirmation($eposta, $veri) { echo "Iade onayi gonderildi: $eposta\n"; }
}
$mailService = new MailService();


echo "Bildirim tuketicisi baslatildi. Olaylar bekleniyor...\n";

$logFile = __DIR__ . '/../../rabbitmq_events.log';
$lastPosition = filesize($logFile);

while (true) {
    clearstatcache();
    $currentPosition = filesize($logFile);

    if ($currentPosition > $lastPosition) {
        $file = fopen($logFile, 'r');
        fseek($file, $lastPosition);

        while (!feof($file)) {
            $line = fgets($file);
            if ($line) {
                $event = json_decode($line, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "Yeni olay alindi: {$event['routing_key']}\n";

                    try {
                        $veri = $event['payload'];
                        $eposta = $veri['kullanici_eposta'] ?? 'eposta@bulunamadi.com';

                        switch ($event['routing_key']) {
                            case 'siparis.basarili':
                                $mailService->sendOrderConfirmation($eposta, $veri);
                                break;
                            case 'siparis.kargolandi':
                                $mailService->sendShippingConfirmation($eposta, $veri);
                                break;
                            case 'iade.odeme_basarili':
                                $mailService->sendRefundConfirmation($eposta, $veri);
                                break;
                        }
                    } catch (Exception $e) {
                         error_log("Bildirim olayı işlenirken hata: " . $e->getMessage());
                    }
                }
            }
        }
        fclose($file);
        $lastPosition = $currentPosition;
    }

    sleep(3);
}
