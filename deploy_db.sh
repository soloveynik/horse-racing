#!/bin/bash
set -e

echo "[1/5] Установка MariaDB..."
apt update
apt install -y mariadb-server mariadb-client ufw

echo "[2/5] Запуск MariaDB..."
systemctl enable --now mariadb

echo "[3/5] Создание БД и пользователя..."
mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS myapp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'appuser'@'192.168.126.10' IDENTIFIED BY 'StrongPassword123!';
GRANT SELECT, INSERT, UPDATE, DELETE ON myapp_db.* TO 'appuser'@'192.168.126.10';
FLUSH PRIVILEGES;
USE myapp_db;
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);
INSERT INTO items (name) VALUES ('item-1'), ('item-2'), ('item-3');
SQL

echo "[4/5] Настройка bind-address..."
sed -i 's/^bind-address.*/bind-address = 192.168.126.11/' /etc/mysql/mariadb.conf.d/50-server.cnf
systemctl restart mariadb

echo "[5/5] Firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow from 192.168.126.10 to any port 3306 proto tcp
ufw --force enable

echo "Готово."