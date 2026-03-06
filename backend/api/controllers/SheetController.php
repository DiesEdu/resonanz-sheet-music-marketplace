<?php
// api/controllers/SheetController.php
namespace SheetMusic\Controllers;

use SheetMusic\Config\Database;
use SheetMusic\Models\SheetMusic;
use SheetMusic\Models\Category;
use SheetMusic\Models\Instrument;
use SheetMusic\Middleware\AuthMiddleware;

class SheetController
{
    private $db;
    private $sheet;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->sheet = new SheetMusic($this->db);
    }

    public function handleRequest($method, $id = null, $action = null)
    {
        switch ($method) {
            case 'GET':
                if ($id && $action === 'reviews') {
                    $this->getReviews($id);
                } elseif ($id && $action === 'related') {
                    $this->getRelated($id);
                } elseif ($id) {
                    $this->getOne($id);
                } elseif ($action === 'featured') {
                    $this->getFeatured();
                } elseif ($action === 'popular') {
                    $this->getPopular();
                } else {
                    $this->getAll();
                }
                break;

            case 'POST':
                if ($id && $action === 'reviews') {
                    $this->addReview($id);
                } else {
                    $this->create();
                }
                break;

            case 'PUT':
                if ($id) {
                    $this->update($id);
                }
                break;

            case 'DELETE':
                if ($id) {
                    $this->delete($id);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    }

    private function getAll()
    {
        // Get query parameters
        $filters = [
            'instrument' => $_GET['instrument'] ?? null,
            'difficulty' => $_GET['difficulty'] ?? null,
            'category' => $_GET['category'] ?? null,
            'search' => $_GET['search'] ?? null,
            'featured' => isset($_GET['featured']) ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN) : null,
            'premium' => isset($_GET['premium']) ? filter_var($_GET['premium'], FILTER_VALIDATE_BOOLEAN) : null,
            'min_price' => $_GET['min_price'] ?? null,
            'max_price' => $_GET['max_price'] ?? null
        ];

        $sort = $_GET['sort'] ?? 'title';
        $order = $_GET['order'] ?? 'ASC';
        $limit = $_GET['limit'] ?? 20;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $sheets = $this->sheet->getAll($filters, $sort, $order, $limit, $offset);

        // Get total count for pagination
        $total = count($sheets); // You might want a separate count query

        http_response_code(200);
        echo json_encode([
            'data' => $sheets,
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => (int) $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    private function getOne($id)
    {
        $sheet = $this->sheet->getById($id);

        if ($sheet) {
            http_response_code(200);
            echo json_encode($sheet);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Sheet music not found']);
        }
    }

    private function create()
    {
        // Check admin access
        $userData = AuthMiddleware::requireComposer();

        $data = $this->getRequestData();
        $uploadedFilePath = $this->handleFileUpload('sheet_file');

        if (empty($data['title']) || empty($data['composer'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Incomplete data']);
            return;
        }

        $requiredFields = ['instrument_id', 'category_id', 'difficulty', 'price', 'pages', 'format'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: {$field}"]);
                return;
            }
        }

        $resolvedFilePath = trim((string) ($uploadedFilePath ?? ($data['file_path'] ?? '')));
        if ($resolvedFilePath === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: file_path']);
            return;
        }

        $normalizedData = [
            'title' => trim((string) $data['title']),
            'subtitle' => trim((string) ($data['subtitle'] ?? '')),
            'composer' => trim((string) $data['composer']),
            'description' => trim((string) ($data['description'] ?? '')),
            'instrument_id' => (int) $data['instrument_id'],
            'list_instruments' => isset($data['list_instruments']) ? json_encode($data['list_instruments']) : null,
            'category_id' => (int) $data['category_id'],
            'difficulty' => trim((string) $data['difficulty']),
            'price' => (float) $data['price'],
            'pages' => (int) $data['pages'],
            'format' => trim((string) $data['format']),
            'file_path' => $resolvedFilePath,
            'cover_image' => trim((string) ($data['cover_image'] ?? '')),
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'is_premium' => !empty($data['is_premium']) ? 1 : 0,
            'rating' => isset($data['rating']) ? (float) $data['rating'] : 0.0,
            'reviews_count' => isset($data['reviews_count']) ? (int) $data['reviews_count'] : 0,
            'downloads_count' => isset($data['downloads_count']) ? (int) $data['downloads_count'] : 0,
            'views_count' => isset($data['views_count']) ? (int) $data['views_count'] : 0,
            'created_by' => (int) ($userData['id'] ?? 0)
        ];

        if ($normalizedData['price'] < 0 || $normalizedData['pages'] < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid price or pages value']);
            return;
        }

        if ($this->addSheetMusic($normalizedData)) {
            $sheetId = (int) $this->db->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'message' => 'Sheet music created successfully',
                'id' => $sheetId
            ]);
            return;
        }

        http_response_code(500);
        echo json_encode(['error' => 'Unable to create sheet music']);
    }

    private function getRequestData()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'multipart/form-data') !== false) {
            return $_POST;
        }

        $rawBody = file_get_contents("php://input");
        $decoded = json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function addSheetMusic($data)
    {
        $query = "INSERT INTO sheet_music (
            title, subtitle, composer, description, instrument_id, list_instruments, category_id,
            difficulty, price, pages, format, file_path, cover_image,
            is_featured, is_premium, rating, reviews_count, downloads_count, views_count, created_by
        ) VALUES (
            :title, :subtitle, :composer, :description, :instrument_id, :list_instruments, :category_id,
            :difficulty, :price, :pages, :format, :file_path, :cover_image,
            :is_featured, :is_premium, :rating, :reviews_count, :downloads_count, :views_count, :created_by
        )";

        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            ':title' => $data['title'],
            ':subtitle' => $data['subtitle'],
            ':composer' => $data['composer'],
            ':description' => $data['description'],
            ':instrument_id' => $data['instrument_id'],
            ':list_instruments' => $data['list_instruments'],
            ':category_id' => $data['category_id'],
            ':difficulty' => $data['difficulty'],
            ':price' => $data['price'],
            ':pages' => $data['pages'],
            ':format' => $data['format'],
            ':file_path' => $data['file_path'],
            ':cover_image' => $data['cover_image'],
            ':is_featured' => $data['is_featured'],
            ':is_premium' => $data['is_premium'],
            ':rating' => $data['rating'],
            ':reviews_count' => $data['reviews_count'],
            ':downloads_count' => $data['downloads_count'],
            ':views_count' => $data['views_count'],
            ':created_by' => $data['created_by']
        ]);
    }

    private function update($id)
    {
        // Check admin access
        AuthMiddleware::requireComposer();

        $data = json_decode(file_get_contents("php://input"), true);

        if ($this->sheet->update($id, $data)) {
            http_response_code(200);
            echo json_encode(['message' => 'Sheet music updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to update sheet music']);
        }
    }

    private function delete($id)
    {
        // Check admin access
        AuthMiddleware::requireComposer();

        if ($this->sheet->delete($id)) {
            http_response_code(200);
            echo json_encode(['message' => 'Sheet music deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to delete sheet music']);
        }
    }

    private function getReviews($id)
    {
        $reviews = $this->sheet->getReviews($id);
        http_response_code(200);
        echo json_encode($reviews);
    }

    private function addReview($id)
    {
        $user_data = AuthMiddleware::authenticate();

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->rating) && !empty($data->comment)) {
            if ($this->sheet->addReview($user_data['id'], $id, $data->rating, $data->comment)) {
                http_response_code(201);
                echo json_encode(['message' => 'Review added successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Unable to add review']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Rating and comment required']);
        }
    }

    private function getRelated($id)
    {
        $limit = $_GET['limit'] ?? 4;
        $related = $this->sheet->getRelated($id, $limit);
        http_response_code(200);
        echo json_encode($related);
    }

    private function getFeatured()
    {
        $limit = $_GET['limit'] ?? 6;
        $featured = $this->sheet->getFeatured($limit);
        http_response_code(200);
        echo json_encode($featured);
    }

    private function getPopular()
    {
        $limit = $_GET['limit'] ?? 6;
        $popular = $this->sheet->getPopular($limit);
        http_response_code(200);
        echo json_encode($popular);
    }

    private function handleFileUpload($field_name)
    {
        if (!isset($_FILES[$field_name])) {
            return null;
        }

        $file = $_FILES[$field_name];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedMimeTypes = ['application/pdf'];
        $tmpName = $file['tmp_name'] ?? '';
        $detectedMime = $tmpName !== '' ? mime_content_type($tmpName) : '';
        if (!in_array($detectedMime, $allowedMimeTypes, true)) {
            return null;
        }

        $upload_dir = __DIR__ . '/../uploads/sheets/';

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower((string) $extension) !== 'pdf') {
            return null;
        }
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/api/uploads/sheets/' . $filename;
        }

        return null;
    }
}
?>
