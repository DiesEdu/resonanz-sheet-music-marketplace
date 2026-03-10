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
                } elseif ($action === 'resend-verification') {
                    $this->resendVerificationEmail();
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Auth action not found']);
                }
                break;

            case 'GET':
                if ($action === 'verify') {
                    $this->verifyEmail();
                } elseif ($action === 'profile') {
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
                } elseif ($action === 'password') {
                    $this->changePassword();
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

    private function verifyEmail()
    {
        $token = $_GET['token'] ?? null;

        if (!$token) {
            http_response_code(400);
            echo json_encode(['error' => 'Verification token required']);
            return;
        }

        $query = "UPDATE users 
              SET email_verified = 1, verification_token = NULL
              WHERE verification_token = :token";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            // redirect to Vue success page
            header("Location: https://scores.resonanz.id/verified");
            exit();

        } else {

            http_response_code(400);
            echo json_encode(['error' => 'Invalid or expired verification link']);

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
                        'copyright_name' => $this->user->copyright_name,
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
            $this->user->copyright_name = $data->copyright_name ?? '';

            $verification_token = bin2hex(random_bytes(32));
            $this->user->verification_token = $verification_token;

            if ($this->user->create()) {

                $this->sendVerificationEmail($this->user->email, $this->user->username, $verification_token);

                $token = $this->user->generateJWT();

                http_response_code(201);
                echo json_encode([
                    'message' => 'User created successfully. Please verify your email.',
                    'token' => $token,
                    'user' => [
                        'id' => $this->user->id,
                        'username' => $this->user->username,
                        'email' => $this->user->email,
                        'full_name' => $this->user->full_name,
                        'copyright_name' => $this->user->copyright_name,
                        'role' => $this->user->role
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

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request body']);
            return;
        }

        if (isset($data['username']) && trim((string) $data['username']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Username cannot be empty']);
            return;
        }

        $result = $this->user->updateProfile($user_data['id'], $data);
        if (!$result['ok']) {
            $errorMessage = $result['error'] ?? 'Unable to update profile';

            if ($errorMessage === 'No profile fields provided') {
                http_response_code(400);
                echo json_encode(['error' => $errorMessage]);
                return;
            }

            if ($errorMessage === 'Username or email already exists') {
                http_response_code(409);
                echo json_encode(['error' => $errorMessage]);
                return;
            }

            http_response_code(500);
            echo json_encode(['error' => $errorMessage]);
            return;
        }

        $updatedProfile = $this->user->getById($user_data['id']);
        if ($updatedProfile) {
            http_response_code(200);
            echo json_encode([
                'message' => 'Profile updated successfully',
                'user' => $updatedProfile
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Profile updated, but failed to fetch latest profile']);
        }
    }

    private function changePassword()
    {
        $user_data = AuthMiddleware::authenticate();
        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request body']);
            return;
        }

        $currentPassword = trim((string) ($data['current_password'] ?? ''));
        $newPassword = (string) ($data['new_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Current password and new password are required']);
            return;
        }

        if (strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'New password must be at least 6 characters']);
            return;
        }

        $result = $this->user->changePassword($user_data['id'], $currentPassword, $newPassword);
        if (!$result['ok']) {
            http_response_code($result['status'] ?? 500);
            echo json_encode(['error' => $result['error'] ?? 'Unable to change password']);
            return;
        }

        http_response_code(200);
        echo json_encode(['message' => 'Password updated successfully']);
    }

    private function resendVerificationEmail()
    {
        $user_data = AuthMiddleware::authenticate();
        $verificationData = $this->user->getVerificationDataById($user_data['id']);

        if (!$verificationData) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        if ((int) ($verificationData['email_verified'] ?? 0) === 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Email is already verified']);
            return;
        }

        $verificationToken = $verificationData['verification_token'];
        if (!$verificationToken) {
            $verificationToken = bin2hex(random_bytes(32));
            if (!$this->user->setVerificationToken((int) $verificationData['id'], $verificationToken)) {
                http_response_code(500);
                echo json_encode(['error' => 'Unable to prepare verification email']);
                return;
            }
        }

        $sent = $this->sendVerificationEmail(
            (string) $verificationData['email'],
            (string) $verificationData['username'],
            $verificationToken
        );

        if (!$sent) {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to send verification email']);
            return;
        }

        http_response_code(200);
        echo json_encode(['message' => 'Verification email sent successfully']);
    }

    private function sendVerificationEmail($email, $username, $verificationToken)
    {
        $base_url = $_ENV['APP_URL'] ?? 'https://scores.resonanz.id';
        $verify_link = $base_url . "/api/auth/verify?token=" . $verificationToken;

        $subject = "Verify your email";

        $message = "
Hello {$username},

Please verify your email by clicking the link below:

{$verify_link}
";

        $headers = "From: admin@scores.resonanz.id";
        return mail($email, $subject, $message, $headers);
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
