<?php
namespace ProSiparis\Controllers;

use ProSiparis\Service\AutomationService;
use ProSiparis\Core\Request;
use PDO;

class CronController
{
    private AutomationService $automationService;

    public function __construct()
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException("Veritabanı bağlantısı kurulamadı.");
        }
        $this->automationService = new AutomationService($pdo);
    }

    /**
     * POST /api/cron/run endpoint'ini yönetir.
     * Güvenli bir anahtar ile korunur ve otomasyon görevlerini tetikler.
     */
    public function run(Request $request)
    {
        // 1. Güvenlik Kontrolü
        $authHeader = $request->getHeader('Authorization');
        if (!$authHeader) {
            $this->sendForbidden('Yetkilendirme başlığı eksik.');
            return;
        }

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->sendForbidden('Geçersiz yetkilendirme formatı.');
            return;
        }

        $submittedKey = $matches[1];
        if ($submittedKey !== CRON_SECRET_KEY) {
            $this->sendForbidden('Geçersiz güvenlik anahtarı.');
            return;
        }

        // 2. Otomasyon Görevlerini Çalıştır
        echo "Cron başlatıldı...\n";
        $sonuc = $this->automationService->runAllTasks();

        // Sonucu JSON olarak veya bir log dosyasına yazdırabilirsiniz.
        // Bu, cron job'un çıktısını izlemek için kullanışlıdır.
        header('Content-Type: application/json');
        http_response_code($sonuc['kod']);
        echo json_encode($sonuc);
        echo "\nCron tamamlandı.\n";
    }

    private function sendForbidden(string $message): void
    {
        http_response_code(403);
        echo json_encode(['durum' => 'hata', 'mesaj' => $message]);
    }
}
