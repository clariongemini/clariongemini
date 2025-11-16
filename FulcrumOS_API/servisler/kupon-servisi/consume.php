<?php
// Kupon-Servisi - consume.php (RabbitMQ Worker Simülasyonu)

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/src/KuponService.php';

use ProSiparis\Kupon\KuponService;
use ProSiparis\Core\Database;

echo "Kupon tuketicisi baslatildi. Olaylar bekleniyor...\n";

$pdo = Database::getConnection('prosiparis_kupon'); // Kupon servisinin kendi veritabanı
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

                    if ($event['routing_key'] === 'siparis.basarili') {
                        $kuponService = new KuponService($pdo);
                        $kuponService->tekOlayIsle($event['routing_key'], $event['payload']);
                    }
                }
            }
        }
        fclose($file);
        $lastPosition = $currentPosition;
    }

    sleep(5);
}
