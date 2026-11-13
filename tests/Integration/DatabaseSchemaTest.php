<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

final class DatabaseSchemaTest extends TestCase
{
    private PDO $pdo;

    /** @return array{host: string, port: int, dbname: string, user: string, password: string} */
    private function dbConfig(): array
    {
        return [
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('DB_PORT') ?: 3306),
            'dbname' => 'horse_racing_test',
            'user' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASSWORD') !== false ? (string) getenv('DB_PASSWORD') : 'root',
        ];
    }

    protected function setUp(): void
    {
        Connection::reset();
        $root = dirname(__DIR__, 2);
        $cfg = $this->dbConfig();

        $dsn = sprintf('mysql:host=%s;port=%d', $cfg['host'], $cfg['port']);
        $admin = new PDO($dsn, $cfg['user'], $cfg['password']);
        $admin->exec('DROP DATABASE IF EXISTS horse_racing_test');
        $admin->exec('CREATE DATABASE horse_racing_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $cmd = sprintf(
            'cd %s && DB_HOST=%s DB_NAME=horse_racing_test DB_USER=%s DB_PASSWORD=%s vendor/bin/phinx migrate -e testing 2>&1',
            escapeshellarg($root),
            escapeshellarg($cfg['host']),
            escapeshellarg($cfg['user']),
            escapeshellarg($cfg['password'])
        );
        exec($cmd, $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));

        $this->pdo = Connection::get($cfg);
    }

    protected function tearDown(): void
    {
        Connection::reset();
    }

    public function testAllTablesExist(): void
    {
        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach (['users', 'owners', 'jockeys', 'horses', 'races', 'results', 'phinxlog'] as $table) {
            $this->assertContains($table, $tables, "Missing table {$table}");
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
        $this->assertGreaterThanOrEqual(3, count($fks));
    }

    public function testCleanDatabaseCanBeCreatedAndMigrated(): void
    {
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM races')->fetchColumn();
        $this->assertSame(0, $count);
    }
}
