<?php
// api/controllers/PaymentController.php
namespace SheetMusic\Controllers;

use SheetMusic\Config\Database;
use SheetMusic\Models\Order;
use SheetMusic\Middleware\AuthMiddleware;

class PaymentController
{
    private $db;
    private $order;
    private $env;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->order = new Order($this->db);
        $this->env = $this->loadEnv(dirname(__DIR__, 2) . '/.env');
    }

    public function handleRequest($method, $id = null, $action = null)
    {
        $requestedAction = $action ?? $id;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data)) {
            $data = [];
        }

        if ($requestedAction === 'midtrans-status') {
            AuthMiddleware::authenticate();
            $order_id = '';

            if ($method === 'GET') {
                $order_id = trim((string) ($_GET['order_id'] ?? ''));
            } elseif ($method === 'POST') {
                $order_id = trim((string) ($data['order_id'] ?? ''));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            if ($order_id !== '') {
                echo $this->getPaymentStatusMidtrans($order_id);
                return;
            }

            http_response_code(400);
            echo json_encode(['error' => 'order_id is required']);
            return;
        }

        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if ($requestedAction === 'create-intent') {
            $user = AuthMiddleware::authenticate();
            echo $this->createPaymentIntent((int) $user['id'], $data);
            return;
        }

        if ($requestedAction === 'confirm') {
            $user = AuthMiddleware::authenticate();
            echo $this->confirmPayment((int) $user['id'], $data);
            return;
        }

        if ($requestedAction === 'checkout') {
            AuthMiddleware::authenticate();
            echo $this->processCheckout($data);
            return;
        }

        if ($requestedAction === 'webhook') {
            echo $this->handlePaymentWebhook($data);
            return;
        }

        http_response_code(404);
        echo json_encode(['error' => 'Payment action not found']);
    }

    public function createPaymentIntent($user_id, $data)
    {
        $order_id = (int) ($data['order_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        if ($order_id <= 0 || $amount <= 0) {
            http_response_code(400);
            return json_encode(["message" => "Invalid order data."]);
        }

        $order = $this->order->getOrderDetails($order_id);
        if (!$order || (int) ($order['user_id'] ?? 0) !== (int) $user_id) {
            http_response_code(404);
            return json_encode(["message" => "Order not found."]);
        }

        // Demo placeholders. Replace with actual Stripe PaymentIntent in production.
        $client_secret = 'pi_' . uniqid('', true) . '_secret_' . uniqid('', true);
        $payment_intent_id = 'pi_' . uniqid('', true);

        return json_encode([
            "client_secret" => $client_secret,
            "payment_intent_id" => $payment_intent_id,
            "publishable_key" => $this->getEnvValue('STRIPE_PUBLISHABLE_KEY', '')
        ]);
    }

    public function handlePaymentWebhook($payload)
    {
        $event_type = $payload['type'] ?? '';
        $payment_intent = $payload['data']['object'] ?? [];
        $order_id = (int) ($payment_intent['metadata']['order_id'] ?? 0);

        if ($order_id > 0 && $event_type === 'payment_intent.succeeded') {
            $this->order->updatePaymentStatus($order_id, 'paid');
            $this->order->updateStatus($order_id, 'processing');
        }

        if ($order_id > 0 && $event_type === 'payment_intent.payment_failed') {
            $this->order->updatePaymentStatus($order_id, 'failed');
        }

        return json_encode(["message" => "Webhook received."]);
    }

    private function confirmPayment($user_id, $data)
    {
        $order_id = (int) ($data['order_id'] ?? 0);
        $payment_intent_id = (string) ($data['payment_intent_id'] ?? '');

        if ($order_id <= 0 || $payment_intent_id === '') {
            http_response_code(400);
            return json_encode(["message" => "Invalid payment confirmation data."]);
        }

        $order_data = $this->order->getOrderDetails($order_id);
        if (!$order_data || (int) ($order_data['user_id'] ?? 0) !== (int) $user_id) {
            http_response_code(404);
            return json_encode(["message" => "Order not found."]);
        }

        if (($order_data['payment_status'] ?? '') === 'pending') {
            $updatedPayment = $this->order->updatePaymentStatus($order_id, 'paid');
            $updatedStatus = $this->order->updateStatus($order_id, 'processing');

            if ($updatedPayment && $updatedStatus) {
                return json_encode([
                    "message" => "Payment confirmed successfully.",
                    "order_number" => $order_data['order_number'] ?? '',
                    "status" => "processing",
                    "payment_intent_id" => $payment_intent_id
                ]);
            }

            http_response_code(500);
            return json_encode(["message" => "Failed to update payment status."]);
        }

        return json_encode(["message" => "Payment already processed."]);
    }

    private function processCheckout($transactionData)
    {
        $url = $this->getEnvValue('MIDTRANS_BASE_URL', '');
        $server_key = $this->getEnvValue('MIDTRANS_SERVER_KEY', '');

        if ($url === '' || $server_key === '') {
            http_response_code(500);
            return json_encode([
                'error' => 'Missing Midtrans configuration',
                'details' => 'MIDTRANS_BASE_URL and MIDTRANS_SERVER_KEY are required'
            ]);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($transactionData),
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($server_key . ':'),
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $curlError = curl_error($ch);
            http_response_code(500);
            return json_encode([
                'error' => 'cURL error',
                'details' => $curlError,
            ]);
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code === 200 || $http_code === 201) {
            return $response;
        }

        http_response_code($http_code);
        return json_encode([
            'error' => 'Failed to process checkout',
            'details' => json_decode($response, true),
        ]);
    }

    private function getPaymentStatusMidtrans($order_id)
    {
        $url = "https://api.sandbox.midtrans.com/v2/$order_id/status";
        $server_key = $this->getEnvValue('MIDTRANS_SERVER_KEY', '');

        if ($server_key === '') {
            http_response_code(500);
            return json_encode([
                'error' => 'Missing Midtrans configuration',
                'details' => 'MIDTRANS_SERVER_KEY is required'
            ]);
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($server_key . ':'),
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $curlError = curl_error($ch);

            http_response_code(500);
            return json_encode([
                'error' => 'cURL error',
                'details' => $curlError
            ]);
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code === 200) {
            return $response;
        }

        http_response_code($http_code);
        return json_encode([
            'error' => 'Failed to get payment status',
            'details' => json_decode($response, true)
        ]);
    }

    private function loadEnv($path)
    {
        $vars = [];

        if (!file_exists($path)) {
            return $vars;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);

            if (
                strlen($value) >= 2 &&
                (
                    ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                    ($value[0] === "'" && $value[strlen($value) - 1] === "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    private function getEnvValue($key, $default = '')
    {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        if (isset($this->env[$key]) && $this->env[$key] !== '') {
            return $this->env[$key];
        }

        return $default;
    }
}
?>