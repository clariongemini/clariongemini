<?php
namespace ProSiparis\Core;

// Bu sınıf, php-amqplib/php-amqplib kütüphanesinin davranışlarını simüle eder.
// Gerçek bir RabbitMQ bağlantısı yerine, olayları merkezi bir log dosyasına yazar.

class EventBusService
{
    private const LOG_FILE = __DIR__ . '/../rabbitmq_events.log';
    private const EXCHANGE_NAME = 'prosiparis_events';

    public function __construct()
    {
        // Gerçekte burada RabbitMQ sunucusuna bağlantı kurulurdu.
        // Simülasyon olduğu için bu adımı atlıyoruz.
        // Log dosyasının varlığını ve yazılabilirliğini kontrol edebiliriz.
        if (!file_exists(self::LOG_FILE)) {
            touch(self::LOG_FILE);
        }
    }

    /**
     * Bir olayı RabbitMQ exchange'ine yayınlar (simülasyon).
     *
     * @param string $eventName Yayınlanacak olayın adı (routing_key). Örn: "siparis.basarili"
     * @param array $data Olayla birlikte gönderilecek veri.
     */
    public function publish(string $eventName, array $data): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'exchange' => self::EXCHANGE_NAME,
            'routing_key' => $eventName,
            'payload' => $data
        ];

        $logString = json_encode($logEntry) . PHP_EOL;

        // Olayı log dosyasına ekle.
        // FILE_APPEND bayrağı, dosyanın üzerine yazmak yerine sonuna eklememizi sağlar.
        file_put_contents(self::LOG_FILE, $logString, FILE_APPEND);
    }

    public function __destruct()
    {
        // Gerçekte burada RabbitMQ bağlantısı kapatılırdı.
    }
}
