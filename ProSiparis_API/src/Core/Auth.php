<?php
namespace ProSiparis\Core;

/**
 * Kimliği doğrulanmış kullanıcıyı bir istek boyunca statik olarak tutan basit bir sınıf.
 * Bu, dependency injection container olmadan veriyi Middleware'den Controller'a taşımanın
 * basit bir yoludur.
 */
class Auth
{
    private static $user = null;

    /**
     * Kimliği doğrulanmış kullanıcıyı ayarlar.
     * @param object $userData JWT'den çözülen kullanıcı verisi
     */
    public static function setUser(object $userData): void
    {
        self::$user = $userData;
    }

    /**
     * Mevcut kimliği doğrulanmış kullanıcıyı döndürür.
     * @return object|null
     */
    public static function user(): ?object
    {
        return self::$user;
    }

    /**
     * Bir kullanıcının kimliğinin doğrulanıp doğrulanmadığını kontrol eder.
     * @return bool
     */
    public static function check(): bool
    {
        return self::$user !== null;
    }

    /**
     * Mevcut kullanıcının ID'sini döndürür.
     * @return int|null
     */
    public static function id(): ?int
    {
        return self::$user ? self::$user->kullanici_id : null;
    }
}
