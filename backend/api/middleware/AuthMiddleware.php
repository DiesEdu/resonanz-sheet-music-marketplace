<?php
// api/middleware/AuthMiddleware.php
namespace SheetMusic\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public static function authenticate()
    {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No authorization token provided']);
            exit();
        }

        $auth_header = $headers['Authorization'];
        $token = str_replace('Bearer ', '', $auth_header);

        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256'));
            return (array) $decoded->data;
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit();
        }
    }

    public static function requireAdmin()
    {
        $user = self::authenticate();

        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit();
        }

        return $user;
    }
}
?>