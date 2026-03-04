<?php
// api/config/database.php
namespace SheetMusic\Config;

use PDO;
use PDOException;

class Database
{
    private $host = 'localhost';
    private $db_name = 'sheet_music_marketplace';
    private $username = 'root';
    private $password = 'tester';
    private $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
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