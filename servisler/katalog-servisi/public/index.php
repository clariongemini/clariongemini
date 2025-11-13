<?php
// Katalog-Servisi - public/index.php

// ... (gerekli dosyalar)

use FulcrumOS\Core\Request;
use FulcrumOS\Core\Router;
use FulcrumOS\Controllers\UrunController;
use FulcrumOS\Controllers\KategoriController;

$request = new Request();
$router = new Router($request);

// --- PUBLIC API ENDPOINTS ---
$router->get('/api/urunler', [UrunController::class, 'listele']);
$router->get('/api/urunler/{id}', [UrunController::class, 'detay']);
$router->get('/api/kategoriler', [KategoriController::class, 'listele']);

// --- INTERNAL API ENDPOINTS (YENİ) ---
// Sadece diğer servisler tarafından çağrılmak içindir.
$router->get('/internal/katalog/varyantlar', [UrunController::class, 'internalVaryantlariGetir']);

$router->dispatch();
