<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

final class DatabaseSchemaTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        Connection::reset();
        $root = dirname(__DIR__, 2);

        // Чистая БД
        $admin = new PDO('mysql:host=localhost', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS horse_racing_test');
        $admin->exec('CREATE DATABASE horse_racing_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        exec("cd {$root} && DB_NAME=horse_racing_test vendor/bin/phinx migrate -e testing 2>&1", $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));

        $this->pdo = Connection::get([
            'host' => 'localhost',
            'dbname' => 'horse_racing_test',
            'user' => 'root',
            'password' => '',
        ]);
    }

    protected function tearDown(): void
    {
        Connection::reset();
    }

    public function testAllTablesExist(): void
    {
        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $expected = ['users', 'owners', 'jockeys', 'horses', 'races', 'results', 'phinxlog'];
        foreach ($expected as $table) {
            $this->assertContains($table, $tables, "Таблица {$table} отсутствует");
        }
    }

    public function testRacesHasDistanceColumn(): void
    {
        $cols = $this->pdo->query('SHOW COLUMNS FROM races')->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('distance_meters', $cols);
    }

    public function testForeignKeysOnResults(): void
    {
        $stmt = $this->pdo->query(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = 'horse_racing_test'
               AND TABLE_NAME = 'results'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );
        $fks = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assertNotEmpty($fks);
        $this->assertGreaterThanOrEqual(3, count($fks));
    }

    public function testCleanDatabaseCanBeCreatedAndMigrated(): void
    {
        // setUp already did clean create + migrate
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM races')->fetchColumn();
        $this->assertSame(0, $count);
    }
}
