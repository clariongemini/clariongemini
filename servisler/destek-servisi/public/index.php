<?php
// Destek-Servisi - public/index.php

// Otomatik yükleyiciyi ve temel sınıfları dahil et
// Not: Ortamda 'composer install' çalıştığı varsayılıyor.
require_once __DIR__ . '/../../ProSiparis_API/vendor/autoload.php';
require_once __DIR__ . '/../../ProSiparis_API/src/Core/Request.php';
require_once __DIR__ . '/../../ProSiparis_API/src/Core/Router.php';

// Veritabanı bağlantısını dahil et
require_once __DIR__ . '/../../ProSiparis_API/config/veritabani_baglantisi.php';

// Denetleyicileri dahil et
require_once __DIR__ . '/../src/Controllers/DestekController.php';

use ProSiparis\Core\Request;
use ProSiparis\Core\Router;
use ProSiparis\Destek\Controllers\DestekController;

$request = new Request();
$router = new Router($request);

// Denetleyici nesnesini oluştur
$destekController = new DestekController($pdo); // $pdo, veritabani_baglantisi.php'den geliyor

// --- ROTA TANIMLAMALARI ---

// Destek Talebi API Rotaları
$router->get('/api/admin/destek-talepleri', [$destekController, 'listele']);
$router->get('/api/admin/destek-talepleri/{talepId}', [$destekController, 'detayGetir']);
$router->post('/api/admin/destek-talepleri/{talepId}/mesaj', [$destekController, 'mesajGonder']);
$router->put('/api/admin/destek-talepleri/{talepId}/durum', [$destekController, 'durumGuncelle']);

$router->dispatch();
