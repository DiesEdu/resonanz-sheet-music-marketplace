<?php
// api/controllers/OrderController.php
namespace SheetMusic\Controllers;

use SheetMusic\Config\Database;
use SheetMusic\Models\Order;
use SheetMusic\Models\Cart;
use SheetMusic\Middleware\AuthMiddleware;

class OrderController
{
    private $db;
    private $order;
    private $cart;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->order = new Order($this->db);
        $this->cart = new Cart($this->db);
    }

    public function handleRequest($method, $id = null, $action = null)
    {
        $user_data = AuthMiddleware::authenticate();

        switch ($method) {
            case 'GET':
                if ($id && $action === 'download') {
                    $this->downloadSheet($id, $user_data['id']);
                } elseif ($id) {
                    $this->getOrder($id, $user_data['id']);
                } elseif ($action === 'downloads') {
                    $this->getDownloads($user_data['id']);
                } else {
                    $this->getUserOrders($user_data['id']);
                }
                break;

            case 'POST':
                if ($action === 'checkout') {
                    $this->checkout($user_data['id']);
                }
                break;

            case 'PUT':
                if ($action === 'status' && $id) {
                    $this->updateStatus($id);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    }

    private function checkout($user_id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        // Get cart items
        $cart = $this->cart->getOrCreateCart($user_id, null);
        $cart_items = $this->cart->getCartItems($cart['id']);

        if (empty($cart_items)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cart is empty']);
            return;
        }

        // Prepare billing info
        $billing_info = [
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'notes' => $data['notes'] ?? ''
        ];

        try {
            // Create order
            $order_id = $this->order->create($user_id, $cart_items, $billing_info);

            // Clear cart
            $this->cart->clearCart($cart['id']);

            // Process payment (integrate with payment gateway here)
            // For now, just mark as paid
            $this->order->updatePaymentStatus($order_id, 'paid');

            http_response_code(201);
            echo json_encode([
                'message' => 'Order created successfully',
                'order_id' => $order_id
            ]);

            // Send confirmation email
            $this->sendOrderConfirmation($order_id);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to process order: ' . $e->getMessage()]);
        }
    }

    private function getUserOrders($user_id)
    {
        $orders = $this->order->getUserOrders($user_id);
        http_response_code(200);
        echo json_encode($orders);
    }

    private function getOrder($order_id, $user_id)
    {
        $order = $this->order->getOrderDetails($order_id);

        if ($order && ($order['user_id'] == $user_id || $this->isAdmin())) {
            http_response_code(200);
            echo json_encode($order);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
        }
    }

    private function updateStatus($order_id)
    {
        // Check admin access
        AuthMiddleware::requireComposer();

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->status)) {
            if ($this->order->updateStatus($order_id, $data->status)) {
                http_response_code(200);
                echo json_encode(['message' => 'Order status updated']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Unable to update order status']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Status required']);
        }
    }

    private function getDownloads($user_id)
    {
        $downloads = $this->order->getDownloads($user_id);
        http_response_code(200);
        echo json_encode($downloads);
    }

    private function downloadSheet($sheet_id, $user_id)
    {
        // Verify purchase
        if (!$this->order->verifyPurchase($user_id, $sheet_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'You have not purchased this item']);
            return;
        }

        // Get sheet info
        $sheet = new SheetMusic($this->db);
        $sheet_info = $sheet->getById($sheet_id);

        if (!$sheet_info || !$sheet_info['file_path']) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
            return;
        }

        // Increment download count
        $sheet->incrementDownloads($sheet_id);

        // Serve file
        $file_path = __DIR__ . '/..' . $sheet_info['file_path'];

        if (file_exists($file_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($sheet_info['title'] . '.pdf"'));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
        }
    }

    private function sendOrderConfirmation($order_id)
    {
        // Use PHPMailer or similar to send email
        // Implementation depends on your email service
    }

    private function isAdmin()
    {
        try {
            $user = AuthMiddleware::authenticate();
            return $user['role'] === 'admin';
        } catch (\Exception $e) {
            return false;
        }
    }
}
?>