<?php

$envFile = __DIR__ . '/.env';
$env = is_file($envFile) ? parse_ini_file($envFile) : [];

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => $env['DB_HOST'] ?? '127.0.0.1',
            'name' => $env['DB_NAME'] ?? 'horse_racing',
            'user' => $env['DB_USER'] ?? 'root',
            'pass' => $env['DB_PASSWORD'] ?? 'root',
            'port' => (int) ($env['DB_PORT'] ?? 3306),
            'charset' => 'utf8mb4',
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => getenv('DB_HOST') ?: ($env['DB_HOST'] ?? '127.0.0.1'),
            'name' => getenv('DB_NAME') ?: 'horse_racing_test',
            'user' => getenv('DB_USER') ?: ($env['DB_USER'] ?? 'root'),
            'pass' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ($env['DB_PASSWORD'] ?? 'root'),
            'port' => (int) (getenv('DB_PORT') ?: ($env['DB_PORT'] ?? 3306)),
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
