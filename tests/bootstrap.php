<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';

$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    $env = parse_ini_file($envFile);
    if (is_array($env)) {
        foreach (['DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD', 'DB_NAME'] as $key) {
            if (isset($env[$key]) && getenv($key) === false) {
                putenv($key . '=' . $env[$key]);
                $_ENV[$key] = $env[$key];
            }
        }
    }
}
