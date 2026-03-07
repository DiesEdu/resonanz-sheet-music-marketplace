<?php
namespace SheetMusic\Controllers;

use SheetMusic\Config\Database;
use SheetMusic\Models\Favorite;
use SheetMusic\Middleware\AuthMiddleware;

class FavoriteController
{
    private $favorite;

    public function __construct()
    {
        $database = new Database();
        $db = $database->getConnection();
        $this->favorite = new Favorite($db);
    }

    public function handleRequest($method, $id = null, $action = null)
    {
        $userData = AuthMiddleware::authenticate();
        $userId = (int) $userData['id'];

        switch ($method) {
            case 'GET':
                if ($id === 'check' && $action !== null && is_numeric($action)) {
                    $this->check($userId, (int) $action);
                    return;
                }
                $this->index($userId);
                return;

            case 'POST':
                $postAction = $id ?? '';
                if ($postAction === 'add') {
                    $this->add($userId);
                    return;
                }
                http_response_code(400);
                echo json_encode(['error' => 'Invalid favorites action']);
                return;

            case 'DELETE':
                if ($id !== null && is_numeric($id)) {
                    $this->remove($userId, (int) $id);
                    return;
                }
                http_response_code(400);
                echo json_encode(['error' => 'Sheet ID required']);
                return;

            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
        }
    }

    private function index($userId)
    {
        $items = $this->favorite->getByUserId($userId);
        http_response_code(200);
        echo json_encode($items);
    }

    private function add($userId)
    {
        $data = json_decode(file_get_contents('php://input'));
        $sheetId = (int) ($data->sheet_id ?? 0);

        if ($sheetId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid sheet_id is required']);
            return;
        }

        if ($this->favorite->add($userId, $sheetId)) {
            http_response_code(200);
            echo json_encode(['message' => 'Added to favorites']);
            return;
        }

        http_response_code(500);
        echo json_encode(['error' => 'Unable to add favorite']);
    }

    private function remove($userId, $sheetId)
    {
        if ($sheetId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid sheet_id is required']);
            return;
        }

        if ($this->favorite->remove($userId, $sheetId)) {
            http_response_code(200);
            echo json_encode(['message' => 'Removed from favorites']);
            return;
        }

        http_response_code(500);
        echo json_encode(['error' => 'Unable to remove favorite']);
    }

    private function check($userId, $sheetId)
    {
        if ($sheetId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid sheet_id is required']);
            return;
        }

        $exists = $this->favorite->exists($userId, $sheetId);
        http_response_code(200);
        echo json_encode(['is_favorite' => $exists]);
    }
}
?>
