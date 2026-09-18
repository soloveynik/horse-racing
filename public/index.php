<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Система «Скачки»</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header class="header">

    <div class="logo">
        🐎 Скачки
    </div>

    <div class="user-info">
        Пользователь:
        <strong><?= $username ?></strong>

        <button id="logoutButton">
            Выйти
        </button>
    </div>

</header>


<main class="container">

    <h1>Информационная система «Скачки»</h1>

    <p class="welcome">
        Добро пожаловать, <?= $username ?>!
    </p>


    <div class="cards">

        <a href="horses.html" class="card">
            <h2>🐎 Лошади</h2>
            <p>Учёт лошадей и их владельцев</p>
        </a>


        <a href="owners.html" class="card">
            <h2>👤 Владельцы</h2>
            <p>Информация о владельцах</p>
        </a>


        <a href="jockeys.html" class="card">
            <h2>🏇 Жокеи</h2>
            <p>Учёт жокеев</p>
        </a>


        <a href="races.html" class="card">
            <h2>🏆 Скачки</h2>
            <p>Соревнования и события</p>
        </a>


        <a href="results.html" class="card">
            <h2>📊 Результаты</h2>
            <p>Результаты соревнований</p>
        </a>

    </div>

</main>


<script>

document
    .getElementById('logoutButton')
    .addEventListener('click', async function() {

        const response = await fetch(
            '../api/auth.php?action=logout',
            {
                method: 'POST'
            }
        );

        const result = await response.json();

        if (result.success) {
            window.location.href = 'login.html';
        }

    });

</script>

</body>

</html>