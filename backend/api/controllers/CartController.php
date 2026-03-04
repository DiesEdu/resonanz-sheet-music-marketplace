<?php
// api/controllers/CartController.php
namespace SheetMusic\Controllers;

use SheetMusic\Config\Database;
use SheetMusic\Models\Cart;
use SheetMusic\Models\SheetMusic;
use SheetMusic\Middleware\AuthMiddleware;

class CartController
{
    private $db;
    private $cart;
    private $sheet;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->cart = new Cart($this->db);
        $this->sheet = new SheetMusic($this->db);
    }

    public function handleRequest($method, $id = null, $action = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Keep a stable guest cart key across requests.
        if (empty($_SESSION['cart_session_id'])) {
            $_SESSION['cart_session_id'] = bin2hex(random_bytes(16));
        }
        $session_id = $_SESSION['cart_session_id'];

        // Try to authenticate user
        try {
            $user_data = AuthMiddleware::authenticate();
            $user_id = $user_data['id'];
        } catch (\Exception $e) {
            $user_id = null;
        }

        // Get or create cart
        $cart = $this->cart->getOrCreateCart($user_id, $session_id);
        $cart_id = $cart['id'];

        switch ($method) {
            case 'GET':
                $this->getCart($cart_id);
                break;

            case 'POST':
                $postAction = $action ?? $id;
                if ($postAction === 'add') {
                    $this->addToCart($cart_id);
                } elseif ($postAction === 'merge') {
                    $this->mergeCarts($cart_id, $user_id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid cart action']);
                }
                break;

            case 'PUT':
                // Supports both /cart/{sheet_id}/update and /cart/update/{sheet_id}
                if ($action === 'update' && $id && is_numeric($id)) {
                    $this->updateQuantity($cart_id, (int) $id);
                } elseif ($id === 'update' && $action && is_numeric($action)) {
                    $this->updateQuantity($cart_id, (int) $action);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid update request']);
                }
                break;

            case 'DELETE':
                if ($id && is_numeric($id)) {
                    $this->removeFromCart($cart_id, (int) $id);
                } elseif ($id === 'clear' || $id === null) {
                    $this->clearCart($cart_id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid delete request']);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    }

    private function getCart($cart_id)
    {
        $items = $this->cart->getCartItems($cart_id);
        $total = $this->cart->getCartTotal($cart_id);

        http_response_code(200);
        echo json_encode([
            'cart_id' => $cart_id,
            'items' => $items,
            'total' => $total,
            'item_count' => array_sum(array_column($items, 'quantity'))
        ]);
    }

    private function addToCart($cart_id)
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->sheet_id)) {
            $quantity = $data->quantity ?? 1;

            if ($this->cart->addItem($cart_id, $data->sheet_id, $quantity)) {
                http_response_code(200);
                echo json_encode(['message' => 'Item added to cart']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Unable to add item to cart']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Sheet ID required']);
        }
    }

    private function updateQuantity($cart_id, $sheet_id)
    {
        $data = json_decode(file_get_contents("php://input"));

        if (isset($data->quantity)) {
            if ($this->cart->updateQuantity($cart_id, $sheet_id, $data->quantity)) {
                http_response_code(200);
                echo json_encode(['message' => 'Cart updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Unable to update cart']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Quantity required']);
        }
    }

    private function removeFromCart($cart_id, $sheet_id)
    {
        if ($this->cart->removeItem($cart_id, $sheet_id)) {
            http_response_code(200);
            echo json_encode(['message' => 'Item removed from cart']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to remove item']);
        }
    }

    private function clearCart($cart_id)
    {
        if ($this->cart->clearCart($cart_id)) {
            http_response_code(200);
            echo json_encode(['message' => 'Cart cleared']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to clear cart']);
        }
    }

    private function mergeCarts($session_cart_id, $user_id)
    {
        if ($user_id) {
            $user_cart = $this->cart->mergeCarts($session_cart_id, $user_id);
            http_response_code(200);
            echo json_encode([
                'message' => 'Carts merged successfully',
                'cart_id' => $user_cart['id']
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'User not authenticated']);
        }
    }
}
?>
