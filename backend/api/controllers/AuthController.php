<?php
// api/controllers/AuthController.php
namespace SheetMusic\Controllers;

use SheetMusic\Config\Database;
use SheetMusic\Models\User;
use SheetMusic\Middleware\AuthMiddleware;

class AuthController
{
    private $db;
    private $user;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function handleRequest($method, $action)
    {
        switch ($method) {
            case 'POST':
                if ($action === 'login') {
                    $this->login();
                } elseif ($action === 'register') {
                    $this->register();
                } elseif ($action === 'logout') {
                    $this->logout();
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Auth action not found']);
                }
                break;

            case 'GET':
                if ($action === 'profile') {
                    $this->getProfile();
                } elseif ($action === 'wishlist') {
                    $this->getWishlist();
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Auth action not found']);
                }
                break;

            case 'PUT':
                if ($action === 'profile') {
                    $this->updateProfile();
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Auth action not found']);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    }

    private function login()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->username) && !empty($data->password)) {
            $this->user->username = $data->username;
            $this->user->email = $data->username; // Allow login with email too
            $this->user->password = $data->password;

            if ($this->user->login()) {
                $token = $this->user->generateJWT();

                http_response_code(200);
                echo json_encode([
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => [
                        'id' => $this->user->id,
                        'username' => $this->user->username,
                        'email' => $this->user->email,
                        'full_name' => $this->user->full_name,
                        'role' => $this->user->role,
                        'avatar' => $this->user->avatar
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password required']);
        }
    }

    private function register()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->username) && !empty($data->email) && !empty($data->password)) {
            $this->user->username = $data->username;
            $this->user->email = $data->email;
            $this->user->password = $data->password;
            $this->user->full_name = $data->full_name ?? '';

            if ($this->user->create()) {
                $token = $this->user->generateJWT();

                http_response_code(201);
                echo json_encode([
                    'message' => 'User created successfully',
                    'token' => $token,
                    'user' => [
                        'id' => $this->user->id,
                        'username' => $this->user->username,
                        'email' => $this->user->email,
                        'full_name' => $this->user->full_name
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Unable to create user']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Incomplete data']);
        }
    }

    private function logout()
    {
        // Client-side should remove token
        http_response_code(200);
        echo json_encode(['message' => 'Logged out successfully']);
    }

    private function getProfile()
    {
        $user_data = AuthMiddleware::authenticate();

        $user_info = $this->user->getById($user_data['id']);

        if ($user_info) {
            http_response_code(200);
            echo json_encode($user_info);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    }

    private function updateProfile()
    {
        $user_data = AuthMiddleware::authenticate();
        $data = json_decode(file_get_contents("php://input"), true);

        if ($this->user->updateProfile($user_data['id'], $data)) {
            http_response_code(200);
            echo json_encode(['message' => 'Profile updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to update profile']);
        }
    }

    private function getWishlist()
    {
        $user_data = AuthMiddleware::authenticate();

        $wishlist = $this->user->getWishlist($user_data['id']);

        http_response_code(200);
        echo json_encode($wishlist);
    }
}
?>
