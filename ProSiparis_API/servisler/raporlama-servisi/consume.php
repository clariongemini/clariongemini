<?php
// Raporlama-Servisi - consume.php (RabbitMQ Worker Simülasyonu)

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/src/RaporlamaService.php';

use ProSiparis\Raporlama\RaporlamaService;
use ProSiparis\Core\Database;

echo "Raporlama tuketicisi baslatildi. Olaylar bekleniyor...\n";

$pdo = Database::getConnection('prosiparis_raporlama'); // Raporlama servisinin kendi veritabanı
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

                    // Raporlama servisi şimdilik sadece 'siparis.kargolandi' ile ilgileniyor
                    if ($event['routing_key'] === 'siparis.kargolandi') {
                        $raporlamaService = new RaporlamaService($pdo);
                        $raporlamaService->tekOlayIsle($event['routing_key'], $event['payload']);
                    }
                }
            }
        }
        fclose($file);
        $lastPosition = $currentPosition;
    }

    sleep(5); // Raporlama daha az kritik olduğu için daha uzun aralıkla sorgulanabilir
}
