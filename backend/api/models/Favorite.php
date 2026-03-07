<?php
namespace SheetMusic\Models;

use PDO;
use PDOException;

class Favorite
{
    private $conn;
    private $table = 'favorites';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getByUserId($userId)
    {
        $query = "SELECT s.*, f.created_at AS added_date
                  FROM " . $this->table . " f
                  JOIN sheet_music s ON f.sheet_id = s.id
                  WHERE f.user_id = :user_id
                  ORDER BY f.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add($userId, $sheetId)
    {
        $query = "INSERT INTO " . $this->table . " (user_id, sheet_id)
                  VALUES (:user_id, :sheet_id)";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Duplicate favorite should not fail UI.
            if ($e->getCode() === '23000') {
                return true;
            }
            return false;
        }
    }

    public function remove($userId, $sheetId)
    {
        $query = "DELETE FROM " . $this->table . "
                  WHERE user_id = :user_id AND sheet_id = :sheet_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function exists($userId, $sheetId)
    {
        $query = "SELECT id FROM " . $this->table . "
                  WHERE user_id = :user_id AND sheet_id = :sheet_id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}
?>
