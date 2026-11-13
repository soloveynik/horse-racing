#!/bin/bash
set -e

REPO_URL="https://github.com/soloveynik/horse-racing.git"

echo "[1/7] Установка Apache, PHP и пакетов..."
apt update
apt install -y apache2 php php-mysql libapache2-mod-php git iptables-persistent

echo "[2/7] Клонирование проекта..."
rm -rf /var/www/html/*
git clone -b main "$REPO_URL" /var/www/html
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

echo "[3/7] Настройка .env..."
cat > /var/www/html/.env <<'EOF'
DB_HOST=192.168.126.11
DB_NAME=horse_racing
DB_USER=appuser
DB_PASSWORD=StrongPassword123
EOF
chown www-data:www-data /var/www/html/.env
chmod 640 /var/www/html/.env

echo "[4/7] Настройка Apache..."
systemctl enable apache2
systemctl restart apache2

echo "[5/7] Настройка iptables..."
iptables -F
iptables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
iptables -A INPUT -i lo -j ACCEPT
iptables -A INPUT -p tcp --dport 22 -j ACCEPT
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT
netfilter-persistent save

echo "[6/7] Очистка старых настроек (Flask)..."
rm -f /etc/systemd/system/myapp.service
systemctl daemon-reload
systemctl stop myapp 2>/dev/null || true
systemctl disable myapp 2>/dev/null || true
rm -rf /opt/myapp /etc/myapp

echo "[7/7] Готово."
echo "Проверьте: curl http://127.0.0.1/api/health.php"