<?php
// Bildirim-Servisi - worker.php - v3.1

// ... (gerekli dosyaları yükle)

$pdo = new PDO(/*...*/);
$mailService = new MailService();

echo "Bildirim worker'ı başlatıldı...\n";

$olayTipleri = ['siparis.basarili', 'siparis.kargolandi', 'iade.odeme_basarili'];
$placeholders = rtrim(str_repeat('?,', count($olayTipleri)), ',');

$sql = "SELECT olay_id, olay_tipi, veri FROM olay_gunlugu WHERE olay_tipi IN ($placeholders) AND islendi = 0 ORDER BY olay_id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($olayTipleri);
$olaylar = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($olaylar as $olay) {
    echo "İşleniyor: Olay ID {$olay['olay_id']}\n";
    $veri = json_decode($olay['veri'], true);

    try {
        switch ($olay['olay_tipi']) {
            case 'siparis.basarili':
                $mailService->sendOrderConfirmation($veri['kullanici_eposta'], $veri);
                break;
            case 'siparis.kargolandi':
                $mailService->sendShippingConfirmation($veri['kullanici_eposta'], $veri);
                break;
            case 'iade.odeme_basarili':
                $mailService->sendRefundConfirmation($veri['kullanici_eposta'], $veri);
                break;
        }

        // İşlenen olayı işaretle
        $pdo->prepare("UPDATE olay_gunlugu SET islendi = 1 WHERE olay_id = ?")->execute([$olay['olay_id']]);

    } catch (Exception $e) {
        error_log("Bildirim olayı işlenirken hata (ID: {$olay['olay_id']}): " . $e->getMessage());
    }
}

echo "Bildirim worker'ı tamamlandı.\n";
