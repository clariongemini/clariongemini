<?php
// API Gateway - public/index.php v4.0 (Düzeltilmiş Yönlendirme)

$requestUri = $_SERVER['REQUEST_URI'];
$route = strtok($requestUri, '?');

// Yönlendirme mantığı, en spesifik rotadan en genele doğru olmalıdır.

// Depo bazlı rotalar (en spesifik olanlar)
if (preg_match('/^\/api\/depo\/(\d+)\/teslimat-al/', $route)) {
    require __DIR__ . '/../servisler/tedarik-servisi/public/index.php';
    exit;
}
if (preg_match('/^\/api\/depo\/(\d+)\/iade-teslim-al/', $route)) {
    require __DIR__ . '/../servisler/iade-servisi/public/index.php';
    exit;
}
if (preg_match('/^\/api\/depo\/(\d+)\/hazirlanacak-siparisler/', $route) || preg_match('/^\/api\/depo\/(\d+)\/siparis\/(\d+)\/kargoya-ver/', $route)) {
    require __DIR__ . '/../servisler/siparis-servisi/public/index.php';
    exit;
}

// Diğer servis rotaları
$servisHaritasi = [
    '/api/admin/raporlar' => 'raporlama-servisi',
    '/api/admin/dashboard/kpi-ozet' => 'raporlama-servisi',
    '/api/organizasyon/' => 'organizasyon-servisi',
    '/api/admin/tedarikciler' => 'tedarik-servisi',
    '/api/admin/satin-alma-siparisleri' => 'tedarik-servisi',
    '/api/admin/iade-talepleri' => 'iade-servisi',
    '/api/kullanici/iade-talepleri' => 'iade-servisi',
    '/api/kullanici/iade-talebi-olustur' => 'iade-servisi',
    '/api/kullanici/siparisler' => 'siparis-servisi',
    '/api/kargo-secenekleri' => 'siparis-servisi',
    '/api/kullanici/adresler' => 'siparis-servisi',
    '/api/odeme' => 'siparis-servisi',
    '/api/urunler' => 'katalog-servisi',
    '/api/kategoriler' => 'katalog-servisi',
    '/api/kullanici/giris' => 'auth-servisi',
    '/api/kullanici/kayit' => 'auth-servisi'
];

foreach ($servisHaritasi as $prefix => $servisAdi) {
    if (strpos($route, $prefix) === 0) {
        $servisYolu = __DIR__ . '/../servisler/' . $servisAdi . '/public/index.php';
        if (file_exists($servisYolu)) {
            require $servisYolu;
            exit;
        }
    }
}

// Kalan tüm istekler Ana Monolith'e (Legacy Core) gider.
require __DIR__ . '/index.legacy.php';
