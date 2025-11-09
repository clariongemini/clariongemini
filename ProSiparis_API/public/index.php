<?php
// API Gateway - public/index.php v3.2 (Birleştirilmiş Yapı)

$requestUri = $_SERVER['REQUEST_URI'];
// Basit bir temizleme, gerçek uygulamada daha güvenli olmalı
$route = strtok($requestUri, '?');

// Rota gruplarını tanımla
$authRoutes = ['/api/kullanici/giris', '/api/kullanici/kayit'];
$katalogRoutes = ['/api/urunler', '/api/kategoriler'];
$siparisRoutes = [
    '/api/odeme', '/api/kullanici/adresler', '/api/kargo-secenekleri',
    '/api/kullanici/siparisler'
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
// Not: Rota çakışmalarını önlemek için en spesifik olanlar en başa yazılır.
if (strpos($route, '/api/depo/teslimat-al') === 0 || strpos($route, '/api/depo/beklenen-teslimatlar') === 0 || strpos($route, '/api/admin/satin-alma-siparisleri') === 0 || strpos($route, '/api/admin/tedarikciler') === 0) {
    require __DIR__ . '/../servisler/tedarik-servisi/public/index.php';
    exit;
}
if (strpos($route, '/api/depo/iade-teslim-al') === 0 || strpos($route, '/api/admin/iade-talepleri') === 0 || strpos($route, '/api/kullanici/iade-talepleri') === 0 || strpos($route, '/api/kullanici/iade-talebi-olustur') === 0) {
    require __DIR__ . '/../servisler/iade-servisi/public/index.php';
    exit;
}
if (strpos($route, '/api/kullanici/siparisler') === 0 || strpos($route, '/api/kargo-secenekleri') === 0 || strpos($route, '/api/kullanici/adresler') === 0 || strpos($route, '/api/odeme') === 0) {
    require __DIR__ . '/../servisler/siparis-servisi/public/index.php';
    exit;
}
if (strpos($route, '/api/urunler') === 0 || strpos($route, '/api/kategoriler') === 0) {
    require __DIR__ . '/../servisler/katalog-servisi/public/index.php';
    exit;
}
if (strpos($route, '/api/kullanici/giris') === 0 || strpos($route, '/api/kullanici/kayit') === 0) {
    require __DIR__ . '/../servisler/auth-servisi/public/index.php';
    exit;
}

// Kalan tüm istekler Ana Monolith'e (Legacy Core) gider.
require __DIR__ . '/index.legacy.php';
