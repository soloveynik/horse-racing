import os
import mysql.connector
from flask import Flask, jsonify

app = Flask(__name__)


def get_db():
    return mysql.connector.connect(
        host=os.environ.get("DB_HOST"),
        port=int(os.environ.get("DB_PORT", 3306)),
        user=os.environ.get("DB_USER"),
        password=os.environ.get("DB_PASSWORD"),
        database=os.environ.get("DB_NAME"),
    )


@app.route("/")
def index():
    return "MyApp is running on Nikita!\n"


@app.route("/health")
def health():
    try:
        conn = get_db()
        conn.close()
        return jsonify({"status": "ok", "db": "reachable"}), 200
    except Exception as e:
        return jsonify({"status": "error", "db": "unreachable", "detail": str(e)}), 500


@app.route("/items")
def items():
    conn = get_db()
    cur = conn.cursor()
    cur.execute("SELECT id, name FROM items")
    rows = cur.fetchall()
    cur.close()
    conn.close()
    return jsonify([{"id": r[0], "name": r[1]} for r in rows])


if __name__ == "__main__":
    app.run(
        host=os.environ.get("APP_HOST", "0.0.0.0"),
        port=int(os.environ.get("APP_PORT", 8080)),
    )