<?php
// Tedarik-Servisi - public/index.php

// Otomatik yükleyiciyi ve temel sınıfları dahil et
// Not: Ortamda 'composer install' çalıştığı varsayılıyor.
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../ProSiparis_API/src/Core/Request.php';
require_once __DIR__ . '/../../ProSiparis_API/src/Core/Router.php';

// Veritabanı bağlantısını dahil et
require_once __DIR__ . '/../../ProSiparis_API/config/veritabani_baglantisi.php';

// Denetleyicileri dahil et
require_once __DIR__ . '/../src/Controllers/TedarikciController.php';
require_once __DIR__ . '/../src/Controllers/SatinAlmaController.php';

use ProSiparis\Core\Request;
use ProSiparis\Core\Router;
use ProSiparis\Tedarik\Controllers\TedarikciController;
use ProSiparis\Tedarik\Controllers\SatinAlmaController;

$request = new Request();
$router = new Router($request);

// Denetleyici nesnelerini oluştur
$tedarikciController = new TedarikciController($pdo); // $pdo, veritabani_baglantisi.php'den geliyor
$satinAlmaController = new SatinAlmaController($pdo);

// --- ROTA TANIMLAMALARI ---

// Tedarikçi API Rotaları
$router->get('/api/admin/tedarik/tedarikciler', [$tedarikciController, 'listele']);
$router->post('/api/admin/tedarik/tedarikciler', [$tedarikciController, 'olustur']);
$router->put('/api/admin/tedarik/tedarikciler/{id}', [$tedarikciController, 'guncelle']);
$router->delete('/api/admin/tedarik/tedarikciler/{id}', [$tedarikciController, 'sil']);

// Satın Alma Siparişi (PO) API Rotaları
$router->get('/api/admin/tedarik/siparisler', [$satinAlmaController, 'listele']);
$router->post('/api/admin/tedarik/siparisler', [$satinAlmaController, 'olustur']);
$router->get('/api/admin/tedarik/siparisler/{poId}', [$satinAlmaController, 'detayGetir']);
$router->put('/api/admin/tedarik/siparisler/{poId}/durum', [$satinAlmaController, 'durumGuncelle']);
$router->post('/api/admin/tedarik/siparisler/{poId}/teslim-al', [$satinAlmaController, 'teslimAl']);
$router->get('/api/admin/tedarik/siparisler/{poId}/gecmis', [$satinAlmaController, 'gecmisLoglariGetir']);

$router->dispatch();
