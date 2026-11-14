# Horse Racing — Лабораторная работа №3

Автоматические проверки, миграции и резервное копирование.

## Быстрый старт

```bash
cp .env.example .env
docker compose up -d
composer install
make setup
make verify
```

## Команды

| Команда | Назначение |
|---------|------------|
| `make up` | MySQL в Docker |
| `make setup` | установка + БД + миграции |
| `make verify` | lint + sast + тесты + покрытие ≥40% |
| `make test` | unit + integration |
| `make migrate` | миграции |
| `make backup` | резервная копия |
| `make restore FILE=...` | восстановление |

## Пункты задания

1. `make setup`  
2. Фиксированные версии в composer.lock  
3. install / run / test / migrate / backup / restore  
4. Unit + integration, покрытие ≥ 40%  
5. Валидация и граничные случаи (`Validator`)  
6. БД `horse_racing_test`  
7. phpcs, phpstan, отчёт coverage  
8. 3 миграции Phinx  
9. Тесты чистой и заполненной БД  
10. Функция `distance_meters`  
11–12. backup / restore  
13. `make verify`  
14. `POLICY.md`  
15. Изменения только после `make verify`  

## Защита

```bash
make verify
make backup
# порча данных:
mysql -h127.0.0.1 -uroot -proot horse_racing -e "DELETE FROM races;"
make restore FILE=backups/XXXX.sql
```
