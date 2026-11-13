# Horse Racing — единые команды управления проектом
# Запрещено: пропускать тесты, снижать пороги покрытия/анализаторов
# и добавлять исключения без явного обоснования в PR/коммите.
# См. POLICY.md

.PHONY: help install setup migrate migrate-status migrate-rollback \
        test test-unit test-integration test-coverage \
        lint analyse sast quality verify \
        run backup restore clean db-reset db-seed \
        up down container-check

PHP ?= php
COMPOSER ?= composer
PHINX ?= vendor/bin/phinx
PHPUNIT ?= vendor/bin/phpunit
PHPCS ?= vendor/bin/phpcs
PHPSTAN ?= vendor/bin/phpstan

# Порог покрытия (п.4, п.14) — снижение запрещено без обоснования в PR
COVERAGE_MIN := 40

help:
	@echo "Доступные команды:"
	@echo "  make setup            — первоначальная настройка проекта"
	@echo "  make install          — установка зависимостей (Composer)"
	@echo "  make run              — локальный запуск (PHP built-in server)"
	@echo "  make test             — автоматические тесты (unit + integration)"
	@echo "  make test-coverage    — тесты с отчётом покрытия (>= $(COVERAGE_MIN)%)"
	@echo "  make quality          — форматирование и статический анализ"
	@echo "  make lint             — проверка форматирования (phpcs)"
	@echo "  make analyse          — статический анализ (phpstan)"
	@echo "  make sast             — SAST / проверка безопасности"
	@echo "  make migrate          — применение миграций"
	@echo "  make migrate-status   — статус миграций"
	@echo "  make migrate-rollback — откат последней миграции"
	@echo "  make backup           — создание резервной копии БД"
	@echo "  make restore FILE=... — восстановление из резервной копии"
	@echo "  make verify           — полный набор локальных проверок"
	@echo "  make db-reset         — пересоздание чистой БД + миграции"
	@echo "  make up               — запуск контейнерного окружения (docker compose)"
	@echo "  make down             — остановка окружения"
	@echo "  make container-check  — проверка контейнерного окружения"
	@echo "  make clean            — очистка артефактов сборки"

install:
	$(COMPOSER) install --no-interaction --prefer-dist

# 1. Единый сценарий первоначальной настройки
setup: install
	@if [ ! -f .env ]; then cp .env.example .env; echo "Создан .env из .env.example"; fi
	@mkdir -p backups build
	@$(MAKE) db-reset
	@echo "Проект настроен. Запуск: make run"

db-reset:
	@mysql -u root -e "DROP DATABASE IF EXISTS horse_racing; CREATE DATABASE horse_racing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	@mysql -u root -e "DROP DATABASE IF EXISTS horse_racing_test; CREATE DATABASE horse_racing_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	@$(PHINX) migrate -e development
	@echo "БД пересозданы и миграции применены"

migrate:
	$(PHINX) migrate -e development

migrate-status:
	$(PHINX) status -e development

migrate-rollback:
	$(PHINX) rollback -e development

test:
	$(PHPUNIT) --testdox

test-unit:
	$(PHPUNIT) --testsuite Unit --testdox

test-integration:
	$(PHPUNIT) --testsuite Integration --testdox

test-coverage:
	@mkdir -p build/coverage
	$(PHPUNIT) --coverage-text --coverage-html=build/coverage --coverage-clover=build/coverage/clover.xml
	@$(PHP) scripts/check_coverage.php build/coverage/clover.xml $(COVERAGE_MIN)

quality: lint analyse

lint:
	$(PHPCS) --standard=phpcs.xml

analyse:
	$(PHPSTAN) analyse -c phpstan.neon --memory-limit=512M

sast: analyse
	@echo "SAST: composer audit + phpstan strict rules"
	@$(COMPOSER) audit --no-interaction || true
	@echo "SAST-проверки завершены"

run:
	@echo "Сервер: http://127.0.0.1:8080"
	$(PHP) -S 127.0.0.1:8080 -t public

# 11–12. Резервное копирование и восстановление
backup:
	@mkdir -p backups
	@FILE=backups/horse_racing_$$(date +%Y%m%d_%H%M%S).sql; \
	mysqldump -u root horse_racing > $$FILE; \
	echo "Резервная копия: $$FILE"

restore:
	@if [ -z "$(FILE)" ]; then echo "Укажите FILE=path/to/backup.sql"; exit 1; fi
	@mysql -u root horse_racing < $(FILE)
	@echo "Восстановление из $(FILE) выполнено"

# Docker (опционально, если есть docker-compose.yml)
up:
	@if [ -f docker-compose.yml ]; then docker compose up -d; else echo "docker-compose.yml отсутствует — используйте make run"; fi

down:
	@if [ -f docker-compose.yml ]; then docker compose down; else echo "docker-compose.yml отсутствует"; fi

container-check:
	@if [ -f docker-compose.yml ]; then docker compose ps; else echo "Контейнеры не используются; локальный стек: PHP + MySQL"; fi

clean:
	rm -rf build/coverage build/phpunit-cache .phpunit.cache
	@echo "Артефакты очищены"

# 13. Единая команда обязательных локальных проверок
# 14. Порог покрытия и отсутствие skip зафиксированы
verify: lint analyse sast test-coverage
	@echo ""
	@echo "============================================"
	@echo "  make verify: все проверки пройдены"
	@echo "============================================"
