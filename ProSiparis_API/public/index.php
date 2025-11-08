<?php
// public/index.php - Front Controller v2.6

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
// Controller'ları alfabetik sıraya alalım
use ProSiparis\Controllers\AdresController;
use ProSiparis\Controllers\CouponController;
use ProSiparis\Controllers\CmsController;
use ProSiparis\Controllers\CronController; // Yeni
use ProSiparis\Controllers\DashboardController;
use ProSiparis\Controllers\DestekController;
use ProSiparis\Controllers\KargoController;
use ProSiparis\Controllers\KategoriController;
use ProSiparis\Controllers\KullaniciController;
use ProSiparis\Controllers\OdemeController;
use ProSiparis\Controllers\ReviewController;
use ProSiparis\Controllers\SepetController;
use ProSiparis\Controllers\SiparisController;
use ProSiparis\Controllers\UrunController;

$request = new Request();
$router = new Router($request);

// --- Herkese Açık Rotalar ---
$router->post('/api/kullanici/kayit', [KullaniciController::class, 'kayitOl']);
$router->post('/api/kullanici/giris', [KullaniciController::class, 'girisYap']);
$router->get('/api/urunler', [UrunController::class, 'listele']);
$router->get('/api/urunler/{id}', [UrunController::class, 'detay']);
$router->get('/api/kategoriler', [KategoriController::class, 'listele']);
$router->get('/api/kategoriler/{id}/urunler', [UrunController::class, 'kategoriyeGoreListele']);
$router->get('/api/urunler/{id}/degerlendirmeler', [ReviewController::class, 'listele']);
$router->get('/api/kargo-secenekleri', [KargoController::class, 'listele']);
$router->get('/api/sayfa/{slug}', [CmsController::class, 'getSayfa']);
$router->get('/api/bannerlar', [CmsController::class, 'getBannerlar']);
$router->post('/api/odeme/callback/iyzico', [OdemeController::class, 'callback']);

// --- Kullanıcı Korumalı Rotalar ---
$auth = AuthMiddleware::class;

$router->get('/api/sepet', [SepetController::class, 'sepetiGetir'], [$auth]);
$router->post('/api/sepet/guncelle', [SepetController::class, 'sepetiGuncelle'], [$auth]);
// (Diğer kullanıcı rotaları...)
$router->get('/api/kullanici/destek-talepleri', [DestekController::class, 'kullaniciTalepleriniListele'], [$auth]);
$router->post('/api/kullanici/destek-talepleri', [DestekController::class, 'talepOlustur'], [$auth]);
$router->get('/api/kullanici/destek-talepleri/{id}', [DestekController::class, 'talepDetaylariniGetir'], [$auth]);
$router->post('/api/kullanici/destek-talepleri/{id}/mesaj', [DestekController::class, 'kullaniciMesajEkle'], [$auth]);
$router->get('/api/kullanici/onerilen-urunler', [KullaniciController::class, 'onerilenUrunler'], [$auth]);
$router->get('/api/siparisler', [SiparisController::class, 'gecmis'], [$auth]);
$router->get('/api/siparisler/{id}', [SiparisController::class, 'detay'], [$auth]);
$router->get('/api/kullanici/profil', [KullaniciController::class, 'profilGetir'], [$auth]);
$router->put('/api/kullanici/profil', [KullaniciController::class, 'profilGuncelle'], [$auth]);
$router->get('/api/kullanici/favoriler', [UrunController::class, 'favorileriListele'], [$auth]);
$router->post('/api/kullanici/favoriler', [UrunController::class, 'favoriyeEkle'], [$auth]);
$router->delete('/api/kullanici/favoriler/{id}', [UrunController::class, 'favoridenCikar'], [$auth]);
$router->get('/api/kullanici/adresler', [AdresController::class, 'listele'], [$auth]);
$router->post('/api/kullanici/adresler', [AdresController::class, 'olustur'], [$auth]);
$router->put('/api/kullanici/adresler/{id}', [AdresController::class, 'guncelle'], [$auth]);
$router->delete('/api/kullanici/adresler/{id}', [AdresController::class, 'sil'], [$auth]);
$router->post('/api/sepet/kupon-dogrula', [CouponController::class, 'dogrula'], [$auth]);
$router->post('/api/odeme/baslat', [OdemeController::class, 'baslat'], [$auth]);
$router->post('/api/urunler/{id}/degerlendirme', [ReviewController::class, 'olustur'], [$auth]);

