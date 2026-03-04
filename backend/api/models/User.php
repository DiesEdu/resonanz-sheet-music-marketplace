<?php
// api/models/User.php
namespace SheetMusic\Models;

use PDO;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User
{
    private $conn;
    private $table = 'users';

    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $avatar;
    public $role;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table . "
                  SET username = :username,
                      email = :email,
                      password_hash = :password_hash,
                      full_name = :full_name";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind data
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password);
        $stmt->bindParam(':full_name', $this->full_name);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->role = 'user';
            return true;
        }
        return false;
    }

    public function login()
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE username = :username OR email = :email 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($this->password, $row['password_hash'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->full_name = $row['full_name'];
                $this->role = $row['role'];
                $this->avatar = $row['avatar'];
                return true;
            }
        }
        return false;
    }

    public function generateJWT()
    {
        $payload = [
            'iss' => 'sheetmusic-marketplace',
            'aud' => 'sheetmusic-marketplace',
            'iat' => time(),
            'exp' => time() + JWT_EXPIRATION,
            'data' => [
                'id' => $this->id,
                'username' => $this->username,
                'email' => $this->email,
                'role' => $this->role
            ]
        ];

        return JWT::encode($payload, JWT_SECRET_KEY, 'HS256');
    }

    public function getById($id)
    {
        $query = "SELECT id, username, email, full_name, avatar, role, created_at 
                  FROM " . $this->table . " WHERE id = :id LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile($id, $data)
    {
        $query = "UPDATE " . $this->table . "
                  SET full_name = :full_name,
                      email = :email
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function updateAvatar($id, $avatar_path)
    {
        $query = "UPDATE " . $this->table . " SET avatar = :avatar WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':avatar', $avatar_path);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function addToWishlist($user_id, $sheet_id)
    {
        $query = "INSERT INTO wishlist (user_id, sheet_id) VALUES (:user_id, :sheet_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':sheet_id', $sheet_id);
        return $stmt->execute();
    }

    public function removeFromWishlist($user_id, $sheet_id)
    {
        $query = "DELETE FROM wishlist WHERE user_id = :user_id AND sheet_id = :sheet_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':sheet_id', $sheet_id);
        return $stmt->execute();
    }

    public function getWishlist($user_id)
    {
        $query = "SELECT s.*, w.created_at as added_date 
                  FROM wishlist w
                  JOIN sheet_music s ON w.sheet_id = s.id
                  WHERE w.user_id = :user_id
                  ORDER BY w.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll()
    {
        $query = "SELECT id, username, email, full_name, role, created_at
                  FROM " . $this->table . "
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRole($id, $role)
    {
        $query = "UPDATE " . $this->table . "
                  SET role = :role
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
?>
