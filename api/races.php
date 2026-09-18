<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

function normalizeText(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', trim($text));

    return $text;
}

function getWordStem(string $word): string
{
    $word = normalizeText($word);

    if (mb_strlen($word, 'UTF-8') <= 3) {
        return $word;
    }

    $endings = [
        'ами',
        'ями',
        'ого',
        'ему',
        'ому',
        'ыми',
        'ими',
        'ее',
        'ие',
        'ые',
        'ое',
        'ей',
        'ой',
        'ий',
        'ый',
        'ой',
        'ем',
        'им',
        'ым',
        'ом',
        'ах',
        'ях',
        'ов',
        'ев',
        'ам',
        'ям',
        'а',
        'я',
        'ы',
        'и',
        'у',
        'ю',
        'е',
        'о',
        'ь'
    ];

    foreach ($endings as $ending) {
        $endingLength = mb_strlen($ending, 'UTF-8');
        $wordLength = mb_strlen($word, 'UTF-8');

        if (
            $wordLength > $endingLength + 2 &&
            mb_substr($word, -$endingLength, null, 'UTF-8') === $ending
        ) {
            return mb_substr(
                $word,
                0,
                $wordLength - $endingLength,
                'UTF-8'
            );
        }
    }

    return $word;
}

function raceMatchesSearch(array $race, string $search): bool
{
    $search = normalizeText($search);

    if ($search === '') {
        return true;
    }

    $searchWords = preg_split('/\s+/u', $search);

    $raceText = normalizeText(
        ($race['name'] ?? '') . ' ' .
        ($race['location'] ?? '') . ' ' .
        ($race['status'] ?? '')
    );

    $raceWords = preg_split('/\s+/u', $raceText);

    foreach ($searchWords as $searchWord) {

        if ($searchWord === '') {
            continue;
        }

        $stem = getWordStem($searchWord);

        $found = false;

        foreach ($raceWords as $raceWord) {

            $raceStem = getWordStem($raceWord);

            if (
                mb_strpos($raceWord, $searchWord, 0, 'UTF-8') !== false ||
                mb_strpos($raceWord, $stem, 0, 'UTF-8') !== false ||
                mb_strpos($raceStem, $stem, 0, 'UTF-8') !== false
            ) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return false;
        }
    }

    return true;
}

try {

    if ($method === 'GET') {

        $stmt = $pdo->query("
            SELECT
                id,
                name,
                race_date,
                location,
                status,
                created_at
            FROM races
            ORDER BY race_date DESC, id DESC
        ");

        $races = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $search = trim($_GET['search'] ?? '');

        if ($search !== '') {

            $races = array_values(
                array_filter(
                    $races,
                    function ($race) use ($search) {
                        return raceMatchesSearch($race, $search);
                    }
                )
            );
        }

        echo json_encode([
            'success' => true,
            'data' => $races
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }


    if ($method === 'POST') {

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $name = trim($data['name'] ?? '');
        $raceDate = trim($data['race_date'] ?? '');
        $location = trim($data['location'] ?? '');
        $status = trim($data['status'] ?? 'Запланирована');

        if ($name === '') {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Необходимо указать название скачки'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        if ($raceDate === '') {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Необходимо указать дату скачки'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $allowedStatuses = [
            'Запланирована',
            'Проводится',
            'Завершена'
        ];

        if (!in_array($status, $allowedStatuses, true)) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Недопустимый статус скачки'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO races
                (name, race_date, location, status)
            VALUES
                (:name, :race_date, :location, :status)
        ");

        $stmt->execute([
            'name' => $name,
            'race_date' => $raceDate,
            'location' => $location,
            'status' => $status
        ]);

        http_response_code(201);

        echo json_encode([
            'success' => true,
            'message' => 'Скачка успешно добавлена',
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
                'message' => 'Не указан ID скачки'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $name = trim($data['name'] ?? '');
        $raceDate = trim($data['race_date'] ?? '');
        $location = trim($data['location'] ?? '');
        $status = trim($data['status'] ?? '');

        if ($name === '' || $raceDate === '') {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Название и дата обязательны'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $allowedStatuses = [
            'Запланирована',
            'Проводится',
            'Завершена'
        ];

        if (!in_array($status, $allowedStatuses, true)) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Недопустимый статус скачки'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM races WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id
        ]);

        if (!$stmt->fetch()) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Скачка не найдена'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE races
            SET
                name = :name,
                race_date = :race_date,
                location = :location,
                status = :status
            WHERE id = :id
        ");

        $stmt->execute([
            'name' => $name,
            'race_date' => $raceDate,
            'location' => $location,
            'status' => $status,
            'id' => $id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Данные скачки обновлены'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    if ($method === 'DELETE') {

        $id = $_GET['id'] ?? null;

        if (!$id) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Не указан ID скачки'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM races WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id
        ]);

        if (!$stmt->fetch()) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Скачка не найдена'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'DELETE FROM races WHERE id = :id'
        );

        try {

            $stmt->execute([
                'id' => $id
            ]);

        } catch (PDOException $e) {

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'Нельзя удалить скачку, у которой есть результаты'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Скачка удалена'
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