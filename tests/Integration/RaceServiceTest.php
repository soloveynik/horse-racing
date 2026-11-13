<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database\Connection;
use App\Service\RaceService;
use PDO;
use PHPUnit\Framework\TestCase;

final class RaceServiceTest extends TestCase
{
    private PDO $pdo;
    private RaceService $service;

    /** @return array{host: string, port: int, dbname: string, user: string, password: string} */
    private function dbConfig(): array
    {
        return [
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('DB_PORT') ?: 3306),
            'dbname' => getenv('DB_NAME') ?: 'horse_racing_test',
            'user' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASSWORD') !== false ? (string) getenv('DB_PASSWORD') : 'root',
        ];
    }

    protected function setUp(): void
    {
        Connection::reset();
        $cfg = $this->dbConfig();
        $root = dirname(__DIR__, 2);

        $this->pdo = Connection::get($cfg);
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['results', 'races', 'horses', 'jockeys', 'owners', 'users', 'phinxlog'] as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        $cmd = sprintf(
            'cd %s && DB_HOST=%s DB_NAME=horse_racing_test DB_USER=%s DB_PASSWORD=%s vendor/bin/phinx migrate -e testing 2>&1',
            escapeshellarg($root),
            escapeshellarg($cfg['host']),
            escapeshellarg($cfg['user']),
            escapeshellarg($cfg['password'])
        );
        exec($cmd, $output, $code);
        if ($code !== 0) {
            $this->fail('Migrations failed: ' . implode("\n", $output));
        }

        Connection::reset();
        $this->pdo = Connection::get($cfg);
        $this->service = new RaceService($this->pdo);
    }

    protected function tearDown(): void
    {
        Connection::reset();
    }

    public function testCreateAndListRaceWithDistance(): void
    {
        $result = $this->service->create([
            'name' => 'Кубок',
            'race_date' => '2026-05-20',
            'location' => 'Москва',
            'status' => 'planned',
            'distance_meters' => 1600,
        ]);
        $this->assertTrue($result['success']);
        $list = $this->service->listAll();
        $this->assertCount(1, $list);
        $this->assertSame(1600, (int) $list[0]['distance_meters']);
    }

    public function testCreateRejectsInvalidData(): void
    {
        $result = $this->service->create([
            'name' => '',
            'race_date' => 'bad',
            'location' => '',
        ]);
        $this->assertFalse($result['success']);
    }

    public function testUpdateAndDelete(): void
    {
        $created = $this->service->create([
            'name' => 'Test',
            'race_date' => '2026-06-01',
            'location' => 'SPB',
            'distance_meters' => 1200,
        ]);
        $id = (int) $created['id'];
        $updated = $this->service->update($id, [
            'name' => 'Test2',
            'race_date' => '2026-06-02',
            'location' => 'Kazan',
            'status' => 'finished',
            'distance_meters' => 2400,
        ]);
        $this->assertTrue($updated['success']);
        $found = $this->service->findById($id);
        $this->assertSame(2400, (int) $found['distance_meters']);
        $this->assertTrue($this->service->delete($id)['success']);
        $this->assertNull($this->service->findById($id));
    }

    public function testDeleteNonexistent(): void
    {
        $this->assertFalse($this->service->delete(999999)['success']);
    }

    public function testMigrationPreservesDataOnUpgrade(): void
    {
        $this->service->create([
            'name' => 'Existing Race',
            'race_date' => '2025-12-01',
            'location' => 'Track',
            'distance_meters' => 1800,
        ]);
        $this->assertCount(1, $this->service->listAll());

        $root = dirname(__DIR__, 2);
        $cfg = $this->dbConfig();
        $cmd = sprintf(
            'cd %s && DB_HOST=%s DB_NAME=horse_racing_test DB_USER=%s DB_PASSWORD=%s vendor/bin/phinx migrate -e testing 2>&1',
            escapeshellarg($root),
            escapeshellarg($cfg['host']),
            escapeshellarg($cfg['user']),
            escapeshellarg($cfg['password'])
        );
        exec($cmd, $out, $code);
        $this->assertSame(0, $code);

        Connection::reset();
        $this->service = new RaceService(Connection::get($cfg));
        $after = $this->service->listAll();
        $this->assertCount(1, $after);
        $this->assertSame(1800, (int) $after[0]['distance_meters']);
    }
}
