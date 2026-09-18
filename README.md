# Horse Racing

Веб-приложение для учета информации о скачках.

## Назначение

Система предназначена для хранения и обработки информации о:

- скачках;
- лошадях;
- владельцах;
- жокеях;
- результатах скачек.

Приложение предоставляет веб-интерфейс и HTTP API для работы с данными.

## Пользователи

### Администратор

Администратор может:

- авторизоваться в системе;
- просматривать данные;
- добавлять записи;
- работать с лошадьми;
- работать с владельцами;
- работать с жокеями;
- создавать скачки;
- добавлять результаты скачек.

## Технологии

- PHP 8.2
- MySQL
- HTML5
- CSS3
- JavaScript
- Apache
- XAMPP
- Git
- GitHub

## Структура проекта

```text
horse-racing/
├── api/
│   ├── auth.php
│   ├── health.php
│   ├── horses.php
│   ├── jockeys.php
│   ├── owners.php
│   ├── races.php
│   └── results.php
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── public/
│   ├── css/
│   │   └── style.css
│   ├── index.php
│   ├── login.html
│   ├── register.html
│   ├── horses.html
│   ├── jockeys.html
│   ├── owners.html
│   ├── races.html
│   ├── results.html
│   └── test-api.html
├── .env.example
├── .gitignore
├── index.php
└── README.md
## Запуск