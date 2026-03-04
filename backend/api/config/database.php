<?php
// api/config/database.php
namespace SheetMusic\Config;

use PDO;
use PDOException;

class Database
{
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        $envPath = dirname(__DIR__, 2) . '/.env';
        $env = $this->loadEnv($envPath);

        // Prefer DB_* keys to avoid conflict with app/server HOST values.
        $rawDbHost = $env['DB_HOST'] ?? $env['db_host'] ?? null;
        $rawHost = $rawDbHost ?? $env['host'] ?? $env['HOST'] ?? 'localhost';
        $this->port = $env['DB_PORT'] ?? $env['db_port'] ?? '3306';

        // Support values like "localhost:8000" by splitting host/port safely.
        if (strpos($rawHost, ':') !== false) {
            $parts = explode(':', $rawHost, 2);
            if (!empty($parts[0])) {
                $rawHost = $parts[0];
            }
            if ($rawDbHost !== null && ($env['DB_PORT'] ?? $env['db_port'] ?? '') === '' && !empty($parts[1])) {
                $this->port = $parts[1];
            }
        }

        $this->host = $rawHost;
        $this->db_name = $env['DB_NAME'] ?? $env['db_name'] ?? 'sheet_music_marketplace';
        $this->username = $env['DB_USERNAME'] ?? $env['db_username'] ?? $env['username'] ?? $env['USERNAME'] ?? 'root';
        $this->password = $env['DB_PASSWORD'] ?? $env['db_password'] ?? $env['password'] ?? $env['PASSWORD'] ?? '';
    }

    private function loadEnv($path)
    {
        $vars = [];

        if (!file_exists($path)) {
            return $vars;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);

            if (
                strlen($value) >= 2 &&
                (
                    ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                    ($value[0] === "'" && $value[strlen($value) - 1] === "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [PDO::ATTR_TIMEOUT => 5]
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
}

// JWT Secret Key
define('JWT_SECRET_KEY', 'your-secret-key-here-change-in-production');
define('JWT_EXPIRATION', 86400); // 24 hours
?>
