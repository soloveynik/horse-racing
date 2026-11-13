\# Lab 2 — Two-tier app



\## Architecture

\- Server 1 (`Nikita`, 192.168.126.10) — Flask app on port 8080

\- Server 2 (`nik`, 192.168.126.11) — MariaDB on port 3306 (only from 192.168.126.10)



\## Deploy



\### Server 2 (DB)

&#x20;   git clone <URL> /tmp/lab2

&#x20;   sudo bash /tmp/lab2/deploy\_db.sh



\### Server 1 (App)

&#x20;   git clone <URL> /tmp/lab2

&#x20;   sudo bash /tmp/lab2/deploy\_app.sh

&#x20;   sudo nano /etc/myapp/config.env   # set real password

&#x20;   sudo systemctl restart myapp



\## Verify

&#x20;   curl http://127.0.0.1:8080/health



\## Secrets

\- Passwords in `/etc/myapp/config.env` (mode 600, owner appuser)

\- Repo contains only `config.env.example` (no real password)

