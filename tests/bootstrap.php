<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Изоляция тестовых данных: отдельная БД horse_racing_test
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
