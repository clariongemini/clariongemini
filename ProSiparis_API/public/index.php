<?php
// public/index.php - Front Controller v2.5

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../vendor/manual_autoload.php';
}

require_once __DIR__ . '/../config/ayarlar.php';
require_once __DIR__ . '/../config/veritabani_baglantisi.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

use ProSiparis\Core\Request;
use ProSiparis\Core\Router;
use ProSiparis\Middleware\AuthMiddleware;
use ProSiparis\Middleware\PermissionMiddleware;
use ProSiparis\Controllers\KullaniciController;
use ProSiparis\Controllers\UrunController;
use ProSiparis\Controllers\SiparisController;
use ProSiparis\Controllers\AdresController;
use ProSiparis\Controllers\KargoController;
use ProSiparis\Controllers\CouponController;
use ProSiparis\Controllers\OdemeController;
use ProSiparis\Controllers\ReviewController;
use ProSiparis\Controllers\KategoriController;
use ProSiparis\Controllers\DashboardController;

$request = new Request();
$router = new Router($request);

// --- Rotaları Tanımla ---

// Kimlik Doğrulama Rotaları
$router->post('/api/kullanici/kayit', [KullaniciController::class, 'kayitOl']);
$router->post('/api/kullanici/giris', [KullaniciController::class, 'girisYap']);

// Ürün Rotaları (Herkese Açık)
$router->get('/api/urunler', [UrunController::class, 'listele']);
$router->get('/api/urunler/{id}', [UrunController::class, 'detay']);

// Kategori Rotaları (Herkese Açık)
$router->get('/api/kategoriler', [KategoriController::class, 'listele']);
$router->get('/api/kategoriler/{id}/urunler', [UrunController::class, 'kategoriyeGoreListele']);

// Değerlendirme Rotaları (Herkese Açık)
$router->get('/api/urunler/{id}/degerlendirmeler', [ReviewController::class, 'listele']);

// Kargo Rotaları (Herkese Açık)
$router->get('/api/kargo-secenekleri', [KargoController::class, 'listele']);


// --- Kullanıcı Korumalı Rotalar ---
$auth = AuthMiddleware::class;

// Kişiselleştirme Rotası (Yeni Eklendi)
$router->get('/api/kullanici/onerilen-urunler', [KullaniciController::class, 'onerilenUrunler'], [$auth]);

// Sipariş Rotaları
$router->get('/api/siparisler', [SiparisController::class, 'gecmis'], [$auth]);
$router->get('/api/siparisler/{id}', [SiparisController::class, 'detay'], [$auth]);

// Kullanıcı Profili Rotaları
$router->get('/api/kullanici/profil', [KullaniciController::class, 'profilGetir'], [$auth]);
$router->put('/api/kullanici/profil', [KullaniciController::class, 'profilGuncelle'], [$auth]);

// Favori Rotaları
$router->get('/api/kullanici/favoriler', [UrunController::class, 'favorileriListele'], [$auth]);
$router->post('/api/kullanici/favoriler', [UrunController::class, 'favoriyeEkle'], [$auth]);
$router->delete('/api/kullanici/favoriler/{id}', [UrunController::class, 'favoridenCikar'], [$auth]);

// Kullanıcı Adres Yönetimi
$router->get('/api/kullanici/adresler', [AdresController::class, 'listele'], [$auth]);
$router->post('/api/kullanici/adresler', [AdresController::class, 'olustur'], [$auth]);
$router->put('/api/kullanici/adresler/{id}', [AdresController::class, 'guncelle'], [$auth]);
$router->delete('/api/kullanici/adresler/{id}', [AdresController::class, 'sil'], [$auth]);

// Kupon ve Ödeme
$router->post('/api/sepet/kupon-dogrula', [CouponController::class, 'dogrula'], [$auth]);
$router->post('/api/odeme/baslat', [OdemeController::class, 'baslat'], [$auth]);
$router->post('/api/urunler/{id}/degerlendirme', [ReviewController::class, 'olustur'], [$auth]);

// Ödeme Callback (Public - Iyzico tarafından çağrılır)
$router->post('/api/odeme/callback/iyzico', [OdemeController::class, 'callback']);


// --- Admin Rotaları (Yetki Korumalı) ---

// Dashboard Rotaları
$dashboardYetkisi = [PermissionMiddleware::class, 'dashboard_goruntule'];
$router->get('/api/admin/dashboard/kpi-ozet', [DashboardController::class, 'kpiOzet'], [$auth, $dashboardYetkisi]);
$router->get('/api/admin/dashboard/satis-grafigi', [DashboardController::class, 'satisGrafigi'], [$auth, $dashboardYetkisi]);
$router->get('/api/admin/dashboard/en-cok-satilan-urunler', [DashboardController::class, 'enCokSatilanUrunler'], [$auth, $dashboardYetkisi]);
$router->get('/api/admin/dashboard/son-faaliyetler', [DashboardController::class, 'sonFaaliyetler'], [$auth, $dashboardYetkisi]);

// Ürün Yönetimi
$router->post('/api/admin/urunler', [UrunController::class, 'olustur'], [$auth, [PermissionMiddleware::class, 'urun_yarat']]);
$router->put('/api/admin/urunler/{id}', [UrunController::class, 'guncelle'], [$auth, [PermissionMiddleware::class, 'urun_guncelle']]);
$router->delete('/api/admin/urunler/{id}', [UrunController::class, 'sil'], [$auth, [PermissionMiddleware::class, 'urun_sil']]);

// Kategori Yönetimi
$router->post('/api/admin/kategoriler', [KategoriController::class, 'olustur'], [$auth, [PermissionMiddleware::class, 'urun_yarat']]);
$router->put('/api/admin/kategoriler/{id}', [KategoriController::class, 'guncelle'], [$auth, [PermissionMiddleware::class, 'urun_guncelle']]);
$router->delete('/api/admin/kategoriler/{id}', [KategoriController::class, 'sil'], [$auth, [PermissionMiddleware::class, 'urun_sil']]);

// Sipariş Yönetimi
$router->get('/api/admin/siparisler', [SiparisController::class, 'tumunuListele'], [$auth, [PermissionMiddleware::class, 'siparis_listele']]);
$router->put('/api/admin/siparisler/{id}', [SiparisController::class, 'durumGuncelle'], [$auth, [PermissionMiddleware::class, 'siparis_durum_guncelle']]);

// Değerlendirme Yönetimi
$router->delete('/api/admin/degerlendirmeler/{id}', [ReviewController::class, 'sil'], [$auth, [PermissionMiddleware::class, 'degerlendirme_sil']]);

// Kupon Yönetimi
$router->get('/api/admin/kuponlar', [CouponController::class, 'listele'], [$auth, [PermissionMiddleware::class, 'kupon_listele']]);
$router->post('/api/admin/kuponlar', [CouponController::class, 'olustur'], [$auth, [PermissionMiddleware::class, 'kupon_yarat']]);
$router->put('/api/admin/kuponlar/{id}', [CouponController::class, 'guncelle'], [$auth, [PermissionMiddleware::class, 'kupon_guncelle']]);
$router->delete('/api/admin/kuponlar/{id}', [CouponController::class, 'sil'], [$auth, [PermissionMiddleware::class, 'kupon_sil']]);


// İsteği işle ve uygun rotayı çalıştır
$router->dispatch();
