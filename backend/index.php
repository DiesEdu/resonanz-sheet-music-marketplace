<?php
// index.php
// CORS: credentials require a specific origin (not *)
$allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'https://scores.resonanz.id'
];

$request_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($request_origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: " . $request_origin);
    header("Vary: Origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    // Non-browser or untrusted origin fallback.
    header("Access-Control-Allow-Origin: *");
}

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, HEAD");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Range");
header("Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges, Content-Type, Content-Disposition");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Composer autoloader for vendor packages (e.g., firebase/php-jwt)
require_once __DIR__ . '/vendor/autoload.php';

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'SheetMusic\\';
    $base_dir = __DIR__ . '/api/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $relative_path = str_replace('\\', '/', $relative_class) . '.php';

    // Linux hosts are case-sensitive; normalize known top-level folders.
    $normalized_path = preg_replace_callback(
        '#^(Models|Controllers|Middleware|Config)/#',
        function ($matches) {
            return strtolower($matches[1]) . '/';
        },
        $relative_path
    );

    $file = $base_dir . $normalized_path;

    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
require_once __DIR__ . '/api/config/database.php';

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$segments = explode('/', trim($path, '/'));

// Route to appropriate controller
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;
$action = $segments[2] ?? null;

try {
    switch ($resource) {
        case 'auth':
            require_once __DIR__ . '/api/controllers/AuthController.php';
            $controller = new SheetMusic\Controllers\AuthController();
            // Auth routes are /api/auth/{action}
            $controller->handleRequest($_SERVER['REQUEST_METHOD'], $id);
            break;

        case 'sheets':
            require_once __DIR__ . '/api/controllers/SheetController.php';
            $controller = new SheetMusic\Controllers\SheetController();
            $controller->handleRequest($_SERVER['REQUEST_METHOD'], $id, $action);
            break;

        case 'cart':
            require_once __DIR__ . '/api/controllers/CartController.php';
            $controller = new SheetMusic\Controllers\CartController();
            $controller->handleRequest($_SERVER['REQUEST_METHOD'], $id, $action);
            break;

        case 'orders':
            require_once __DIR__ . '/api/controllers/OrderController.php';
            $controller = new SheetMusic\Controllers\OrderController();
            $controller->handleRequest($_SERVER['REQUEST_METHOD'], $id, $action);
            break;
        case 'users':
            require_once __DIR__ . '/api/controllers/UserController.php';
            $controller = new SheetMusic\Controllers\UserController();
            $controller->handleRequest($_SERVER['REQUEST_METHOD'], $id, $action);
            break;

        case 'categories':
            require_once __DIR__ . '/api/controllers/CategoryController.php';
            $controller = new SheetMusic\Controllers\CategoryController();
            $controller->handleRequest($_SERVER['REQUEST_METHOD'], $id);
            break;

        case 'instruments':
            require_once __DIR__ . '/api/controllers/InstrumentController.php';
            $controller = new SheetMusic\Controllers\InstrumentController();
            $controller->handleRequest($_SERVER['REQUEST_METHOD'], $id);
            break;

        case 'uploads':
            // Serve uploaded files
            $relativePath = implode('/', array_slice($segments, 1));
            $uploadsRoot = realpath(__DIR__ . '/api/uploads');
            $filePath = realpath(__DIR__ . '/api/uploads/' . $relativePath);

            if ($uploadsRoot && $filePath && str_starts_with($filePath, $uploadsRoot) && file_exists($filePath)) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($filePath) ?: 'application/octet-stream';

                header("Content-Type: " . $mime_type);
                header("Content-Length: " . filesize($filePath));
                header("Content-Disposition: inline; filename=\"" . basename($filePath) . "\"");
                header("Accept-Ranges: bytes");
                if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
                    exit();
                }
                readfile($filePath);
                exit();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Resource not found']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
