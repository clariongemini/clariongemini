<?php
// Auth-Servisi - public/index.php

// Gerekli dosyaları ve yapılandırmayı yükle
require_once __DIR__ . '/../../../ProSiparis_API/vendor/autoload.php';
require_once __DIR__ . '/../../../ProSiparis_API/config/ayarlar.php';
require_once __DIR__ . '/../../../ProSiparis_API/config/veritabani_baglantisi.php';

use ProSiparis\Core\Request;
use ProSiparis\Core\Router;
use ProSiparis\Controllers\KullaniciController;

$request = new Request();
$router = new Router($request);

// Bu servisin yönettiği rotalar
$router->post('/api/kullanici/kayit', [KullaniciController::class, 'kayitOl']);
$router->post('/api/kullanici/giris', [KullaniciController::class, 'girisYap']);

// Dahili (servisler arası) rotalar
$router->get('/internal/kullanici/{id}', [KullaniciController::class, 'dahiliKullaniciGetir']);

$router->dispatch();
