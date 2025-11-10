<?php
// Katalog-Servisi - public/index.php

// ... (gerekli dosyalar)

use ProSiparis\Core\Request;
use ProSiparis\Core\Router;
use ProSiparis\Controllers\UrunController;
use ProSiparis\Controllers\KategoriController;
use ProSiparis\Controllers\FeedController;

$request = new Request();
$router = new Router($request);

// --- PUBLIC API ENDPOINTS ---
$router->get('/api/urunler', [UrunController::class, 'listele']);
$router->get('/api/urunler/{id}', [UrunController::class, 'detay']);
$router->get('/api/kategoriler', [KategoriController::class, 'listele']);

// --- v5.2 PUBLIC XML FEED ENDPOINTS ---
$router->get('/sitemap.xml', [FeedController::class, 'generateSitemap']);
$router->get('/api/feeds/google-merchant.xml', [FeedController::class, 'generateGoogleMerchantFeed']);
$router->get('/api/feeds/bing-shopping.xml', [FeedController::class, 'generateBingShoppingFeed']);


// --- INTERNAL API ENDPOINTS (YENİ) ---
// Sadece diğer servisler tarafından çağrılmak içindir.
$router->get('/internal/katalog/varyantlar', [UrunController::class, 'internalVaryantlariGetir']);
$router->get('/internal/urun-takip-yontemi', [UrunController::class, 'internalGetTakipYontemi']);
$router->get('/internal/varyant-detaylari/{id}', [UrunController::class, 'internalGetVaryantDetaylari']);

$router->dispatch();
