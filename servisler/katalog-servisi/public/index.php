<?php
// Katalog-Servisi - public/index.php

require_once __DIR__ . '/../../../ProSiparis_API/vendor/autoload.php';
require_once __DIR__ . '/../../../ProSiparis_API/config/ayarlar.php';
require_once __DIR__ . '/../../../ProSiparis_API/config/veritabani_baglantisi.php';

use ProSiparis\Core\Request;
use ProSiparis\Core\Router;
use ProSiparis\Controllers\UrunController;
use ProSiparis\Controllers\KategoriController;

$request = new Request();
$router = new Router($request);

// Bu servisin yönettiği rotalar
$router->get('/api/urunler', [UrunController::class, 'listele']);
$router->get('/api/urunler/{id}', [UrunController::class, 'detay']);
$router->get('/api/kategoriler', [KategoriController::class, 'listele']);

$router->dispatch();
