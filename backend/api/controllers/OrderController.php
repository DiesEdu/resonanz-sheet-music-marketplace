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
        $requestedAction = $action ?? $id;

        switch ($method) {
            case 'GET':
                if ($id && $action === 'download') {
                    $this->downloadSheet($id, $user_data['id']);
                } elseif ($requestedAction === 'sales') {
                    $this->getComposerSales($user_data);
                } elseif ($requestedAction === 'downloads') {
                    $this->getDownloads($user_data['id']);
                } elseif ($id && is_numeric($id)) {
                    $this->getOrder($id, $user_data['id']);
                } elseif ($requestedAction === 'order-number' && isset($_GET['order_number'])) {
                    $this->getOrderByOrderNumber($_GET['order_number'], $user_data['id']);
                } else {
                    $this->getUserOrders($user_data['id']);
                }
                break;

            case 'POST':
                if ($requestedAction === 'checkout') {
                    $this->checkout($user_data['id']);
                } elseif ($requestedAction === 'update-payment-status') {
                    $data = json_decode(file_get_contents("php://input"), true);
                    $this->updateOrderPaymentStatus(
                        $data['order_id'] ?? 0,
                        $data['payment_status'] ?? '',
                        $user_data
                    );
                }
                break;

            case 'PUT':
                if ($action === 'cancel' && $id && is_numeric($id)) {
                    $this->cancelOrder((int) $id, $user_data['id']);
                } elseif ($action === 'status' && $id && is_numeric($id)) {
                    $this->updateStatus($id);
                } elseif ($id === 'status' && $action && is_numeric($action)) {
                    $this->updateStatus($action);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    }

    private function getOrderByOrderNumber($order_number, $user_id)
    {
        $order = $this->order->getOrderByOrderNumber($order_number);
        $orderUserId = $order['user_id'] ?? null;

        if ($order && ($orderUserId == $user_id || $this->isAdmin())) {
            http_response_code(200);
            echo json_encode($order);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
        }
    }

    private function updateOrderPaymentStatus($order_id, $payment_status, $user_data)
    {
        $order_id = (int) $order_id;
        $payment_status = strtolower(trim((string) $payment_status));
        $allowed_payment_statuses = ['pending', 'paid', 'failed'];

        if ($order_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid order_id is required']);
            return;
        }

        if (!in_array($payment_status, $allowed_payment_statuses, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payment_status. Allowed: pending, paid, failed']);
            return;
        }

        $order_data = $this->order->getOrderDetails($order_id);
        if (!$order_data) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }

        // Logged-in users can only update their own orders, except admin.
        $is_admin = ($user_data['role'] ?? '') === 'admin';
        if (!$is_admin && (int) ($order_data['user_id'] ?? 0) !== (int) ($user_data['id'] ?? 0)) {
            http_response_code(403);
            echo json_encode(['error' => 'You are not allowed to update this order']);
            return;
        }

        if (!$this->order->updatePaymentStatus($order_id, $payment_status)) {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to update order status']);
            return;
        }

        if ($payment_status === 'paid' && ($order_data['status'] ?? '') !== 'processing') {
            if (!$this->order->updateStatus($order_id, 'processing')) {
                http_response_code(500);
                echo json_encode(['error' => 'Payment updated, but failed to update order status']);
                return;
            }
        }

        http_response_code(200);
        echo json_encode([
            'message' => 'Order payment status updated',
            'order_id' => $order_id,
            'payment_status' => $payment_status
        ]);
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

            // Fetch the created order to get order_number
            $order_data = $this->order->getOrderDetails($order_id);

            http_response_code(201);
            echo json_encode([
                'message' => 'Order created successfully',
                'order_id' => $order_id,
                'order_number' => $order_data['order_number'] ?? '',
                'total_amount' => $order_data['total_amount'] ?? 0,
                'billing_info' => $billing_info
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
        $orderUserId = $order['user_id'] ?? null;

        if ($order && ($orderUserId == $user_id || $this->isAdmin())) {
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

    private function cancelOrder($order_id, $user_id)
    {
        if ($this->order->cancelPendingOrderForUser($order_id, $user_id)) {
            http_response_code(200);
            echo json_encode(['message' => 'Order cancelled successfully']);
            return;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Only your pending orders can be cancelled']);
    }

    private function getDownloads($user_id)
    {
        $downloads = $this->order->getDownloads($user_id);
        http_response_code(200);
        echo json_encode($downloads);
    }

    private function getComposerSales($user_data)
    {
        if ($user_data['role'] !== 'admin' && $user_data['role'] !== 'composer') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin/Composer access required']);
            return;
        }

        $sales = $this->order->getComposerSales($user_data['id']);
        http_response_code(200);
        echo json_encode($sales);
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