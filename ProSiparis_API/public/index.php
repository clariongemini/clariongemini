<?php
// public/index.php - Front Controller v2.8 - Nihai ve Tam Sürüm

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/ayarlar.php';
require_once __DIR__ . '/../config/veritabani_baglantisi.php';

header("Content-Type: application/json; charset=UTF-8");

use ProSiparis\Core\Request;
use ProSiparis\Core\Router;
use ProSiparis\Middleware\AuthMiddleware;
use ProSiparis\Middleware\PermissionMiddleware;
use ProSiparis\Controllers\AdminController;
use ProSiparis\Controllers\DepoController;
use ProSiparis\Controllers\KullaniciController;
use ProSiparis\Controllers\TedarikController;

$request = new Request();
$router = new Router($request);
$auth = AuthMiddleware::class;

// --- Rota Tanımlamaları ---

// Kullanıcı & Genel Rotalar
$router->post('/api/kullanici/iade-talebi-olustur', [KullaniciController::class, 'iadeTalebiOlustur'], [$auth]);
$router->get('/api/kullanici/iade-talepleri', [KullaniciController::class, 'iadeTalepleriniListele'], [$auth]);

// Depo Rotaları
$router->get('/api/depo/beklenen-teslimatlar', [DepoController::class, 'beklenenTeslimatlar'], [$auth, [PermissionMiddleware::class, 'satin_alma_teslim_al']]);
$router->post('/api/depo/teslimat-al/{po_id}', [DepoController::class, 'teslimatAl'], [$auth, [PermissionMiddleware::class, 'satin_alma_teslim_al']]);
$router->post('/api/depo/iade-teslim-al/{iade_id}', [DepoController::class, 'iadeTeslimAl'], [$auth, [PermissionMiddleware::class, 'iade_teslim_al']]);

// Admin Rotaları
$tedarikciYetkisi = [PermissionMiddleware::class, 'tedarikci_yonet'];
$satinAlmaYetkisi = [PermissionMiddleware::class, 'satin_alma_yonet'];
$iadeYonetYetkisi = [PermissionMiddleware::class, 'iade_yonet'];

// Tedarik Zinciri Yönetimi (TAMAMLANDI)
$router->get('/api/admin/tedarikciler', [TedarikController::class, 'listeleTedarikciler'], [$auth, $tedarikciYetkisi]);
$router->post('/api/admin/tedarikciler', [TedarikController::class, 'olusturTedarikci'], [$auth, $tedarikciYetkisi]);
$router->put('/api/admin/tedarikciler/{id}', [TedarikController::class, 'guncelleTedarikci'], [$auth, $tedarikciYetkisi]);
$router->delete('/api/admin/tedarikciler/{id}', [TedarikController::class, 'silTedarikci'], [$auth, $tedarikciYetkisi]);

$router->get('/api/admin/satin-alma-siparisleri', [TedarikController::class, 'listeleSatinAlmaSiparisleri'], [$auth, $satinAlmaYetkisi]);
$router->post('/api/admin/satin-alma-siparisleri', [TedarikController::class, 'olusturSatinAlmaSiparisi'], [$auth, $satinAlmaYetkisi]);
$router->put('/api/admin/satin-alma-siparisleri/{id}', [TedarikController::class, 'guncelleSatinAlmaSiparisi'], [$auth, $satinAlmaYetkisi]);


// İade Yönetimi
$router->get('/api/admin/iade-talepleri', [AdminController::class, 'listeleIadeTalepleri'], [$auth, $iadeYonetYetkisi]);
$router->put('/api/admin/iade-talepleri/{id}/durum-guncelle', [AdminController::class, 'iadeDurumGuncelle'], [$auth, $iadeYonetYetkisi]);
$router->post('/api/admin/iade-talepleri/{id}/odeme-yap', [AdminController::class, 'iadeOdemeYap'], [$auth, $iadeYonetYetkisi]);

$router->dispatch();
