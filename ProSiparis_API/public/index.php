<?php
// API Gateway - public/index.php v3.1

$requestUri = $_SERVER['REQUEST_URI'];
$route = '...' // Gelen URI'yi temizle

// Rota gruplarını tanımla
$authRoutes = ['/api/kullanici/giris', '/api/kullanici/kayit'];
$katalogRoutes = ['/api/urunler', '/api/kategoriler'];
$siparisRoutes = [
    '/api/odeme', '/api/kullanici/adresler', '/api/kargo-secenekleri',
    '/api/kullanici/siparisler'
    // Not: '/api/depo/' genel bir prefix olduğu için Tedarik ve İade servislerindeki
    // daha spesifik rotalarla çakışmaması için buradan kaldırıldı veya
    // daha dikkatli yönetilmesi gerekir. Bu örnekte, spesifik rotalar önceliklidir.
];

$tedarikRoutes = [
    '/api/admin/tedarikciler',
    '/api/admin/satin-alma-siparisleri',
    '/api/depo/beklenen-teslimatlar',
    '/api/depo/teslimat-al'
];

$iadeRoutes = [
    '/api/kullanici/iade-talebi-olustur',
    '/api/kullanici/iade-talepleri',
    '/api/admin/iade-talepleri',
    '/api/depo/iade-teslim-al'
];


// Yönlendirme mantığı
// Önce daha spesifik olan yeni servis rotalarını kontrol et
foreach ($tedarikRoutes as $prefix) {
    if (strpos($route, $prefix) === 0) {
        require __DIR__ . '/../../servisler/tedarik-servisi/public/index.php';
        exit;
    }
}

foreach ($iadeRoutes as $prefix) {
    if (strpos($route, $prefix) === 0) {
        require __DIR__ . '/../../servisler/iade-servisi/public/index.php';
        exit;
    }
}


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
