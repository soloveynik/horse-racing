#!/bin/bash
set -e

APP_DIR=/opt/myapp
CONF_DIR=/etc/myapp
REPO_URL="https://github.com/soloveynik/horse-racing.git"

echo "[1/7] Пакеты..."
apt update
apt install -y python3 python3-pip python3-venv git mariadb-client ufw

echo "[2/7] Клонирование репозитория..."
if [ ! -d "$APP_DIR/.git" ]; then
    git clone "$REPO_URL" "$APP_DIR"
else
    cd "$APP_DIR" && git pull
fi
chown -R admin:appuser "$APP_DIR"
chmod 750 "$APP_DIR"

echo "[3/7] Виртуальное окружение..."
if [ ! -d "$APP_DIR/venv" ]; then
    sudo -u appuser python3 -m venv "$APP_DIR/venv"
fi
sudo -u appuser "$APP_DIR/venv/bin/pip" install --upgrade pip
sudo -u appuser "$APP_DIR/venv/bin/pip" install -r "$APP_DIR/requirements.txt"

echo "[4/7] Конфигурация..."
mkdir -p "$CONF_DIR"
if [ ! -f "$CONF_DIR/config.env" ]; then
    cp "$APP_DIR/config.env.example" "$CONF_DIR/config.env"
    echo "!!! Отредактируйте $CONF_DIR/config.env и впишите пароль !!!"
fi
chown appuser:appuser "$CONF_DIR/config.env"
chmod 600 "$CONF_DIR/config.env"

echo "[5/7] systemd-служба..."
cp "$APP_DIR/myapp.service" /etc/systemd/system/myapp.service
systemctl daemon-reload
systemctl enable myapp
systemctl restart myapp

echo "[6/7] Firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 8080/tcp
ufw --force enable

echo "[7/7] Готово."
echo "Проверьте: curl http://127.0.0.1:8080/health"