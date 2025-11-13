<?php
// API Gateway - public/index.php v3.1

$requestUri = $_SERVER['REQUEST_URI'];
$route = '...' // Gelen URI'yi temizle

// Rota gruplarını tanımla
$authRoutes = ['/api/kullanici/giris', '/api/kullanici/kayit'];
$katalogRoutes = ['/api/urunler', '/api/kategoriler'];
$siparisRoutes = [
    '/api/odeme', '/api/kullanici/adresler', '/api/kargo-secenekleri',
    '/api/kullanici/siparisler', '/api/depo/'
];

// Yönlendirme mantığı
foreach ($authRoutes as $prefix) {
    if (strpos($route, $prefix) === 0) {
        require __DIR__ . '/../../servisler/auth-servisi/public/index.php';
        exit;
    }
}

foreach ($katalogRoutes as $prefix) {
    if (strpos($route, $prefix) === 0) {
        require __DIR__ . '/../../servisler/katalog-servisi/public/index.php';
        exit;
    }
}

foreach ($siparisRoutes as $prefix) {
    if (strpos($route, $prefix) === 0) {
        // JWT Doğrulamasını burada yap
        // ...
        require __DIR__ . '/../../servisler/siparis-servisi/public/index.php';
        exit;
    }
}

// Kalan tüm istekler Ana Monolith'e (Legacy Core) gider.
require __DIR__ . '/index.legacy.php';
