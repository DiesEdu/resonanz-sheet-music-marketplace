<?php
// api/models/User.php
namespace SheetMusic\Models;

use PDO;
use PDOException;
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
    public $copyright_name;
    public $verification_token;
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
                      full_name = :full_name,
                      copyright_name = :copyright_name,
                      verification_token = :verification_token";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->copyright_name = htmlspecialchars(strip_tags($this->copyright_name ?? ''));
        $this->verification_token = htmlspecialchars(strip_tags($this->verification_token));
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind data
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password);
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':copyright_name', $this->copyright_name);
        $stmt->bindParam(':verification_token', $this->verification_token);

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
                $this->copyright_name = $row['copyright_name'] ?? null;
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
        $query = "SELECT id, username, email, full_name, copyright_name, avatar, role, email_verified, created_at 
                  FROM " . $this->table . " WHERE id = :id LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile($id, $data)
    {
        $allowedFields = ['username', 'full_name', 'email', 'copyright_name'];
        $setParts = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            $setParts[] = "{$field} = :{$field}";
            $params[":{$field}"] = $value;
        }

        if (count($setParts) === 0) {
            return ['ok' => false, 'error' => 'No profile fields provided'];
        }

        $query = "UPDATE " . $this->table . "
                  SET " . implode(', ', $setParts) . "
                  WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return ['ok' => true];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['ok' => false, 'error' => 'Username or email already exists'];
            }
            return ['ok' => false, 'error' => 'Unable to update profile'];
        }
    }

    public function changePassword($id, $currentPassword, $newPassword)
    {
        $query = "SELECT password_hash FROM " . $this->table . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return ['ok' => false, 'error' => 'User not found', 'status' => 404];
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!password_verify($currentPassword, $row['password_hash'])) {
            return ['ok' => false, 'error' => 'Current password is incorrect', 'status' => 400];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateQuery = "UPDATE " . $this->table . " SET password_hash = :password_hash WHERE id = :id";
        $updateStmt = $this->conn->prepare($updateQuery);
        $updateStmt->bindParam(':password_hash', $newHash);
        $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $updateStmt->execute();

        return ['ok' => true];
    }

    public function getVerificationDataById($id)
    {
        $query = "SELECT id, username, email, email_verified, verification_token
                  FROM " . $this->table . "
                  WHERE id = :id
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function setVerificationToken($id, $token)
    {
        $query = "UPDATE " . $this->table . "
                  SET verification_token = :token
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

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
        $query = "SELECT id, username, email, full_name, copyright_name, role, created_at
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
