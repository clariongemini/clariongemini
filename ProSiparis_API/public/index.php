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
$router->get('/api/siparisler/{id}', [\ProSiparis\Controller\SiparisController::class, 'detay'], [$authMiddleware]);
// POST /api/siparisler rotası kaldırıldı. Siparişler artık sadece ödeme callback'i ile oluşturulur.

// Kullanıcı Profili Rotaları (Kullanıcı Korumalı)
$router->get('/api/kullanici/profil', [\ProSiparis\Controller\KullaniciController::class, 'profilGetir'], [$authMiddleware]);
$router->put('/api/kullanici/profil', [\ProSiparis\Controller\KullaniciController::class, 'profilGuncelle'], [$authMiddleware]);

// Favori Rotaları (Kullanıcı Korumalı)
$router->get('/api/kullanici/favoriler', [\ProSiparis\Controller\UrunController::class, 'favorileriListele'], [$authMiddleware]);
$router->post('/api/kullanici/favoriler', [\ProSiparis\Controller\UrunController::class, 'favoriyeEkle'], [$authMiddleware]);
$router->delete('/api/kullanici/favoriler/{id}', [\ProSiparis\Controller\UrunController::class, 'favoridenCikar'], [$authMiddleware]);

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

// Kullanıcı Adres Yönetimi Rotaları (Kullanıcı Korumalı)
$router->get('/api/kullanici/adresler', [\ProSiparis\Controller\AdresController::class, 'listele'], [$authMiddleware]);
$router->post('/api/kullanici/adresler', [\ProSiparis\Controller\AdresController::class, 'olustur'], [$authMiddleware]);
$router->put('/api/kullanici/adresler/{id}', [\ProSiparis\Controller\AdresController::class, 'guncelle'], [$authMiddleware]);
$router->delete('/api/kullanici/adresler/{id}', [\ProSiparis\Controller\AdresController::class, 'sil'], [$authMiddleware]);

// Kargo Rotaları
$router->get('/api/kargo-secenekleri', [\ProSiparis\Controller\KargoController::class, 'listele']); // Herkese açık

// Kupon Rotaları
$router->post('/api/sepet/kupon-dogrula', [\ProSiparis\Controller\CouponController::class, 'dogrula'], [$authMiddleware]);

// Ödeme Rotaları
$router->post('/api/odeme/baslat', [\ProSiparis\Controller\OdemeController::class, 'baslat'], [$authMiddleware]);
$router->post('/api/odeme/callback/iyzico', [\ProSiparis\Controller\OdemeController::class, 'callback']); // Webhook

// Değerlendirme Rotaları
$router->get('/api/urunler/{id}/degerlendirmeler', [\ProSiparis\Controller\ReviewController::class, 'listele']); // Herkese açık
$router->post('/api/urunler/{id}/degerlendirme', [\ProSiparis\Controller\ReviewController::class, 'olustur'], [$authMiddleware]);
$router->delete('/api/degerlendirmeler/{id}', [\ProSiparis\Controller\ReviewController::class, 'sil'], [$authMiddleware]);

// Kategori Rotaları
$router->get('/api/kategoriler', [\ProSiparis\Controller\KategoriController::class, 'listele']); // Herkese açık
$router->get('/api/kategoriler/{id}/urunler', [\ProSiparis\Controller\UrunController::class, 'kategoriyeGoreListele']); // Herkese açık
$router->post('/api/admin/kategoriler', [\ProSiparis\Controller\KategoriController::class, 'olustur'], $adminProtected);
$router->put('/api/admin/kategoriler/{id}', [\ProSiparis\Controller\KategoriController::class, 'guncelle'], $adminProtected);
$router->delete('/api/admin/kategoriler/{id}', [\ProSiparis\Controller\KategoriController::class, 'sil'], $adminProtected);


// İsteği işle ve uygun rotayı çalıştır
$router->dispatch();
