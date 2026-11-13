# Lab 3 — Horse Racing
# Запрещено: пропускать тесты, снижать пороги без обоснования (см. POLICY.md)

.PHONY: help install setup up down wait-db db-reset migrate test test-coverage \
        lint analyse sast verify run backup restore clean

PHP ?= php
COMPOSER ?= composer
PHINX ?= $(PHP) vendor/bin/phinx
PHPUNIT ?= $(PHP) vendor/bin/phpunit
PHPCS ?= $(PHP) vendor/bin/phpcs
PHPSTAN ?= $(PHP) vendor/bin/phpstan

COVERAGE_MIN := 40

DB_HOST ?= 127.0.0.1
DB_PORT ?= 3306
DB_USER ?= root
DB_PASSWORD ?= root
DB_NAME ?= horse_racing
DB_NAME_TEST ?= horse_racing_test

MYSQL = mysql -h$(DB_HOST) -P$(DB_PORT) -u$(DB_USER) -p$(DB_PASSWORD)
MYSQLDUMP = mysqldump -h$(DB_HOST) -P$(DB_PORT) -u$(DB_USER) -p$(DB_PASSWORD)

help:
	@echo "Lab 3 commands:"
	@echo "  make up          - start MySQL (Docker)"
	@echo "  make setup       - install + DB + migrations"
	@echo "  make verify      - lint + sast + tests/coverage"
	@echo "  make test        - unit + integration"
	@echo "  make migrate     - run migrations"
	@echo "  make backup      - DB backup"
	@echo "  make restore FILE=backups/xxx.sql"

install:
	$(COMPOSER) install --no-interaction --prefer-dist

up:
	docker compose up -d
	@$(MAKE) wait-db

down:
	docker compose down

wait-db:
	@echo "Waiting for MySQL..."
	@for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30; do \
		if $(MYSQL) -e "SELECT 1" >/dev/null 2>&1; then echo "MySQL is up"; exit 0; fi; \
		sleep 2; \
	done; \
	echo "MySQL timeout"; exit 1

setup: install
	@if [ ! -f .env ]; then cp .env.example .env; echo "Created .env"; fi
	@mkdir -p backups build
	@docker compose ps --status running 2>/dev/null | grep -q db || $(MAKE) up
	@$(MAKE) db-reset
	@echo "Setup done. Run: make verify"

db-reset:
	@echo "Reset DB $(DB_NAME) / $(DB_NAME_TEST) on $(DB_HOST)..."
	@$(MYSQL) -e "DROP DATABASE IF EXISTS $(DB_NAME); CREATE DATABASE $(DB_NAME) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	@$(MYSQL) -e "DROP DATABASE IF EXISTS $(DB_NAME_TEST); CREATE DATABASE $(DB_NAME_TEST) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	@$(PHINX) migrate -e development
	@echo "Migrations applied"

migrate:
	$(PHINX) migrate -e development

migrate-status:
	$(PHINX) status -e development

test:
	$(PHPUNIT) --testdox

test-unit:
	$(PHPUNIT) --testsuite Unit --testdox

test-integration:
	$(PHPUNIT) --testsuite Integration --testdox

test-coverage:
	@mkdir -p build/coverage
	XDEBUG_MODE=coverage $(PHPUNIT) --coverage-text --coverage-html=build/coverage --coverage-clover=build/coverage/clover.xml
	@$(PHP) scripts/check_coverage.php build/coverage/clover.xml $(COVERAGE_MIN)

lint:
	$(PHPCS) --standard=phpcs.xml -n

analyse:
	$(PHPSTAN) analyse -c phpstan.neon --memory-limit=512M

sast: analyse
	@$(COMPOSER) audit --no-interaction || true
	@echo "SAST done"

run:
	@echo "http://127.0.0.1:8080"
	$(PHP) -S 127.0.0.1:8080 -t public

backup:
	@mkdir -p backups
	@FILE=backups/horse_racing_$$(date +%Y%m%d_%H%M%S).sql; \
	$(MYSQLDUMP) $(DB_NAME) > $$FILE; \
	echo "Backup: $$FILE"

restore:
	@if [ -z "$(FILE)" ]; then echo "Usage: make restore FILE=backups/xxx.sql"; exit 1; fi
	@$(MYSQL) $(DB_NAME) < $(FILE)
	@echo "Restored from $(FILE)"

clean:
	rm -rf build/coverage build/phpunit-cache .phpunit.cache

verify: lint analyse sast test-coverage
	@echo ""
	@echo "============================================"
	@echo "  make verify: OK"
	@echo "============================================"
