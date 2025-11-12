<?php
namespace ProSiparis\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    /**
     * Singleton PDO bağlantısı döndürür.
     *
     * @param string $dbName Bağlanılacak veritabanı adı.
     * @return PDO
     */
    public static function getConnection(string $dbName = 'prosiparis_core'): PDO
    {
        // Şimdilik, her servis kendi veritabanına bağlanabilir diye dbName parametresi ekliyorum.
        // Ama tüm servisler core veritabanını kullanıyorsa, varsayılan değer yeterlidir.
        if (self::$pdo === null) {
            // Güvenlik ve esneklik için veritabanı bilgilerini ortam değişkenlerinden al.
            $host = getenv('DB_HOST') ?: 'db';
            $user = getenv('DB_USER') ?: 'user';
            $pass = getenv('DB_PASS') ?: 'password';
            $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$dbName;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                throw new \PDOException($e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$pdo;
    }
}
