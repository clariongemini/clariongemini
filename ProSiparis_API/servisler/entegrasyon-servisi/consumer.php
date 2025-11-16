<?php
// FulcrumOS v10.3: Entegrasyon Servisi - Asenkron Olay Tüketicisi (Worker)
// RabbitMQ üzerinden gelen finansal olayları dinler ve işler.

require_once __DIR__ . '/../../vendor/autoload.php'; // Composer autoload
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/src/OlayIsleyici.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

// --- Worker Başlatma ---
echo "Entegrasyon Servisi Worker başlatılıyor...\n";

try {
    // 1. Veritabanı Bağlantısını Kur
    $pdo = connect_db();
    $olayIsleyici = new OlayIsleyici($pdo);
    echo "Veritabanı bağlantısı başarılı.\n";

    // 2. RabbitMQ Bağlantısını Kur
    // Varsayım: RabbitMQ servis adı docker-compose içinde 'rabbitmq'
    // Gerçek bir senaryoda bu bilgiler .env dosyasından okunur.
    $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
    $channel = $connection->channel();

    // 3. Kuyruğu Tanımla (Publisher ile aynı olmalı)
    $kuyruk_adi = 'finansal_olaylar';
    $channel->queue_declare($kuyruk_adi, false, true, false, false);

    echo "RabbitMQ bağlantısı başarılı. '$kuyruk_adi' kuyruğu dinleniyor...\n";
    echo " [*] Mesaj bekleniyor. Çıkmak için CTRL+C\n";

    // 4. Mesajları İşleyecek Callback Fonksiyonunu Tanımla
    $callback = function ($msg) use ($olayIsleyici) {
        echo " [x] Yeni bir olay alındı: " . $msg->body . "\n";

        $olayVerisi = json_decode($msg->body, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($olayVerisi['olay_adi'])) {
            try {
                // Olayı işle
                $olayIsleyici->isle($olayVerisi);

                // Mesajın başarıyla işlendiğini RabbitMQ'ya bildir
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                echo " [✔] Olay başarıyla işlendi ve loglandı.\n";

            } catch (Exception $e) {
                // İşleme sırasında bir hata oluşursa
                echo " [!] Olay işlenirken hata oluştu: " . $e->getMessage() . "\n";
                // Mesajı yeniden denemek üzere kuyruğa geri gönderme (isteğe bağlı, nack)
                $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
            }
        } else {
            // Geçersiz JSON veya eksik olay adı
            echo " [!] Geçersiz mesaj formatı, mesaj reddedildi.\n";
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
        }
    };

    // 5. Kuyruğu Dinlemeye Başla
    $channel->basic_qos(null, 1, null); // Her seferinde bir mesaj al
    $channel->basic_consume($kuyruk_adi, '', false, false, false, false, $callback);

    // 6. Worker'ı Çalışır Durumda Tut
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    // 7. Bağlantıları Kapat
    $channel->close();
    $connection->close();

} catch (Exception $e) {
    die("Worker başlatılırken kritik bir hata oluştu: " . $e->getMessage() . "\n");
}
