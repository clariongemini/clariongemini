<?php
// AI-Asistan-Servisi - consume.php (RabbitMQ Worker Simülasyonu)

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/src/AiAsistanService.php';

use ProSiparis\Core\Database;
use ProSiparis\AiAsistan\AiAsistanService;

echo "AI Asistan tuketicisi baslatildi. Katalog olaylari bekleniyor...\n";

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
                    $eventName = $event['routing_key'];

                    if ($eventName === 'katalog.varyant.yaratildi' || $eventName === 'katalog.varyant.guncellendi') {
                        echo "Ilgili olay alindi: $eventName\n";

                        $pdo = Database::getConnection('prosiparis_ai_asistan');
                        $service = new AiAsistanService($pdo);
                        $service->vektoruGuncelle($event['payload']);
                    }
                }
            }
        }
        fclose($file);
        $lastPosition = $currentPosition;
    }

    sleep(3);
}
