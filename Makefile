.PHONY: container-check

container-check:
	@echo "=== Проверка запрета использования latest ==="
	@if grep -q "latest" Dockerfile docker-compose.yml 2>/dev/null; then \
		echo "ОШИБКА: Использование тега latest запрещено!"; exit 1; \
	fi

	@echo "=== Проверка запуска от root ==="
	@if ! grep -q "USER www-data" Dockerfile; then \
		echo "ОШИБКА: Приложение должно запускаться от непривилегированного пользователя!"; exit 1; \
	fi

	@echo "=== Проверка публикации лишних портов ==="
	@if grep -q "3306:3306" docker-compose.yml; then \
		echo "ОШИБКА: Порт БД не должен публиковаться!"; exit 1; \
	fi

	@echo "=== Проверка наличия healthcheck ==="
	@if ! grep -q "healthcheck:" docker-compose.yml; then \
		echo "ОШИБКА: Healthcheck обязателен!"; exit 1; \
	fi

	@echo "✅ Все проверки пройдены!"
