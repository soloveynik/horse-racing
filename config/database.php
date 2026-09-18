<?php

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    die('Файл .env не найден');
}

$env = parse_ini_file($envFile);

$host = $env['DB_HOST'] ?? 'localhost';
$db = $env['DB_NAME'] ?? 'horse_racing';
$user = $env['DB_USER'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? '';

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