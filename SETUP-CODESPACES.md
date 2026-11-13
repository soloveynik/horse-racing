# Codespaces — запуск лабы 3

## Проблема PHP

В Codespaces `php` часто из `/home/codespace/.php/...` **без** pdo_mysql.
Системный PHP: `/usr/bin/php8.3` — с модулем после `apt install php8.3-mysql`.

```bash
# 1) системный PHP + клиент mysql
sudo apt-get update
sudo apt-get install -y php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring mysql-client

export PATH=/usr/bin:$PATH
hash -r
php -m | grep pdo_mysql   # должно быть pdo_mysql

# 2) Docker MySQL
docker compose up -d

# 3) проект
cp .env.example .env
composer install
make setup
make verify
```

Если `make` всё ещё берёт старый php:

```bash
make setup PHP=/usr/bin/php8.3
make verify PHP=/usr/bin/php8.3
```
