<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    private static ?PDO $instance = null;

    public static function get(array $config): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['dbname']
        );

        try {
            $pdo = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Ошибка подключения к базе данных: ' . $e->getMessage(), 0, $e);
        }

        self::$instance = $pdo;

        return $pdo;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public static function fromEnv(string $envFile): PDO
    {
        if (!is_file($envFile)) {
            throw new RuntimeException('Файл окружения не найден: ' . $envFile);
        }

        $env = parse_ini_file($envFile);
        if ($env === false) {
            throw new RuntimeException('Не удалось прочитать файл окружения');
        }

        return self::get([
            'host' => $env['DB_HOST'] ?? 'localhost',
            'dbname' => $env['DB_NAME'] ?? 'horse_racing',
            'user' => $env['DB_USER'] ?? 'root',
            'password' => $env['DB_PASSWORD'] ?? '',
        ]);
    }
}
