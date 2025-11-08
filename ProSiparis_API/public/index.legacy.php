<?php
// public/index.php - Front Controller v2.9

// ... (autoload, config, headers)

use ProSiparis\Core\Request;
use ProSiparis\Core\Router;
use ProSiparis\Middleware\AuthMiddleware;
use ProSiparis\Middleware\PermissionMiddleware;
use ProSiparis\Controllers\DepoController;
use ProSiparis\Controllers\ReportController; // Yeni
// ... (diğer controller'lar)

$request = new Request();
$router = new Router($request);
$auth = AuthMiddleware::class;

// --- Depo Rotaları ---
$router->post('/api/depo/envanter-duzeltme', [DepoController::class, 'envanterDuzeltme'], [$auth, [PermissionMiddleware::class, 'envanter_duzelt']]);
// ...

// --- Admin Rotaları ---
$router->get('/api/admin/raporlar', [ReportController::class, 'olustur'], [$auth, [PermissionMiddleware::class, 'rapor_olustur']]);
// ...


$router->dispatch();
