<?php
// Siparis-Servisi - public/index.php

// Gerekli dosyaları ve yapılandırmayı yükle
// ...

use FulcrumOS\Core\Request;
use FulcrumOS\Core\Router;
use FulcrumOS\Controllers\PaymentController;
use FulcrumOS\Controllers\AdresController;
use FulcrumOS\Controllers\DepoController;
use FulcrumOS\Controllers\SiparisController;
// ... (ve diğerleri)

$request = new Request();
$router = new Router($request);

// Bu servisin yönettiği tüm rotalar
// Ödeme Rotaları
$router->post('/api/odeme/baslat', [PaymentController::class, 'baslat']);
$router->post('/api/odeme/callback/iyzico', [PaymentController::class, 'callback']);

// Adres Rotaları
$router->get('/api/kullanici/adresler', [AdresController::class, 'listele']);
// ... (diğer adres CRUD rotaları)

// Depo Rotaları (Siparişle ilgili olanlar)
$router->get('/api/depo/hazirlanacak-siparisler', [DepoController::class, 'hazirlanacakSiparisler']);
$router->post('/api/depo/siparis/{id}/kargoya-ver', [DepoController::class, 'kargoyaVer']);
// ...

// Sipariş Rotaları
$router->get('/api/kullanici/siparisler', [SiparisController::class, 'gecmis']);
// ...

$router->dispatch();
