<?php
// public/index.php - Front Controller

// Hata raporlamayı aç
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Composer Autoloader
// Composer'ın oluşturduğu varsayılan autoload dosyasını dahil et
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Bu fallback, Composer'ın çalışmadığı ortamlar için geçici bir çözümdür.
    // Gerçek bir sunucuda `composer install` çalıştırılmalıdır.
    require_once __DIR__ . '/../vendor/manual_autoload.php';
}

// Yapılandırma dosyası
require_once __DIR__ . '/../config/ayarlar.php';
// Veritabanı bağlantısı
require_once __DIR__ . '/../config/veritabani_baglantisi.php';

// Temel başlıkları ayarla
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// İsteği ve Router'ı başlat
$request = new \ProSiparis\Core\Request();
$router = new \ProSiparis\Core\Router($request);

// --- Rotaları Tanımla ---

// Kimlik Doğrulama Rotaları
$router->post('/api/kullanici/kayit', [\ProSiparis\Controller\KullaniciController::class, 'kayitOl']);
$router->post('/api/kullanici/giris', [\ProSiparis\Controller\KullaniciController::class, 'girisYap']);

// Ürün Rotaları (Herkese Açık)
$router->get('/api/urunler', [\ProSiparis\Controller\UrunController::class, 'listele']);
$router->get('/api/urunler/{id}', [\ProSiparis\Controller\UrunController::class, 'detay']);

// Sipariş Rotaları (Kullanıcı Korumalı)
$authMiddleware = \ProSiparis\Middleware\AuthMiddleware::class;
$router->get('/api/siparisler', [\ProSiparis\Controller\SiparisController::class, 'gecmis'], [$authMiddleware]);
$router->post('/api/siparisler', [\ProSiparis\Controller\SiparisController::class, 'olustur'], [$authMiddleware]);

// Kullanıcı Profili Rotaları (Kullanıcı Korumalı)
$router->get('/api/kullanici/profil', [\ProSiparis\Controller\KullaniciController::class, 'profilGetir'], [$authMiddleware]);
$router->put('/api/kullanici/profil', [\ProSiparis\Controller\KullaniciController::class, 'profilGuncelle'], [$authMiddleware]);

// --- Admin Rotaları (Admin Korumalı) ---
$adminMiddleware = \ProSiparis\Middleware\AdminMiddleware::class;
$adminProtected = [$authMiddleware, $adminMiddleware];

// Admin: Ürün Yönetimi
$router->post('/api/admin/urunler', [\ProSiparis\Controller\UrunController::class, 'olustur'], $adminProtected);
$router->put('/api/admin/urunler/{id}', [\ProSiparis\Controller\UrunController::class, 'guncelle'], $adminProtected);
$router->delete('/api/admin/urunler/{id}', [\ProSiparis\Controller\UrunController::class, 'sil'], $adminProtected);

// Admin: Sipariş Yönetimi
$router->get('/api/admin/siparisler', [\ProSiparis\Controller\SiparisController::class, 'tumunuListele'], $adminProtected);
$router->put('/api/admin/siparisler/{id}', [\ProSiparis\Controller\SiparisController::class, 'durumGuncelle'], $adminProtected);

// Kategori Rotaları
$router->get('/api/kategoriler', [\ProSiparis\Controller\KategoriController::class, 'listele']); // Herkese açık
$router->get('/api/kategoriler/{id}/urunler', [\ProSiparis\Controller\UrunController::class, 'kategoriyeGoreListele']); // Herkese açık
$router->post('/api/admin/kategoriler', [\ProSiparis\Controller\KategoriController::class, 'olustur'], $adminProtected);
$router->put('/api/admin/kategoriler/{id}', [\ProSiparis\Controller\KategoriController::class, 'guncelle'], $adminProtected);
$router->delete('/api/admin/kategoriler/{id}', [\ProSiparis\Controller\KategoriController::class, 'sil'], $adminProtected);


// İsteği işle ve uygun rotayı çalıştır
$router->dispatch();
