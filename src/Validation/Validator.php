<?php

declare(strict_types=1);

namespace App\Validation;

final class Validator
{
    /**
     * @return list<string>
     */
    public static function validateUserRegistration(array $data): array
    {
        $errors = [];

        $username = trim((string) ($data['username'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '') {
            $errors[] = 'Имя пользователя обязательно';
        } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
            $errors[] = 'Имя пользователя должно быть от 3 до 50 символов';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Имя пользователя может содержать только буквы, цифры и _';
        }

        if ($email === '') {
            $errors[] = 'Email обязателен';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный формат email';
        } elseif (mb_strlen($email) > 100) {
            $errors[] = 'Email слишком длинный';
        }

        if ($password === '') {
            $errors[] = 'Пароль обязателен';
        } elseif (mb_strlen($password) < 6) {
            $errors[] = 'Пароль должен содержать минимум 6 символов';
        } elseif (mb_strlen($password) > 72) {
            $errors[] = 'Пароль слишком длинный';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public static function validateHorse(array $data, bool $requireId = false): array
    {
        $errors = [];

        if ($requireId) {
            $id = $data['id'] ?? null;
            if ($id === null || $id === '' || (int) $id <= 0) {
                $errors[] = 'Некорректный ID лошади';
            }
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Имя лошади обязательно';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Имя лошади слишком длинное';
        }

        $ownerId = $data['owner_id'] ?? null;
        if ($ownerId === null || $ownerId === '' || (int) $ownerId <= 0) {
            $errors[] = 'Необходимо указать корректного владельца';
        }

        if (isset($data['birth_year']) && $data['birth_year'] !== null && $data['birth_year'] !== '') {
            $year = (int) $data['birth_year'];
            $currentYear = (int) date('Y');
            if ($year < 1980 || $year > $currentYear) {
                $errors[] = 'Год рождения должен быть между 1980 и ' . $currentYear;
            }
        }

        if (isset($data['breed']) && is_string($data['breed']) && mb_strlen($data['breed']) > 100) {
            $errors[] = 'Порода слишком длинная';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public static function validateOwner(array $data, bool $requireId = false): array
    {
        $errors = [];

        if ($requireId) {
            $id = $data['id'] ?? null;
            if ($id === null || $id === '' || (int) $id <= 0) {
                $errors[] = 'Некорректный ID владельца';
            }
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Имя владельца обязательно';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Имя владельца слишком длинное';
        }

        if (isset($data['contact']) && is_string($data['contact']) && mb_strlen($data['contact']) > 150) {
            $errors[] = 'Контакт слишком длинный';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public static function validateJockey(array $data, bool $requireId = false): array
    {
        $errors = [];

        if ($requireId) {
            $id = $data['id'] ?? null;
            if ($id === null || $id === '' || (int) $id <= 0) {
                $errors[] = 'Некорректный ID жокея';
            }
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Имя жокея обязательно';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Имя жокея слишком длинное';
        }

        if (isset($data['experience_years'])) {
            $exp = (int) $data['experience_years'];
            if ($exp < 0 || $exp > 60) {
                $errors[] = 'Опыт должен быть от 0 до 60 лет';
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public static function validateRace(array $data, bool $requireId = false): array
    {
        $errors = [];

        if ($requireId) {
            $id = $data['id'] ?? null;
            if ($id === null || $id === '' || (int) $id <= 0) {
                $errors[] = 'Некорректный ID скачки';
            }
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Название скачки обязательно';
        } elseif (mb_strlen($name) > 150) {
            $errors[] = 'Название скачки слишком длинное';
        }

        $raceDate = trim((string) ($data['race_date'] ?? ''));
        if ($raceDate === '') {
            $errors[] = 'Дата скачки обязательна';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raceDate)) {
            $errors[] = 'Дата должна быть в формате YYYY-MM-DD';
        } else {
            $parts = explode('-', $raceDate);
            if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
                $errors[] = 'Некорректная дата скачки';
            }
        }

        $location = trim((string) ($data['location'] ?? ''));
        if ($location === '') {
            $errors[] = 'Место проведения обязательно';
        } elseif (mb_strlen($location) > 150) {
            $errors[] = 'Место проведения слишком длинное';
        }

        if (isset($data['status'])) {
            $allowed = ['planned', 'ongoing', 'finished', 'cancelled'];
            if (!in_array($data['status'], $allowed, true)) {
                $errors[] = 'Недопустимый статус скачки';
            }
        }

        if (isset($data['distance_meters']) && $data['distance_meters'] !== null && $data['distance_meters'] !== '') {
            $distance = (int) $data['distance_meters'];
            if ($distance < 100 || $distance > 10000) {
                $errors[] = 'Дистанция должна быть от 100 до 10000 метров';
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public static function validateResult(array $data, bool $requireId = false): array
    {
        $errors = [];

        if ($requireId) {
            $id = $data['id'] ?? null;
            if ($id === null || $id === '' || (int) $id <= 0) {
                $errors[] = 'Некорректный ID результата';
            }
        }

        foreach (['race_id', 'horse_id', 'jockey_id'] as $field) {
            $val = $data[$field] ?? null;
            if ($val === null || $val === '' || (int) $val <= 0) {
                $errors[] = "Некорректный {$field}";
            }
        }

        $place = $data['place'] ?? null;
        if ($place === null || $place === '' || (int) $place < 1 || (int) $place > 50) {
            $errors[] = 'Место должно быть от 1 до 50';
        }

        if (isset($data['finish_time']) && $data['finish_time'] !== null && $data['finish_time'] !== '') {
            $time = (float) $data['finish_time'];
            if ($time <= 0 || $time > 9999.99) {
                $errors[] = 'Время финиша должно быть положительным числом';
            }
        }

        return $errors;
    }
}