// --- Admin Rotaları (Yetki Korumalı) ---
$cmsYetkisi = [PermissionMiddleware::class, 'cms_yonet'];
$router->get('/api/admin/sayfalar', [CmsController::class, 'listeleSayfalar'], [$auth, $cmsYetkisi]);
$router->post('/api/admin/sayfalar', [CmsController::class, 'olusturSayfa'], [$auth, $cmsYetkisi]);
$router->put('/api/admin/sayfalar/{id}', [CmsController::class, 'guncelleSayfa'], [$auth, $cmsYetkisi]);
$router->delete('/api/admin/sayfalar/{id}', [CmsController::class, 'silSayfa'], [$auth, $cmsYetkisi]);
// (Diğer admin rotaları...)
$router->get('/api/admin/bannerlar', [CmsController::class, 'listeleBannerlar'], [$auth, $cmsYetkisi]);
$router->post('/api/admin/bannerlar', [CmsController::class, 'olusturBanner'], [$auth, $cmsYetkisi]);
$router->put('/api/admin/bannerlar/{id}', [CmsController::class, 'guncelleBanner'], [$auth, $cmsYetkisi]);
$router->delete('/api/admin/bannerlar/{id}', [CmsController::class, 'silBanner'], [$auth, $cmsYetkisi]);
$destekYetkisi = [PermissionMiddleware::class, 'destek_yonet'];
$router->get('/api/admin/destek-talepleri', [DestekController::class, 'tumTalepleriListele'], [$auth, $destekYetkisi]);
$router->post('/api/admin/destek-talepleri/{id}/mesaj', [DestekController::class, 'adminMesajEkle'], [$auth, $destekYetkisi]);
$dashboardYetkisi = [PermissionMiddleware::class, 'dashboard_goruntule'];
$router->get('/api/admin/dashboard/kpi-ozet', [DashboardController::class, 'kpiOzet'], [$auth, $dashboardYetkisi]);
$router->get('/api/admin/dashboard/satis-grafigi', [DashboardController::class, 'satisGrafigi'], [$auth, $dashboardYetkisi]);
$router->get('/api/admin/dashboard/en-cok-satilan-urunler', [DashboardController::class, 'enCokSatilanUrunler'], [$auth, $dashboardYetkisi]);
$router->get('/api/admin/dashboard/son-faaliyetler', [DashboardController::class, 'sonFaaliyetler'], [$auth, $dashboardYetkisi]);
$router->post('/api/admin/urunler', [UrunController::class, 'olustur'], [$auth, [PermissionMiddleware::class, 'urun_yonet']]);
$router->put('/api/admin/urunler/{id}', [UrunController::class, 'guncelle'], [$auth, [PermissionMiddleware::class, 'urun_yonet']]);
$router->delete('/api/admin/urunler/{id}', [UrunController::class, 'sil'], [$auth, [PermissionMiddleware::class, 'urun_yonet']]);
$router->post('/api/admin/kategoriler', [KategoriController::class, 'olustur'], [$auth, [PermissionMiddleware::class, 'urun_yonet']]);
$router->put('/api/admin/kategoriler/{id}', [KategoriController::class, 'guncelle'], [$auth, [PermissionMiddleware::class, 'urun_yonet']]);
$router->delete('/api/admin/kategoriler/{id}', [KategoriController::class, 'sil'], [$auth, [PermissionMiddleware::class, 'urun_yonet']]);
$router->get('/api/admin/siparisler', [SiparisController::class, 'tumunuListele'], [$auth, [PermissionMiddleware::class, 'siparis_yonet']]);
$router->put('/api/admin/siparisler/{id}', [SiparisController::class, 'durumGuncelle'], [$auth, [PermissionMiddleware::class, 'siparis_yonet']]);
$router->delete('/api/admin/degerlendirmeler/{id}', [ReviewController::class, 'sil'], [$auth, [PermissionMiddleware::class, 'degerlendirme_yonet']]);
$router->get('/api/admin/kuponlar', [CouponController::class, 'listele'], [$auth, [PermissionMiddleware::class, 'kupon_yonet']]);
$router->post('/api/admin/kuponlar', [CouponController::class, 'olustur'], [$auth, [PermissionMiddleware::class, 'kupon_yonet']]);
$router->put('/api/admin/kuponlar/{id}', [CouponController::class, 'guncelle'], [$auth, [PermissionMiddleware::class, 'kupon_yonet']]);
$router->delete('/api/admin/kuponlar/{id}', [CouponController::class, 'sil'], [$auth, [PermissionMiddleware::class, 'kupon_yonet']]);

// --- Cron Job (Zamanlanmış Görev) Rotası ---
// Bu rota, sunucudaki bir cron job tarafından tetiklenmelidir.
// Kendi içinde `CRON_SECRET_KEY` ile korunmaktadır.
$router->post('/api/cron/run', [CronController::class, 'run']);


// İsteği işle ve uygun rotayı çalıştır
$router->dispatch();
