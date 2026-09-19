<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo->query('SELECT 1');

    echo json_encode([
        'success' => true,
        'status' => 'ok',
        'database' => 'connected'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(503);

    echo json_encode([
        'success' => false,
        'status' => 'error',
        'database' => 'unavailable'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}