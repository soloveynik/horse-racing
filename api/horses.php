<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {

    if ($method === 'GET') {

        $stmt = $pdo->query("
            SELECT
                horses.id,
                horses.name,
                horses.breed,
                horses.birth_year,
                owners.name AS owner_name
            FROM horses
            INNER JOIN owners ON horses.owner_id = owners.id
            ORDER BY horses.id
        ");

        $horses = $stmt->fetchAll();

        echo json_encode(
            [
                'success' => true,
                'data' => $horses
            ],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        exit;
    }

    if ($method === 'POST') {

        $data = json_decode(file_get_contents('php://input'), true);

        if (
            !isset($data['name']) ||
            !isset($data['owner_id'])
        ) {
            http_response_code(400);

            echo json_encode(
                [
                    'success' => false,
                    'message' => 'Необходимо указать имя лошади и владельца'
                ],
                JSON_UNESCAPED_UNICODE
            );

            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO horses
            (name, breed, birth_year, owner_id)
            VALUES
            (:name, :breed, :birth_year, :owner_id)
        ");

        $stmt->execute([
            'name' => $data['name'],
            'breed' => $data['breed'] ?? null,
            'birth_year' => $data['birth_year'] ?? null,
            'owner_id' => $data['owner_id']
        ]);

        http_response_code(201);

        echo json_encode(
            [
                'success' => true,
                'message' => 'Лошадь успешно добавлена',
                'id' => $pdo->lastInsertId()
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }
    if ($method === 'PUT') {

        $id = $_GET['id'] ?? null;

        if (!$id) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Не указан ID лошади'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (
            !isset($data['name']) ||
            !isset($data['owner_id'])
        ) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Необходимо указать имя лошади и владельца'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM horses WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id
        ]);

        if (!$stmt->fetch()) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Лошадь не найдена'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE horses
             SET name = :name,
                 breed = :breed,
                 birth_year = :birth_year,
                 owner_id = :owner_id
             WHERE id = :id'
        );

        $stmt->execute([
            'name' => $data['name'],
            'breed' => $data['breed'] ?? null,
            'birth_year' => $data['birth_year'] ?? null,
            'owner_id' => $data['owner_id'],
            'id' => $id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Данные лошади обновлены'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    if ($method === 'DELETE') {

        $id = $_GET['id'] ?? null;

        if (!$id) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Не указан ID лошади'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM horses WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id
        ]);

        if (!$stmt->fetch()) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Лошадь не найдена'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'DELETE FROM horses WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Лошадь удалена'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
    http_response_code(405);

    echo json_encode(
        [
            'success' => false,
            'message' => 'Метод не поддерживается'
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'message' => 'Ошибка базы данных'
        ],
        JSON_UNESCAPED_UNICODE
    );
}