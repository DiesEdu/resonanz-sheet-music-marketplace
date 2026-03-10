<?php
// api/controllers/OrderController.php
namespace SheetMusic\Controllers;

use SheetMusic\Config\Database;
use SheetMusic\Models\Order;
use SheetMusic\Models\Cart;
use SheetMusic\Models\SheetMusic;
use SheetMusic\Models\User;
use SheetMusic\Middleware\AuthMiddleware;
use setasign\Fpdi\Fpdi;

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
        $downloadContext = $this->getDownloadRouteContext();

        switch ($method) {
            case 'GET':
                if ($downloadContext) {
                    $this->downloadSheet(
                        $downloadContext['order_id'],
                        $downloadContext['sheet_id'],
                        $user_data
                    );
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

    private function downloadSheet($order_id, $sheet_id, $user_data)
    {
        $order_id = (int) $order_id;
        $sheet_id = (int) $sheet_id;
        $user_id = (int) ($user_data['id'] ?? 0);
        $is_admin = ($user_data['role'] ?? '') === 'admin';

        if ($order_id <= 0 || $sheet_id <= 0 || $user_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid download request']);
            return;
        }

        $order = $this->order->getOrderDetails($order_id);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }

        $orderUserId = (int) ($order['user_id'] ?? 0);
        if (!$is_admin && $orderUserId !== $user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'You are not allowed to download this order']);
            return;
        }

        $isPaid = strtolower((string) ($order['payment_status'] ?? '')) === 'paid';
        if (!$isPaid) {
            http_response_code(403);
            echo json_encode(['error' => 'Payment is not completed for this order']);
            return;
        }

        if (strtolower((string) ($order['status'] ?? '')) === 'cancelled') {
            http_response_code(403);
            echo json_encode(['error' => 'Cancelled orders cannot be downloaded']);
            return;
        }

        $hasItem = false;
        foreach ($order['items'] ?? [] as $item) {
            if ((int) ($item['sheet_id'] ?? 0) === $sheet_id) {
                $hasItem = true;
                break;
            }
        }
        if (!$hasItem) {
            http_response_code(403);
            echo json_encode(['error' => 'This sheet is not part of the order']);
            return;
        }

        // Ensure user has a copyright name before downloading
        $user = new User($this->db);
        $userInfo = $user->getById($user_id);
        $copyrightName = trim((string) ($userInfo['copyright_name'] ?? ''));
        if ($copyrightName === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Please set your copyright name in your profile before downloading.']);
            return;
        }

        $totalPurchasedCopies = $this->order->getPurchasedCopiesForSheetInOrder($order_id, $sheet_id);

        // Get sheet info
        $sheet = new SheetMusic($this->db);
        $sheet_info = $sheet->getById($sheet_id);

        if (!$sheet_info || !$sheet_info['file_path']) {
            error_log("File missing for sheet: $sheet_id");
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
            return;
        }

        // Normalize stored path to avoid duplicated /api prefixes
        $baseDir = realpath(__DIR__ . '/..'); // backend/api
        $filePath = ltrim($sheet_info['file_path'], '/');
        if (strpos($filePath, 'api/') === 0) { // Changed from str_starts_with for PHP < 8.0 compatibility
            $filePath = substr($filePath, 4); // drop leading "api/"
        }
        $fullPath = $baseDir ? realpath($baseDir . '/' . $filePath) : false;

        if (!$fullPath || !file_exists($fullPath)) {
            error_log("File does not exist: " . ($fullPath ?: 'unresolved'));
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
            return;
        }

        // Add copyright text to each page
        try {
            // Check if FPDI class exists
            if (!class_exists(Fpdi::class)) {
                throw new \Exception('FPDI library not loaded');
            }

            $pdf = new Fpdi();
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($fullPath);
            $copyText = ($totalPurchasedCopies == 1) ? 'copy' : 'copies';

            $watermark =
                '© ' .
                date('Y') .
                ' The Resonanz Music Studio. ' .
                'Commissioned for ' . $copyrightName . '. ' .
                $totalPurchasedCopies . ' ' . $copyText;

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

                // Place footer-like copyright
                $pdf->SetFont('Helvetica', 'I', 9);
                $pdf->SetTextColor(120, 120, 120);

                // Calculate text width for centering (optional)
                $textWidth = $pdf->GetStringWidth($watermark);
                $x = ($size['width'] - $textWidth) / 2; // Center the text

                $pdf->SetXY($x, $size['height'] - 12);
                $pdf->Cell($textWidth, 8, $watermark, 0, 0, 'L'); // Added parameters for better control
            }

            // Mark order completed once a download is generated
            if (strtolower((string) ($order['status'] ?? '')) !== 'completed') {
                $this->order->updateStatus($order_id, 'completed');
            }

            // Increment download count AFTER successful PDF generation
            $sheet->incrementDownloads($sheet_id);

            $output = $pdf->Output('S'); // return as string

        } catch (\Throwable $e) {
            error_log('PDF stamping failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());

            // Increment download count even if stamping fails? 
            // If you want to count all downloads regardless of stamping success, move this here
            $sheet->incrementDownloads($sheet_id);

            // Fallback to original file
            $output = file_get_contents($fullPath);
            if ($output === false) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to read file']);
                return;
            }
        }

        // Clean output buffer before sending headers
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Serve file
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');

        // Fix filename - ensure it's properly sanitized
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sheet_info['title']) . '.pdf';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $output;
        exit();
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

    private function getDownloadRouteContext()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/api', '', $path);
        $segments = explode('/', trim($path, '/'));

        if (count($segments) < 4 || $segments[0] !== 'orders') {
            return null;
        }

        if ($segments[3] !== 'download') {
            return null;
        }

        $orderId = $segments[1] ?? null;
        $sheetId = $segments[2] ?? null;

        if (!is_numeric($orderId) || !is_numeric($sheetId)) {
            return null;
        }

        return [
            'order_id' => (int) $orderId,
            'sheet_id' => (int) $sheetId,
        ];
    }
}
?>
