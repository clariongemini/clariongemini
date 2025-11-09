<?php
// API Gateway - public/index.php v4.3 (Onarılmış ve Tamamlanmış)

$requestUri = $_SERVER['REQUEST_URI'];
$route = strtok($requestUri, '?');

// Yönlendirme mantığı, en spesifik rotadan en genele doğru olmalıdır.

// Depo bazlı WMS rotaları (en spesifik)
if (preg_match('/^\/api\/depo\/(\d+)\/teslimat-al/', $route)) {
    require __DIR__ . '/../servisler/tedarik-servisi/public/index.php'; exit;
}
if (preg_match('/^\/api\/depo\/(\d+)\/iade-teslim-al/', $route)) {
    require __DIR__ . '/../servisler/iade-servisi/public/index.php'; exit;
}
if (preg_match('/^\/api\/depo\/(\d+)\/hazirlanacak-siparisler/', $route) || preg_match('/^\/api\/depo\/(\d+)\/siparis\/(\d+)\/kargoya-ver/', $route)) {
    require __DIR__ . '/../servisler/siparis-servisi/public/index.php'; exit;
}

// Genel servis haritası
$servisHaritasi = [
    // v4.3 Servisleri
    '/api/sayfa' => 'cms-servisi',
    '/api/bannerlar' => 'cms-servisi',
    '/api/admin/sayfalar' => 'cms-servisi',
    '/api/admin/bannerlar' => 'cms-servisi',
    '/api/kullanici/destek-talepleri' => 'destek-servisi',
    '/api/admin/destek-talepleri' => 'destek-servisi',
    '/api/sepet' => 'otomasyon-servisi',
    '/api/cron/run' => 'otomasyon-servisi',

    // v4.2 Servisi
    '/api/sepet/kupon-dogrula' => 'kupon-servisi',
    '/api/admin/kuponlar' => 'kupon-servisi',

    // v4.1 Servisleri
    '/api/admin/raporlar' => 'raporlama-servisi',
    '/api/admin/dashboard/kpi-ozet' => 'raporlama-servisi',
    '/api/organizasyon/' => 'organizasyon-servisi',

    // v4.0 Servisleri (WMS'in diğer kısımları)
    '/api/admin/tedarikciler' => 'tedarik-servisi',
    '/api/admin/satin-alma-siparisleri' => 'tedarik-servisi',
    '/api/admin/iade-talepleri' => 'iade-servisi',
    '/api/kullanici/iade-talepleri' => 'iade-servisi',
    '/api/kullanici/iade-talebi-olustur' => 'iade-servisi',

    // v3.x ve v2.x'ten kalan servisler
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
        require __DIR__ . '/../servisler/' . $servisAdi . '/public/index.php';
        exit;
    }
}

// Artık monolith kalmadığı için buraya ulaşan bir istek bulunmamalıdır.
http_response_code(404);
echo json_encode(['basarili' => false, 'mesaj' => 'Endpoint bulunamadı.']);
exit;
