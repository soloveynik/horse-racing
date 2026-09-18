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
                experience_years,
                created_at
            FROM jockeys
            ORDER BY id
        ");

        $jockeys = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $jockeys
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }


    if ($method === 'POST') {

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $name = trim($data['name'] ?? '');
        $experienceYears = $data['experience_years'] ?? null;

        if ($name === '') {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Необходимо указать имя жокея'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        if (
            $experienceYears !== null &&
            (
                !is_numeric($experienceYears) ||
                $experienceYears < 0
            )
        ) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Стаж должен быть неотрицательным числом'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO jockeys
                (name, experience_years)
            VALUES
                (:name, :experience_years)
        ");

        $stmt->execute([
            'name' => $name,
            'experience_years' => $experienceYears
        ]);

        http_response_code(201);

        echo json_encode([
            'success' => true,
            'message' => 'Жокей успешно добавлен',
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
                'message' => 'Не указан ID жокея'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $name = trim($data['name'] ?? '');
        $experienceYears = $data['experience_years'] ?? null;

        if ($name === '') {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Необходимо указать имя жокея'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        if (
            $experienceYears !== null &&
            (
                !is_numeric($experienceYears) ||
                $experienceYears < 0
            )
        ) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Стаж должен быть неотрицательным числом'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM jockeys WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id
        ]);

        if (!$stmt->fetch()) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Жокей не найден'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE jockeys
            SET
                name = :name,
                experience_years = :experience_years
            WHERE id = :id
        ");

        $stmt->execute([
            'name' => $name,
            'experience_years' => $experienceYears,
            'id' => $id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Данные жокея обновлены'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    if ($method === 'DELETE') {

        $id = $_GET['id'] ?? null;

        if (!$id) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Не указан ID жокея'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM jockeys WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id
        ]);

        if (!$stmt->fetch()) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Жокей не найден'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'DELETE FROM jockeys WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Жокей удалён'
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