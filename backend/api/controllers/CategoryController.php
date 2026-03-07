<?php
namespace SheetMusic\Controllers;

use SheetMusic\Config\Database;
use SheetMusic\Models\Category;

class CategoryController
{
    private $category;

    public function __construct()
    {
        $database = new Database();
        $db = $database->getConnection();
        $this->category = new Category($db);
    }

    public function handleRequest($method, $id = null)
    {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getOne((int) $id);
                } else {
                    $this->getAll();
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    }

    private function getAll()
    {
        $categories = $this->category->getAll();
        http_response_code(200);
        echo json_encode($categories);
    }

    private function getOne($id)
    {
        $category = $this->category->getById($id);

        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }

        http_response_code(200);
        echo json_encode($category);
    }
}
?>
