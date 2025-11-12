<?php
namespace ProSiparis\Tedarik\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PDO;

abstract class TestCase extends BaseTestCase
{
    protected ?PDO $pdoTedarik = null;
    protected ?PDO $pdoEnvanter = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Testler için iki ayrı in-memory SQLite veritabanı kullan
        $this->pdoTedarik = new PDO('sqlite::memory:');
        $this->pdoTedarik->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdoEnvanter = new PDO('sqlite::memory:');
        $this->pdoEnvanter->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // İlgili şemaları veritabanlarına uygula
        $this->applySchema($this->pdoTedarik, __DIR__ . '/../schema_tedarik.sql');
        $this->applySchema($this->pdoEnvanter, __DIR__ . '/../../envanter-servisi/schema_envanter.sql');
    }

    private function applySchema(PDO $pdo, string $schemaPath): void
    {
        $schema = file_get_contents($schemaPath);
        $schema = preg_replace('/ENGINE=InnoDB.*/', '', $schema);
        $schema = preg_replace("/ON UPDATE current_timestamp\(\)/", "", $schema);
        $schema = preg_replace("/enum\([^)]+\)/", "varchar(255)", $schema);
        $pdo->exec($schema);
    }

    protected function tearDown(): void
    {
        $this->pdoTedarik = null;
        $this->pdoEnvanter = null;
        parent::tearDown();
    }
}
