<?php
namespace ProSiparis\Envanter\Tests\Unit;

use ProSiparis\Envanter\Tests\TestCase;
// Bu servisin var olduğunu ve AOMHesapla metoduna sahip olduğunu varsayıyoruz.
use ProSiparis\Envanter\Service\EnvanterService;

class AomHesaplamaTest extends TestCase
{
    /** @test */
    public function it_calculates_the_weighted_average_cost_correctly()
    {
        $envanterService = new EnvanterService();

        // Senaryo: Depoda 10 adet ürün var, maliyeti 100 TL.
        //           Yeni gelen 10 adet ürünün maliyeti 120 TL.
        // Beklenen Sonuç: Yeni ortalama maliyet 110 TL olmalı.
        // ((10 * 100) + (10 * 120)) / (10 + 10) = (1000 + 1200) / 20 = 2200 / 20 = 110

        $eskiStok = 10;
        $eskiMaliyet = 100.00;
        $yeniStok = 10;
        $yeniMaliyet = 120.00;

        $yeniOrtalamaMaliyet = $envanterService->AOMHesapla($eskiStok, $eskiMaliyet, $yeniStok, $yeniMaliyet);

        $this->assertEquals(110.00, $yeniOrtalamaMaliyet, "Ağırlıklı Ortalama Maliyet doğru hesaplanmalıdır.");
    }

    /** @test */
    public function it_handles_zero_initial_stock_correctly()
    {
        $envanterService = new EnvanterService();

        // Senaryo: Depoda hiç ürün yok. 20 adet ürün 150 TL maliyetle giriyor.
        // Beklenen Sonuç: Yeni ortalama maliyet 150 TL olmalı.

        $eskiStok = 0;
        $eskiMaliyet = 0.00;
        $yeniStok = 20;
        $yeniMaliyet = 150.00;

        $yeniOrtalamaMaliyet = $envanterService->AOMHesapla($eskiStok, $eskiMaliyet, $yeniStok, $yeniMaliyet);

        $this->assertEquals(150.00, $yeniOrtalamaMaliyet, "Başlangıç stoğu sıfır olduğunda ortalama maliyet yeni maliyete eşit olmalıdır.");
    }
}
