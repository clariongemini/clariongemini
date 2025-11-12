<?php
namespace ProSiparis\Siparis\Tests\Integration;

use ProSiparis\Siparis\Tests\TestCase;

class SiparisDurumGuncellemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test için sahte bir sipariş oluştur
        $this->pdo->exec("INSERT INTO siparisler (kullanici_id, durum) VALUES (1, 'hazirlaniyor')");
    }

    /** @test */
    public function updating_an_order_status_also_creates_an_audit_log()
    {
        $siparisId = 1;
        $yeniDurum = 'kargolandi';
        $yapanKullaniciId = 99; // Test için varsayımsal bir admin ID'si

        // Varsayımsal bir API istemcisi ile isteği yap
        $client = new ApiTestClient($this->pdo);
        $response = $client->put(
            "/api/admin/siparisler/{$siparisId}/durum",
            ['durum' => $yeniDurum],
            ['X-User-ID' => $yapanKullaniciId] // Yetkili kullanıcıyı simüle et
        );

        // 1. Yanıtı doğrula
        $this->assertEquals(200, $response->getStatusCode());

        // 2. Sipariş durumunun güncellendiğini doğrula
        $stmtSiparis = $this->pdo->query("SELECT durum FROM siparisler WHERE siparis_id = {$siparisId}");
        $this->assertEquals($yeniDurum, $stmtSiparis->fetchColumn());

        // 3. Denetim kaydının oluşturulduğunu doğrula
        $stmtLog = $this->pdo->query("SELECT * FROM siparis_gecmisi_loglari WHERE siparis_id = {$siparisId}");
        $log = $stmtLog->fetch();

        $this->assertNotFalse($log, "Denetim kaydı bulunamadı.");
        $this->assertEquals($yapanKullaniciId, $log['yapan_kullanici_id']);
        $this->assertEquals('DURUM_GUNCELLEME', $log['eylem']);
        $this->assertStringContainsString($yeniDurum, $log['aciklama']);
    }
}

// Not: ApiTestClient, testleri hızlandırmak için kullanılan varsayımsal bir yardımcı sınıftır.
