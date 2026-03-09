<?php
// api/models/Order.php
namespace SheetMusic\Models;

use PDO;

class Order
{
    private $conn;
    private $table = 'orders';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($user_id, $cart_items, $billing_info)
    {
        try {
            $this->conn->beginTransaction();

            // Generate unique order number
            $order_number = 'ORD-' . strtoupper(uniqid()) . '-' . date('Ymd');

            // Calculate total
            $total = 0;
            foreach ($cart_items as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            // Create order
            $query = "INSERT INTO " . $this->table . "
                      (order_number, user_id, total_amount, billing_name, billing_email, 
                       billing_phone, billing_address, notes)
                      VALUES (:order_number, :user_id, :total_amount, :billing_name, 
                              :billing_email, :billing_phone, :billing_address, :notes)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_number', $order_number);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':total_amount', $total);
            $stmt->bindParam(':billing_name', $billing_info['name']);
            $stmt->bindParam(':billing_email', $billing_info['email']);
            $stmt->bindParam(':billing_phone', $billing_info['phone']);
            $stmt->bindParam(':billing_address', $billing_info['address']);
            $stmt->bindParam(':notes', $billing_info['notes']);

            if (!$stmt->execute()) {
                throw new \Exception("Failed to create order");
            }

            $order_id = $this->conn->lastInsertId();

            // Create order items
            foreach ($cart_items as $item) {
                $item_query = "INSERT INTO order_items (order_id, sheet_id, quantity, price, file_path)
                               VALUES (:order_id, :sheet_id, :quantity, :price, :file_path)";

                $item_stmt = $this->conn->prepare($item_query);
                $item_stmt->bindParam(':order_id', $order_id);
                $item_stmt->bindParam(':sheet_id', $item['sheet_id']);
                $item_stmt->bindParam(':quantity', $item['quantity']);
                $item_stmt->bindParam(':price', $item['price']);
                $item_stmt->bindParam(':file_path', $item['file_path']);

                if (!$item_stmt->execute()) {
                    throw new \Exception("Failed to create order item");
                }
            }

            $this->conn->commit();
            return $order_id;

        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getUserOrders($user_id)
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getOrderByOrderNumber($order_number)
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE order_number = :order_number";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_number', $order_number);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrderDetails($order_id)
    {
        $query = "SELECT o.*, 
                         oi.id as item_id, oi.sheet_id, oi.quantity, oi.price as item_price,
                         s.title, s.composer, s.file_path
                  FROM " . $this->table . " o
                  LEFT JOIN order_items oi ON o.id = oi.order_id
                  LEFT JOIN sheet_music s ON oi.sheet_id = s.id
                  WHERE o.id = :order_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();

        $order = null;
        $items = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!$order) {
                $order = [
                    'id' => $row['id'],
                    'user_id' => $row['user_id'],
                    'order_number' => $row['order_number'],
                    'total_amount' => $row['total_amount'],
                    'status' => $row['status'],
                    'payment_status' => $row['payment_status'],
                    'billing_name' => $row['billing_name'],
                    'billing_email' => $row['billing_email'],
                    'created_at' => $row['created_at'],
                    'items' => []
                ];
            }

            if ($row['item_id']) {
                $order['items'][] = [
                    'id' => $row['item_id'],
                    'sheet_id' => $row['sheet_id'],
                    'title' => $row['title'],
                    'composer' => $row['composer'],
                    'quantity' => $row['quantity'],
                    'price' => $row['item_price'],
                    'file_path' => $row['file_path']
                ];
            }
        }

        return $order;
    }

    public function updateStatus($order_id, $status)
    {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':order_id', $order_id);
        return $stmt->execute();
    }

    public function updatePaymentStatus($order_id, $payment_status)
    {
        $query = "UPDATE " . $this->table . " SET payment_status = :payment_status WHERE id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':payment_status', $payment_status);
        $stmt->bindParam(':order_id', $order_id);
        return $stmt->execute();
    }

    public function cancelPendingOrderForUser($order_id, $user_id)
    {
        $query = "UPDATE " . $this->table . "
                  SET status = 'cancelled', payment_status = 'failed'
                  WHERE id = :order_id
                    AND user_id = :user_id
                    AND status = 'pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getDownloads($user_id)
    {
        $query = "SELECT DISTINCT s.*, o.created_at as purchase_date, o.order_number
                  FROM order_items oi
                  JOIN orders o ON oi.order_id = o.id
                  JOIN sheet_music s ON oi.sheet_id = s.id
                  WHERE o.user_id = :user_id AND o.payment_status = 'paid'
                  ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getComposerSales($composer_id)
    {
        $query = "SELECT oi.id as order_item_id,
                         oi.sheet_id,
                         oi.quantity,
                         oi.price as sale_price,
                         o.id as order_id,
                         o.order_number,
                         o.user_id as buyer_id,
                         o.created_at as purchase_date,
                         s.title,
                         s.composer,
                         s.instrument_id,
                         s.created_by
                  FROM order_items oi
                  JOIN orders o ON oi.order_id = o.id
                  JOIN sheet_music s ON oi.sheet_id = s.id
                  WHERE s.created_by = :composer_id
                  AND o.payment_status = 'paid'
                  ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':composer_id', $composer_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verifyPurchase($user_id, $sheet_id)
    {
        $query = "SELECT COUNT(*) as count
                  FROM order_items oi
                  JOIN orders o ON oi.order_id = o.id
                  WHERE o.user_id = :user_id 
                  AND oi.sheet_id = :sheet_id 
                  AND o.payment_status = 'paid'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':sheet_id', $sheet_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}
?>