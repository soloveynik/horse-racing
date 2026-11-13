#!/bin/bash
set -e

echo "[1/6] Установка MariaDB и пакетов..."
apt update
apt install -y mariadb-server mariadb-client git iptables-persistent

echo "[2/6] Запуск MariaDB..."
systemctl enable --now mariadb

echo "[3/6] Создание БД и пользователя..."
mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS horse_racing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'appuser'@'192.168.126.10' IDENTIFIED BY 'StrongPassword123';
GRANT SELECT, INSERT, UPDATE, DELETE ON horse_racing.* TO 'appuser'@'192.168.126.10';
FLUSH PRIVILEGES;
SQL

echo "[4/6] Заливка схемы таблиц..."
cd /tmp
rm -rf hr
git clone -b main https://github.com/soloveynik/horse-racing.git hr
mysql horse_racing < /tmp/hr/database/schema.sql

echo "[5/6] Настройка bind-address (только внутренний IP)..."
sed -i 's/^bind-address.*/bind-address = 192.168.126.11/' /etc/mysql/mariadb.conf.d/50-server.cnf
systemctl restart mariadb

echo "[6/6] Настройка iptables..."
iptables -F
iptables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
iptables -A INPUT -i lo -j ACCEPT
iptables -A INPUT -p tcp --dport 22 -j ACCEPT
iptables -A INPUT -p tcp -s 192.168.126.10 --dport 3306 -j ACCEPT
iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT
netfilter-persistent save

echo "Готово. БД horse_racing развернута на nik."