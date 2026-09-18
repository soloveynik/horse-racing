<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {

    if ($method === 'GET') {

        $stmt = $pdo->query("
            SELECT
                results.id,
                results.race_id,
                races.name AS race_name,
                results.horse_id,
                horses.name AS horse_name,
                results.jockey_id,
                jockeys.name AS jockey_name,
                results.place,
                results.finish_time,
                results.created_at
            FROM results
            INNER JOIN races
                ON results.race_id = races.id
            INNER JOIN horses
                ON results.horse_id = horses.id
            INNER JOIN jockeys
                ON results.jockey_id = jockeys.id
            ORDER BY
                results.race_id,
                results.place
        ");

        $results = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $results
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }


    if ($method === 'POST') {

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $raceId = $data['race_id'] ?? null;
        $horseId = $data['horse_id'] ?? null;
        $jockeyId = $data['jockey_id'] ?? null;
        $place = $data['place'] ?? null;
        $finishTime = trim($data['finish_time'] ?? '');


        if (!$raceId || !$horseId || !$jockeyId || !$place) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Необходимо указать скачку, лошадь, жокея и место'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        if (!is_numeric($place) || $place < 1) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Место должно быть положительным числом'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $stmt = $pdo->prepare("
            SELECT id
            FROM races
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $raceId
        ]);

        if (!$stmt->fetch()) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Указанная скачка не найдена'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $stmt = $pdo->prepare("
            SELECT id
            FROM horses
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $horseId
        ]);

        if (!$stmt->fetch()) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Указанная лошадь не найдена'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $stmt = $pdo->prepare("
            SELECT id
            FROM jockeys
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $jockeyId
        ]);

        if (!$stmt->fetch()) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Указанный жокей не найден'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        /*
         * Правило предметной области:
         * одна лошадь не может иметь два результата
         * в одной и той же скачке.
         */

        $stmt = $pdo->prepare("
            SELECT id
            FROM results
            WHERE race_id = :race_id
              AND horse_id = :horse_id
        ");

        $stmt->execute([
            'race_id' => $raceId,
            'horse_id' => $horseId
        ]);

        if ($stmt->fetch()) {

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'Эта лошадь уже участвует в данной скачке'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        /*
         * В одной скачке одно место может принадлежать
         * только одному результату.
         */

        $stmt = $pdo->prepare("
            SELECT id
            FROM results
            WHERE race_id = :race_id
              AND place = :place
        ");

        $stmt->execute([
            'race_id' => $raceId,
            'place' => $place
        ]);

        if ($stmt->fetch()) {

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'Это место уже занято в данной скачке'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $stmt = $pdo->prepare("
            INSERT INTO results
                (
                    race_id,
                    horse_id,
                    jockey_id,
                    place,
                    finish_time
                )
            VALUES
                (
                    :race_id,
                    :horse_id,
                    :jockey_id,
                    :place,
                    :finish_time
                )
        ");

        $stmt->execute([
            'race_id' => $raceId,
            'horse_id' => $horseId,
            'jockey_id' => $jockeyId,
            'place' => $place,
            'finish_time' => $finishTime !== ''
                ? $finishTime
                : null
        ]);


        http_response_code(201);

        echo json_encode([
            'success' => true,
            'message' => 'Результат успешно добавлен',
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
                'message' => 'Не указан ID результата'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $place = $data['place'] ?? null;
        $finishTime = trim($data['finish_time'] ?? '');


        if (!$place || !is_numeric($place) || $place < 1) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Место должно быть положительным числом'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $stmt = $pdo->prepare("
            SELECT
                id,
                race_id,
                horse_id
            FROM results
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $id
        ]);

        $result = $stmt->fetch();

        if (!$result) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Результат не найден'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $stmt = $pdo->prepare("
            SELECT id
            FROM results
            WHERE race_id = :race_id
              AND place = :place
              AND id != :id
        ");

        $stmt->execute([
            'race_id' => $result['race_id'],
            'place' => $place,
            'id' => $id
        ]);

        if ($stmt->fetch()) {

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'Это место уже занято в данной скачке'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $stmt = $pdo->prepare("
            UPDATE results
            SET
                place = :place,
                finish_time = :finish_time
            WHERE id = :id
        ");

        $stmt->execute([
            'place' => $place,
            'finish_time' => $finishTime !== ''
                ? $finishTime
                : null,
            'id' => $id
        ]);


        echo json_encode([
            'success' => true,
            'message' => 'Результат обновлён'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    if ($method === 'DELETE') {

        $id = $_GET['id'] ?? null;

        if (!$id) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Не указан ID результата'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $stmt = $pdo->prepare("
            SELECT id
            FROM results
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $id
        ]);


        if (!$stmt->fetch()) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Результат не найден'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $stmt = $pdo->prepare("
            DELETE FROM results
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $id
        ]);


        echo json_encode([
            'success' => true,
            'message' => 'Результат удалён'
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