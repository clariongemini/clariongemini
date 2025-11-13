<?php
// Auth-Servisi - public/index.php

// Gerekli dosyaları ve yapılandırmayı yükle
require_once __DIR__ . '/../../../FulcrumOS_API/vendor/autoload.php';
require_once __DIR__ . '/../../../FulcrumOS_API/config/ayarlar.php';
require_once __DIR__ . '/../../../FulcrumOS_API/config/veritabani_baglantisi.php';

use FulcrumOS\Core\Request;
use FulcrumOS\Core\Router;
use FulcrumOS\Controllers\KullaniciController;

$request = new Request();
$router = new Router($request);

// Bu servisin yönettiği rotalar
$router->post('/api/kullanici/kayit', [KullaniciController::class, 'kayitOl']);
$router->post('/api/kullanici/giris', [KullaniciController::class, 'girisYap']);

// Dahili (servisler arası) rotalar
$router->get('/internal/kullanici/{id}', [KullaniciController::class, 'dahiliKullaniciGetir']);

$router->dispatch();
