<?php
// public/index.php - Front Controller v2.7

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ... (autoload ve config dosyaları)
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/ayarlar.php';
require_once __DIR__ . '/../config/veritabani_baglantisi.php';
// ... (header'lar)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

use ProSiparis\Core\Request;
use ProSiparis\Core\Router;
use ProSiparis\Middleware\AuthMiddleware;
use ProSiparis\Middleware\PermissionMiddleware;
// Controller'lar
use ProSiparis\Controllers\CmsController;
use ProSiparis\Controllers\CronController;
use ProSiparis\Controllers\DashboardController;
use ProSiparis\Controllers\DepoController; // Yeni
use ProSiparis\Controllers\DestekController;
use ProSiparis\Controllers\KullaniciController;
use ProSiparis\Controllers\OdemeController;
use ProSiparis\Controllers\SepetController;
use ProSiparis\Controllers\SiparisController;
use ProSiparis\Controllers\UrunController;

$request = new Request();
$router = new Router($request);

// --- ROTA GRUPLARI ---
$auth = AuthMiddleware::class;

// --- Herkese Açık Rotalar ---
$router->post('/api/kullanici/giris', [KullaniciController::class, 'girisYap']);
$router->get('/api/urunler', [UrunController::class, 'listele']);
$router->get('/api/urunler/{id}', [UrunController::class, 'detay']);
$router->get('/api/sayfa/{slug}', [CmsController::class, 'getSayfa']);
$router->get('/api/bannerlar', [CmsController::class, 'getBannerlar']);
$router->post('/api/odeme/callback/iyzico', [OdemeController::class, 'callback']);

// --- Kullanıcı Korumalı Rotalar ---
$router->get('/api/sepet', [SepetController::class, 'sepetiGetir'], [$auth]);
$router->post('/api/sepet/guncelle', [SepetController::class, 'sepetiGuncelle'], [$auth]);
$router->post('/api/odeme/baslat', [OdemeController::class, 'baslat'], [$auth]);
// ... (diğer kullanıcı rotaları)

// --- Depo Operasyonları Rotaları (YENİ) ---
$toplamaYetkisi = [PermissionMiddleware::class, 'siparis_toplama_listesi_gor'];
$kargolamaYetkisi = [PermissionMiddleware::class, 'siparis_kargola'];
$router->get('/api/depo/hazirlanacak-siparisler', [DepoController::class, 'hazirlanacakSiparisler'], [$auth, $toplamaYetkisi]);
$router->get('/api/depo/siparis/{id}/toplama-listesi', [DepoController::class, 'toplamaListesi'], [$auth, $toplamaYetkisi]);
$router->post('/api/depo/siparis/{id}/kargoya-ver', [DepoController::class, 'kargoyaVer'], [$auth, $kargolamaYetkisi]);

// --- Admin Rotaları (Yetki Korumalı) ---
$siparisYonetYetkisi = [PermissionMiddleware::class, 'siparis_yonet'];
$router->put('/api/admin/siparisler/{id}', [SiparisController::class, 'durumGuncelle'], [$auth, $siparisYonetYetkisi]);
// ... (diğer admin rotaları)


// İsteği işle
$router->dispatch();
