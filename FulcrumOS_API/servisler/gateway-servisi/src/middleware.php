<?php
// Gateway-Servisi - src/middleware.php

/**
 * Kimlik doğrulama middleware'i.
 * Gelen istekleri kontrol eder, token'ı doğrular ve kullanıcı bilgilerini $_SERVER'a ekler.
 *
 * @param string $route Mevcut rota
 */
function authMiddleware(string $route): void
{
    // Halka açık rotalar, kimlik doğrulaması gerektirmez.
    $publicRoutes = [
        '/api/kullanici/giris',
        '/api/kullanici/kayit'
    ];

    if (in_array($route, $publicRoutes)) {
        return; // Doğrulama yapmadan devam et.
    }

    // Authorization başlığını al
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

    if (!$authHeader || !preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['basarili' => false, 'mesaj' => 'Yetkilendirme token\'ı bulunamadı veya formatı yanlış.']);
        exit;
    }

    $token = $matches[1];

    // Auth-Servisi'ne dahili doğrulama isteği gönder
    // Bu, cURL kullanarak gerçek bir servisler arası HTTP isteğini simüle eder.
    $ch = curl_init();

    // Not: Bu ortamda servisler aynı sunucuda olduğu için localhost kullanıyoruz.
    // Gerçek bir ortamda bu, servisin internal adresi (örn: http://auth-servisi) olurdu.
    curl_setopt($ch, CURLOPT_URL, "http://localhost/ProSiparis_API/servisler/auth-servisi/public/index.php/internal/auth/dogrula");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code(401);
        // Auth-Servisi'nden gelen hata mesajını istemciye iletmek daha bilgilendirici olabilir.
        $errorResponse = json_decode($response, true);
        $message = $errorResponse['mesaj'] ?? 'Geçersiz veya süresi dolmuş token.';
        echo json_encode(['basarili' => false, 'mesaj' => $message]);
        exit;
    }

    // Başarılı doğrulama. Kullanıcı bilgilerini $_SERVER'a ekle.
    $responseData = json_decode($response, true);
    $kullaniciVerisi = $responseData['veri'] ?? [];

    if (isset($kullaniciVerisi['kullanici_id'])) {
        $_SERVER['HTTP_X_USER_ID'] = $kullaniciVerisi['kullanici_id'];
    }
    if (isset($kullaniciVerisi['rol'])) {
        $_SERVER['HTTP_X_ROLE'] = $kullaniciVerisi['rol'];
    }
    if (isset($kullaniciVerisi['yetkiler'])) {
        // Yetkileri virgülle ayrılmış bir string olarak ekle
        $_SERVER['HTTP_X_PERMISSIONS'] = implode(',', $kullaniciVerisi['yetkiler']);
    }
    if (isset($kullaniciVerisi['fiyat_listesi_id'])) {
        $_SERVER['HTTP_X_PRICELIST_ID'] = $kullaniciVerisi['fiyat_listesi_id'];
    }
}
