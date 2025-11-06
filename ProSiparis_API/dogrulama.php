<?php
// dogrulama.php - Gelen isteklerdeki JWT'yi doğrulamak için yardımcı fonksiyon.

// Gerekli JWT sınıflarını dahil et.
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

/**
 * Gelen HTTP Authorization başlığındaki Bearer token'ı doğrular.
 *
 * @return object Başarılı olursa, token'ın içindeki 'data' yükünü (payload) döndürür.
 *                Başarısız olursa, bir JSON hata mesajı basar ve betiği sonlandırır.
 */
function token_dogrula()
{
    // ayarlar.php'yi dahil et (JWT_SECRET_KEY burada tanımlı).
    // Bu dosya, veritabanı bağlantısı gibi diğer ayarları da içerebilir.
    require_once __DIR__ . '/ayarlar.php';

    // HTTP Authorization başlığını al.
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

    if (!$authHeader) {
        http_response_code(401); // Yetkisiz
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Kimlik doğrulama token\'ı bulunamadı.']);
        exit;
    }

    // Başlığın "Bearer [token]" formatında olup olmadığını kontrol et.
    $parts = explode(' ', $authHeader);
    if (count($parts) !== 2 || $parts[0] !== 'Bearer') {
        http_response_code(401);
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Token formatı geçersiz. "Bearer [token]" olmalı.']);
        exit;
    }

    $jwt = $parts[1];

    try {
        // Token'ı çözmeyi ve doğrulamayı dene.
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));

        // Başarılı olursa, token içindeki kullanıcı verisini döndür.
        return $decoded->data;

    } catch (ExpiredException $e) {
        // Token'ın süresi dolmuşsa.
        http_response_code(401);
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Oturum süreniz doldu. Lütfen tekrar giriş yapın.']);
        exit;
    } catch (Exception $e) {
        // Diğer tüm hatalar (geçersiz imza, bozuk token vb.).
        http_response_code(401);
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Geçersiz token.']);
        exit;
    }
}
