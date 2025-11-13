<?php
// Ana Monolith (Legacy Core) - public/index.legacy.php

// ... (gerekli dosyalar)

use FulcrumOS\Core\Request;
use FulcrumOS\Core\Router;
use FulcrumOS\Controllers\CouponController;

$request = new Request();
$router = new Router($request);

// --- INTERNAL API ENDPOINTS ---
$router->post('/internal/legacy/kupon-dogrula', [CouponController::class, 'internalKuponDogrula']);

// --- PUBLIC API ENDPOINTS ---
// ... (CMS, Destek Talepleri gibi Monolith'te kalan özelliklerin rotaları)

$router->dispatch();
