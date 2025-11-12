<?php
// Envanter-Servisi - consume.php (RabbitMQ Worker Simülasyonu)

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/src/EnvanterService.php';

use ProSiparis\Envanter\EnvanterService;
use ProSiparis\Core\Database;

echo "Envanter tuketicisi baslatildi. Olaylar bekleniyor...\n";

$pdo = Database::getConnection('prosiparis_envanter'); // Envanter servisinin kendi veritabanı
$logFile = __DIR__ . '/../../rabbitmq_events.log';
$lastPosition = filesize($logFile); // Başlangıçta dosyanın sonunu işaretle

// Sonsuz döngü: Sürekli olarak yeni olayları kontrol et
while (true) {
    clearstatcache(); // Dosya boyutu önbelleğini temizle
    $currentPosition = filesize($logFile);

    if ($currentPosition > $lastPosition) {
        $file = fopen($logFile, 'r');
        fseek($file, $lastPosition); // Sadece yeni eklenen kısımları oku

        while (!feof($file)) {
            $line = fgets($file);
            if ($line) {
                $event = json_decode($line, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "Yeni olay alindi: {$event['routing_key']}\n";

                    // İlgili olayları işle
                    $ilgiliOlaylar = ['siparis.kargolandi', 'tedarik.mal_kabul_yapildi', 'iade.stoga_geri_alindi'];
                    if (in_array($event['routing_key'], $ilgiliOlaylar)) {
                        $envanterService = new EnvanterService($pdo);
                        $envanterService->tekOlayIsle($event['routing_key'], $event['payload']);
                    }
                }
            }
        }
        fclose($file);
        $lastPosition = $currentPosition;
    }

    sleep(2); // Sorgulama (polling) aralığı
}
