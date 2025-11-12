<?php
namespace ProSiparis\Tedarik\Tests\Integration;

use ProSiparis\Tedarik\Tests\TestCase;
// Gerekli servislerin ve denetleyicilerin var olduğunu varsayıyoruz
use ProSiparis\Tedarik\Controllers\SatinAlmaController;
use ProSiparis\Envanter\Service\EnvanterService;
// EventBusService'in bir mock'u (taklidi) kullanılacak
use ProSiparis\Core\Tests\Mocks\MockEventBusService;

class TedarikMalKabulEnvanterGuncellemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test için sahte tedarikçi ve satın alma siparişi oluştur
        $this->pdoTedarik->exec("INSERT INTO tedarikciler (firma_adi) VALUES ('Test Tedarikçi')");
        $this->pdoTedarik->exec("INSERT INTO tedarik_siparisleri (tedarikci_id, hedef_depo_id) VALUES (1, 1)");
        $this->pdoTedarik->exec("INSERT INTO tedarik_siparis_urunleri (po_id, varyant_id, siparis_edilen_adet, maliyet_fiyati) VALUES (1, 123, 10, 100.00)");
    }

    /** @test */
    public function receiving_goods_publishes_an_event_and_updates_inventory()
    {
        // 1. Kurulum (Arrange)
        $poId = 1;
        $teslimEdilenUrunler = [
            ['varyant_id' => 123, 'teslim_alinan_adet' => 8]
        ];

        $mockEventBus = new MockEventBusService();
        // SatinAlmaController'ın bu mock'u kullanacağını varsayıyoruz (Dependency Injection ile)
        $satinAlmaController = new SatinAlmaController($this->pdoTedarik, $mockEventBus);

        // 2. Eylem (Act)
        // API endpoint'ini doğrudan çağırarak olayı tetikle
        $_SERVER['HTTP_X_USER_ID'] = 99; // Simüle edilmiş kullanıcı
        $_POST['teslim_edilen_urunler'] = json_encode($teslimEdilenUrunler); // Simüle edilmiş istek gövdesi
        $satinAlmaController->teslimAl($poId);

        // 3. Doğrulama (Assert) - Olay Yayınlama Kısmı
        $this->assertEquals(1, $mockEventBus->getPublishedEventCount(), "Bir olay yayınlanmalıydı.");
        $this->assertEquals('tedarik.mal_kabul_yapildi', $mockEventBus->getLastPublishedEventName());

        $eventPayload = $mockEventBus->getLastPublishedEventPayload();
        $this->assertEquals($poId, $eventPayload['po_id']);
        $this->assertEquals(123, $eventPayload['gelen_urunler'][0]['varyant_id']);
        $this->assertEquals(8, $eventPayload['gelen_urunler'][0]['gelen_adet']);
        $this->assertEquals(100.00, $eventPayload['gelen_urunler'][0]['maliyet_fiyati']);

        // 4. Eylem (Act) - Olay Tüketme Kısmı
        // EnvanterServisi'nin worker'ını manuel olarak tetikle
        $envanterService = new EnvanterService($this->pdoEnvanter);
        $envanterService->stokGuncelle($eventPayload); // Worker'ın çağıracağı metod

        // 5. Doğrulama (Assert) - Envanter Güncelleme Kısmı
        $stmt = $this->pdoEnvanter->query("SELECT adet FROM depo_stoklari WHERE depo_id = 1 AND varyant_id = 123");
        $stok = $stmt->fetchColumn();

        $this->assertEquals(8, $stok, "Envanterdeki stok adedi doğru şekilde güncellenmelidir.");
    }
}
