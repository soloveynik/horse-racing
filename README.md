# Horse Racing

Веб-приложение для учёта информации о скачках (лошади, владельцы, жокеи, скачки, результаты).

## Быстрый старт

```bash
# Требования: PHP ≥ 8.2, MySQL/MariaDB, Composer, make
make setup          # зависимости, .env, чистая БД, миграции
make run            # http://127.0.0.1:8080
make verify         # полный набор локальных проверок (обязателен перед merge)
```

## Командный интерфейс

| Команда | Назначение |
|---------|------------|
| `make setup` | Первоначальная настройка (п.1) |
| `make install` | Установка зависимостей с зафиксированными версиями (п.2) |
| `make run` | Локальный запуск |
| `make test` | Unit + integration тесты |
| `make test-coverage` | Тесты + отчёт покрытия (≥ 40%) |
| `make quality` | Линтер + статический анализ |
| `make lint` | PHPCS |
| `make analyse` / `make sast` | PHPStan + composer audit |
| `make migrate` | Применение миграций Phinx |
| `make backup` | Резервная копия БД |
| `make restore FILE=...` | Восстановление из копии |
| `make verify` | Полный набор проверок (п.13) |
| `make db-reset` | Чистая БД + миграции |
| `make up` / `make down` | Контейнеры (если есть docker-compose) |
| `make container-check` | Статус контейнеров |

## Соответствие требованиям лабораторной

| № | Требование | Реализация |
|---|------------|------------|
| 1 | Единый сценарий настройки | `make setup` |
| 2 | Фиксация версий зависимостей | `composer.json` / `composer.lock` (точные версии) |
| 3 | Команды install/run/test/migrate/backup/restore | Makefile |
| 4 | Unit + integration тесты, покрытие ≥ 40% | `tests/Unit`, `tests/Integration`, `scripts/check_coverage.php` |
| 5 | Ошибочные данные, границы, правила | `ValidatorTest`, проверки в `RaceService` |
| 6 | Тестовая конфигурация и изоляция | `phpunit.xml` → БД `horse_racing_test`, env `testing` |
| 7 | Линтер, SAST, отчёты тестов/покрытия | phpcs, phpstan, clover/html в `build/coverage` |
| 8 | ≥ 3 последовательные миграции | `database/migrations/` (initial → indexes → distance) |
| 9 | Чистая БД и обновление БД с данными | `DatabaseSchemaTest`, `RaceServiceTest` |
| 10 | Новая функция со сменой схемы | `distance_meters` у скачек + поддержка в сервисе |
| 11 | Backup / restore | `make backup`, `make restore` |
| 12 | Восстановление после удаления | сценарий через backup → DROP/DELETE → restore |
| 13 | `make verify` | lint + analyse + sast + test-coverage |
| 14 | Запрет ослабления проверок | `POLICY.md`, комментарии в Makefile |
| 15 | Функциональное изменение только через verify | ветка + PR + обязательный `make verify` |

## Структура

```text
horse-racing/
├── api/                 # HTTP API endpoints
├── config/              # конфигурация БД
├── database/
│   ├── migrations/      # Phinx-миграции (≥ 3)
│   └── schema.sql
├── public/              # веб-интерфейс и точка входа сервера
├── src/
│   ├── Database/        # Connection
│   ├── Service/         # RaceService (бизнес-логика)
│   └── Validation/      # Validator
├── tests/
│   ├── Unit/
│   └── Integration/
├── scripts/check_coverage.php
├── Makefile
├── phinx.php
├── phpunit.xml
├── phpcs.xml
├── phpstan.neon
├── POLICY.md
└── composer.json
```

## Миграции (п.8–10)

1. `20240101000001_initial_schema` — users, owners, jockeys, horses, races, results
2. `20240115000002_add_indexes_and_status_check` — индексы
3. `20240201000003_add_distance_to_races` — колонка `distance_meters` (новая функция)

Проверка чистой БД и миграции поверх данных — в интеграционных тестах.

## Тесты и покрытие

```bash
make test              # без покрытия
make test-coverage     # HTML: build/coverage/, порог 40%
```

Тестовая БД изолирована (`horse_racing_test`), не пересекается с рабочей.

## Резервное копирование (п.11–12)

```bash
make backup
# ... намеренное удаление данных ...
make restore FILE=backups/horse_racing_YYYYMMDD_HHMMSS.sql
```

## Политика качества

См. [POLICY.md](POLICY.md): нельзя пропускать тесты, снижать пороги и добавлять исключения анализаторов без обоснования в PR.

## Технологии

- PHP 8.2+
- MySQL / MariaDB
- Phinx (миграции)
- PHPUnit 11
- PHPStan + strict-rules
- PHP_CodeSniffer
- Composer (зафиксированные версии)
