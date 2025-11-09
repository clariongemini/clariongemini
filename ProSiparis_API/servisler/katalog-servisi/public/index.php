<?php
// Katalog-Servisi - public/index.php

// ... (gerekli dosyalar)

use ProSiparis\Core\Request;
use ProSiparis\Core\Router;
use ProSiparis\Controllers\UrunController;
use ProSiparis\Controllers\KategoriController;

$request = new Request();
$router = new Router($request);

// --- PUBLIC API ENDPOINTS ---
$router->get('/api/urunler', [UrunController::class, 'listele']);
$router->get('/api/urunler/{id}', [UrunController::class, 'detay']);
$router->get('/api/kategoriler', [KategoriController::class, 'listele']);

// --- INTERNAL API ENDPOINTS (YENİ) ---
// Sadece diğer servisler tarafından çağrılmak içindir.
$router->get('/internal/katalog/varyantlar', [UrunController::class, 'internalVaryantlariGetir']);
$router->get('/internal/urun-takip-yontemi', [UrunController::class, 'internalGetTakipYontemi']);

$router->dispatch();
