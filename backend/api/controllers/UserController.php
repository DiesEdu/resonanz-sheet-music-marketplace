<?php
namespace SheetMusic\Controllers;

use SheetMusic\Config\Database;
use SheetMusic\Models\User;
use SheetMusic\Middleware\AuthMiddleware;

class UserController
{
    private $user;
    private $allowedRoles = ['admin', 'composer', 'user'];

    public function __construct()
    {
        $database = new Database();
        $db = $database->getConnection();
        $this->user = new User($db);
    }

    public function handleRequest($method, $id = null, $action = null)
    {
        switch ($method) {
            case 'GET':
                $this->getAllUsers();
                break;
            case 'PUT':
                if ($id && $action === 'role') {
                    $this->updateUserRole((int) $id);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'User action not found']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    }

    private function getAllUsers()
    {
        AuthMiddleware::requireAdmin();

        $users = $this->user->getAll();
        http_response_code(200);
        echo json_encode($users);
    }

    private function updateUserRole($targetUserId)
    {
        $adminUser = AuthMiddleware::requireAdmin();
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['role']) || !in_array($data['role'], $this->allowedRoles, true)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid role. Allowed roles: admin, composer, user.'
            ]);
            return;
        }

        if ($targetUserId === (int) $adminUser['id'] && $data['role'] !== 'admin') {
            http_response_code(400);
            echo json_encode(['error' => 'You cannot remove your own admin role.']);
            return;
        }

        if (!$this->user->getById($targetUserId)) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        if ($this->user->updateRole($targetUserId, $data['role'])) {
            http_response_code(200);
            echo json_encode(['message' => 'User role updated successfully']);
            return;
        }

        http_response_code(500);
        echo json_encode(['error' => 'Unable to update user role']);
    }
}
?>
