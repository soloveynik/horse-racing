<?php

// Сначала проверяем переменные окружения (Docker)
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

// Если переменных нет (локальный запуск), читаем .env
if ($host === false) {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $env = parse_ini_file($envFile);
        $host = $env['DB_HOST'] ?? 'localhost';
        $db = $env['DB_NAME'] ?? 'horse_racing';
        $user = $env['DB_USER'] ?? 'root';
        $password = $env['DB_PASSWORD'] ?? '';
    } else {
        die('Файл .env не найден и переменные окружения не заданы');
    }
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Ошибка подключения к базе данных');
}
