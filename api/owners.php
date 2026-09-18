<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {

    if ($method === 'GET') {

        $stmt = $pdo->query("
            SELECT
                id,
                name,
                contact
            FROM owners
            ORDER BY id
        ");

        $owners = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $owners
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }


    if ($method === 'POST') {

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $name = trim($data['name'] ?? '');
        $contact = trim($data['contact'] ?? '');

        if ($name === '') {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Необходимо указать имя владельца'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO owners (name, contact)
            VALUES (:name, :contact)
        ");

        $stmt->execute([
            'name' => $name,
            'contact' => $contact
        ]);

        http_response_code(201);

        echo json_encode([
            'success' => true,
            'message' => 'Владелец успешно добавлен',
            'id' => $pdo->lastInsertId()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    if ($method === 'PUT') {

        $id = $_GET['id'] ?? null;

        if (!$id) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Не указан ID владельца'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $name = trim($data['name'] ?? '');
        $contact = trim($data['contact'] ?? '');

        if ($name === '') {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Необходимо указать имя владельца'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM owners WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id
        ]);

        if (!$stmt->fetch()) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Владелец не найден'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE owners
            SET name = :name,
                contact = :contact
            WHERE id = :id
        ");

        $stmt->execute([
            'name' => $name,
            'contact' => $contact,
            'id' => $id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Данные владельца обновлены'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    if ($method === 'DELETE') {

        $id = $_GET['id'] ?? null;

        if (!$id) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Не указан ID владельца'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM owners WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id
        ]);

        if (!$stmt->fetch()) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Владелец не найден'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'DELETE FROM owners WHERE id = :id'
        );

        try {

            $stmt->execute([
                'id' => $id
            ]);

        } catch (PDOException $e) {

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'Нельзя удалить владельца, у которого есть лошади'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Владелец удалён'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Метод не поддерживается'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных'
    ], JSON_UNESCAPED_UNICODE);
}