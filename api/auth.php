<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {

    if ($method === 'POST' && $action === 'register') {

        $data = json_decode(file_get_contents('php://input'), true);

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($username === '' || $email === '' || $password === '') {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Заполните все поля'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        if (strlen($password) < 6) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Пароль должен содержать минимум 6 символов'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM users WHERE username = :username OR email = :email'
        );

        $stmt->execute([
            'username' => $username,
            'email' => $email
        ]);

        if ($stmt->fetch()) {
            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'Пользователь с таким именем или email уже существует'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash)
             VALUES (:username, :email, :password_hash)'
        );

        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash
        ]);

        http_response_code(201);

        echo json_encode([
            'success' => true,
            'message' => 'Регистрация выполнена успешно'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($method === 'POST' && $action === 'login') {

        $data = json_decode(file_get_contents('php://input'), true);

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Введите email и пароль'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id, username, email, password_hash
             FROM users
             WHERE email = :email'
        );

        $stmt->execute([
            'email' => $email
        ]);

        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Неверный email или пароль'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];

        echo json_encode([
            'success' => true,
            'message' => 'Вход выполнен успешно',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($method === 'GET' && $action === 'me') {

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Пользователь не авторизован'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email']
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($method === 'POST' && $action === 'logout') {

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        echo json_encode([
            'success' => true,
            'message' => 'Выход выполнен'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'Действие не найдено'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных'
    ], JSON_UNESCAPED_UNICODE);
}