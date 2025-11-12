<?php
namespace ProSiparis\Auth\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PDO;

abstract class TestCase extends BaseTestCase
{
    protected ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Testler için in-memory SQLite veritabanı kullan
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Veritabanı şemasını bu in-memory veritabanına uygula
        $schema = file_get_contents(__DIR__ . '/../schema_auth.sql');
        $this->pdo->exec($schema);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        parent::tearDown();
    }
}
