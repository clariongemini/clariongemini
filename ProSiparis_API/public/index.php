<?php
// API Gateway - public/index.php

// Basit yönlendirme (routing) mantığı
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/ProSiparis_API/public'; // Projenin alt dizinde çalıştığını varsayalım
$route = str_replace($basePath, '', $requestUri);

// Auth-Servisi Rotaları
if (strpos($route, '/api/kullanici/giris') === 0 || strpos($route, '/api/kullanici/kayit') === 0) {
    // JWT Doğrulama (bu rotalar için atlanır)
    // İsteği Auth-Servisi'ne yönlendir
    require __DIR__ . '/../../servisler/auth-servisi/public/index.php';
    exit;
}

// --- JWT Doğrulama (Merkezi) ---
// (JWT doğrulama kodu burada yer alacak)
// ...
// Geçerli bir token varsa, X-User-ID, X-Permissions gibi header'lar hazırlanır.
// $_SERVER['HTTP_X_USER_ID'] = $decodedToken->data->kullanici_id;


// Katalog-Servisi Rotaları
if (strpos($route, '/api/urunler') === 0 || strpos($route, '/api/kategoriler') === 0) {
    require __DIR__ . '/../../servisler/katalog-servisi/public/index.php';
    exit;
}

// Ana Monolith (Legacy Core) için kalan tüm istekler
// Siparişler, İadeler, Tedarikçiler vb.
require __DIR__ . '/index.legacy.php';
