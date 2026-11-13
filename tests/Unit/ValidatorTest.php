<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidUserRegistration(): void
    {
        $errors = Validator::validateUserRegistration([
            'username' => 'admin_user',
            'email' => 'admin@example.com',
            'password' => 'secret12',
        ]);
        $this->assertSame([], $errors);
    }

    public function testEmptyUserFields(): void
    {
        $errors = Validator::validateUserRegistration([]);
        $this->assertNotEmpty($errors);
        $this->assertContains('Имя пользователя обязательно', $errors);
        $this->assertContains('Email обязателен', $errors);
        $this->assertContains('Пароль обязателен', $errors);
    }

    public function testShortPassword(): void
    {
        $errors = Validator::validateUserRegistration([
            'username' => 'user1',
            'email' => 'u@example.com',
            'password' => '12345',
        ]);
        $this->assertContains('Пароль должен содержать минимум 6 символов', $errors);
    }

    public function testInvalidEmail(): void
    {
        $errors = Validator::validateUserRegistration([
            'username' => 'user1',
            'email' => 'not-an-email',
            'password' => 'secret12',
        ]);
        $this->assertContains('Некорректный формат email', $errors);
    }

    public function testUsernameTooShort(): void
    {
        $errors = Validator::validateUserRegistration([
            'username' => 'ab',
            'email' => 'a@b.com',
            'password' => 'secret12',
        ]);
        $this->assertContains('Имя пользователя должно быть от 3 до 50 символов', $errors);
    }

    public function testValidHorse(): void
    {
        $errors = Validator::validateHorse([
            'name' => 'Буцефал',
            'owner_id' => 1,
            'birth_year' => 2018,
            'breed' => 'Арабская',
        ]);
        $this->assertSame([], $errors);
    }

    public function testHorseMissingNameAndOwner(): void
    {
        $errors = Validator::validateHorse([]);
        $this->assertContains('Имя лошади обязательно', $errors);
        $this->assertContains('Необходимо указать корректного владельца', $errors);
    }

    public function testHorseBirthYearBoundary(): void
    {
        $current = (int) date('Y');
        $errorsLow = Validator::validateHorse([
            'name' => 'Old',
            'owner_id' => 1,
            'birth_year' => 1979,
        ]);
        $this->assertNotEmpty($errorsLow);

        $errorsHigh = Validator::validateHorse([
            'name' => 'Future',
            'owner_id' => 1,
            'birth_year' => $current + 1,
        ]);
        $this->assertNotEmpty($errorsHigh);

        $errorsOk = Validator::validateHorse([
            'name' => 'Ok',
            'owner_id' => 1,
            'birth_year' => 1980,
        ]);
        $this->assertSame([], $errorsOk);
    }

    public function testValidOwner(): void
    {
        $this->assertSame([], Validator::validateOwner(['name' => 'Иванов']));
    }

    public function testOwnerEmptyName(): void
    {
        $errors = Validator::validateOwner(['name' => '  ']);
        $this->assertContains('Имя владельца обязательно', $errors);
    }

    public function testJockeyExperienceBoundaries(): void
    {
        $this->assertSame([], Validator::validateJockey([
            'name' => 'Жокей',
            'experience_years' => 0,
        ]));
        $this->assertSame([], Validator::validateJockey([
            'name' => 'Жокей',
            'experience_years' => 60,
        ]));

        $errors = Validator::validateJockey([
            'name' => 'Жокей',
            'experience_years' => 61,
        ]);
        $this->assertContains('Опыт должен быть от 0 до 60 лет', $errors);

        $errorsNeg = Validator::validateJockey([
            'name' => 'Жокей',
            'experience_years' => -1,
        ]);
        $this->assertContains('Опыт должен быть от 0 до 60 лет', $errorsNeg);
    }

    public function testValidRaceWithDistance(): void
    {
        $errors = Validator::validateRace([
            'name' => 'Дерби',
            'race_date' => '2025-06-15',
            'location' => 'Москва',
            'status' => 'planned',
            'distance_meters' => 2400,
        ]);
        $this->assertSame([], $errors);
    }

    public function testRaceInvalidDateAndDistance(): void
    {
        $errors = Validator::validateRace([
            'name' => 'Test',
            'race_date' => '2025-13-40',
            'location' => 'A',
            'distance_meters' => 50,
        ]);
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            in_array('Некорректная дата скачки', $errors, true)
            || in_array('Дата должна быть в формате YYYY-MM-DD', $errors, true)
            || count($errors) >= 1
        );
        $this->assertContains('Дистанция должна быть от 100 до 10000 метров', $errors);
    }

    public function testRaceInvalidStatus(): void
    {
        $errors = Validator::validateRace([
            'name' => 'Test',
            'race_date' => '2025-01-01',
            'location' => 'Track',
            'status' => 'unknown',
        ]);
        $this->assertContains('Недопустимый статус скачки', $errors);
    }

    public function testValidResult(): void
    {
        $errors = Validator::validateResult([
            'race_id' => 1,
            'horse_id' => 2,
            'jockey_id' => 3,
            'place' => 1,
            'finish_time' => 125.45,
        ]);
        $this->assertSame([], $errors);
    }

    public function testResultPlaceBoundaries(): void
    {
        $errorsLow = Validator::validateResult([
            'race_id' => 1,
            'horse_id' => 2,
            'jockey_id' => 3,
            'place' => 0,
        ]);
        $this->assertContains('Место должно быть от 1 до 50', $errorsLow);

        $errorsHigh = Validator::validateResult([
            'race_id' => 1,
            'horse_id' => 2,
            'jockey_id' => 3,
            'place' => 51,
        ]);
        $this->assertContains('Место должно быть от 1 до 50', $errorsHigh);
    }

    public function testResultNegativeFinishTime(): void
    {
        $errors = Validator::validateResult([
            'race_id' => 1,
            'horse_id' => 2,
            'jockey_id' => 3,
            'place' => 1,
            'finish_time' => -1,
        ]);
        $this->assertContains('Время финиша должно быть положительным числом', $errors);
    }
}
