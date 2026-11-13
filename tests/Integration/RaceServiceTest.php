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

    protected function setUp(): void
    {
        Connection::reset();
        $this->pdo = Connection::get([
            'host' => getenv('DB_HOST') ?: 'localhost',
            'dbname' => getenv('DB_NAME') ?: 'horse_racing_test',
            'user' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASSWORD') !== false ? (string) getenv('DB_PASSWORD') : '',
        ]);

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['results', 'races', 'horses', 'jockeys', 'owners', 'users', 'phinxlog'] as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        // Применяем миграции через phinx в тестовое окружение
        $cmd = sprintf(
            'cd %s && vendor/bin/phinx migrate -e testing 2>&1',
            escapeshellarg(dirname(__DIR__, 2))
        );
        // phinx path relative
        $root = dirname(__DIR__, 2);
        $output = [];
        $code = 0;
        exec("cd {$root} && APP_ENV=testing DB_NAME=horse_racing_test vendor/bin/phinx migrate -e testing 2>&1", $output, $code);
        if ($code !== 0) {
            $this->fail("Миграции не применились: " . implode("\n", $output));
        }

        Connection::reset();
        $this->pdo = Connection::get([
            'host' => 'localhost',
            'dbname' => 'horse_racing_test',
            'user' => 'root',
            'password' => '',
        ]);
        $this->service = new RaceService($this->pdo);
    }

    protected function tearDown(): void
    {
        Connection::reset();
    }

    public function testCreateAndListRaceWithDistance(): void
    {
        $result = $this->service->create([
            'name' => 'Кубок Президента',
            'race_date' => '2026-05-20',
            'location' => 'Центральный ипподром',
            'status' => 'planned',
            'distance_meters' => 1600,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('id', $result);

        $list = $this->service->listAll();
        $this->assertCount(1, $list);
        $this->assertSame('Кубок Президента', $list[0]['name']);
        $this->assertSame(1600, (int) $list[0]['distance_meters']);
    }

    public function testCreateRejectsInvalidDistance(): void
    {
        $result = $this->service->create([
            'name' => 'Bad',
            'race_date' => '2026-01-01',
            'location' => 'A',
            'distance_meters' => 10,
        ]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Дистанция', $result['message'] ?? '');
    }

    public function testUpdateAndDelete(): void
    {
        $created = $this->service->create([
            'name' => 'Old Name',
            'race_date' => '2026-03-01',
            'location' => 'Track',
            'distance_meters' => 1200,
        ]);
        $id = (int) $created['id'];

        $updated = $this->service->update($id, [
            'name' => 'New Name',
            'race_date' => '2026-03-02',
            'location' => 'New Track',
            'status' => 'finished',
            'distance_meters' => 2000,
        ]);
        $this->assertTrue($updated['success']);

        $found = $this->service->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('New Name', $found['name']);
        $this->assertSame(2000, (int) $found['distance_meters']);
        $this->assertSame('finished', $found['status']);

        $deleted = $this->service->delete($id);
        $this->assertTrue($deleted['success']);
        $this->assertNull($this->service->findById($id));
    }

    public function testDeleteNonexistent(): void
    {
        $result = $this->service->delete(99999);
        $this->assertFalse($result['success']);
    }

    public function testMigrationPreservesDataOnUpgrade(): void
    {
        // Имитация: данные уже есть после миграций
        $this->service->create([
            'name' => 'Existing Race',
            'race_date' => '2025-12-01',
            'location' => 'Old Track',
            'distance_meters' => 1800,
        ]);

        $before = $this->service->listAll();
        $this->assertCount(1, $before);

        // Повторный migrate не должен ломать данные
        $root = dirname(__DIR__, 2);
        exec("cd {$root} && DB_NAME=horse_racing_test vendor/bin/phinx migrate -e testing 2>&1", $out, $code);
        $this->assertSame(0, $code);

        Connection::reset();
        $this->pdo = Connection::get([
            'host' => 'localhost',
            'dbname' => 'horse_racing_test',
            'user' => 'root',
            'password' => '',
        ]);
        $this->service = new RaceService($this->pdo);

        $after = $this->service->listAll();
        $this->assertCount(1, $after);
        $this->assertSame('Existing Race', $after[0]['name']);
        $this->assertSame(1800, (int) $after[0]['distance_meters']);
    }
}
