<?php
namespace SheetMusic\Models;

use PDO;

class Instrument
{
    private $conn;
    private $table = 'instruments';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll()
    {
        $query = "SELECT id, name, slug, icon, description
                  FROM " . $this->table . "
                  ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $query = "SELECT id, name, slug, icon, description
                  FROM " . $this->table . "
                  WHERE id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
