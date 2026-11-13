<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    private static ?PDO $instance = null;

    /** @param array<string, mixed> $config */
    public static function get(array $config): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $port = isset($config['port']) ? (int) $config['port'] : 3306;
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $port,
            $config['dbname']
        );

        try {
            $pdo = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('DB connection error: ' . $e->getMessage(), 0, $e);
        }

        self::$instance = $pdo;

        return $pdo;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
