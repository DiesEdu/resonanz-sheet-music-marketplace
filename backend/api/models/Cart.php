<?php
// api/models/Cart.php
namespace SheetMusic\Models;

use PDO;

class Cart
{
    private $conn;
    private $table = 'cart';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getOrCreateCart($user_id = null, $session_id = null)
    {
        if ($user_id) {
            $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
        } else {
            $query = "SELECT * FROM " . $this->table . " WHERE session_id = :session_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':session_id', $session_id);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Create new cart
        $query = "INSERT INTO " . $this->table . " (user_id, session_id) VALUES (:user_id, :session_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':session_id', $session_id);

        if ($stmt->execute()) {
            return [
                'id' => $this->conn->lastInsertId(),
                'user_id' => $user_id,
                'session_id' => $session_id
            ];
        }

        return null;
    }

    public function getCartItems($cart_id)
    {
        $query = "SELECT ci.*, s.title, s.composer, s.cover_image, s.file_path,
                         s.price as current_price
                  FROM cart_items ci
                  JOIN sheet_music s ON ci.sheet_id = s.id
                  WHERE ci.cart_id = :cart_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addItem($cart_id, $sheet_id, $quantity = 1)
    {
        $cart_id = (int) $cart_id;
        $sheet_id = (int) $sheet_id;
        $quantity = (int) $quantity;

        if ($cart_id <= 0 || $sheet_id <= 0 || $quantity <= 0) {
            return false;
        }

        // Check if item already exists
        $check_query = "SELECT * FROM cart_items WHERE cart_id = :cart_id AND sheet_id = :sheet_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':cart_id', $cart_id);
        $check_stmt->bindParam(':sheet_id', $sheet_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            // Update quantity
            $item = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $item['quantity'] + $quantity;
            return $this->updateQuantity($cart_id, $sheet_id, $new_quantity);
        } else {
            // Get current price
            $price_query = "SELECT price FROM sheet_music WHERE id = :sheet_id";
            $price_stmt = $this->conn->prepare($price_query);
            $price_stmt->bindParam(':sheet_id', $sheet_id);
            $price_stmt->execute();
            $price_row = $price_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$price_row || !isset($price_row['price'])) {
                return false;
            }
            $price = $price_row['price'];

            // Insert new item
            $query = "INSERT INTO cart_items (cart_id, sheet_id, quantity, price) 
                      VALUES (:cart_id, :sheet_id, :quantity, :price)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cart_id', $cart_id);
            $stmt->bindParam(':sheet_id', $sheet_id);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':price', $price);

            return $stmt->execute();
        }
    }

    public function updateQuantity($cart_id, $sheet_id, $quantity)
    {
        if ($quantity <= 0) {
            return $this->removeItem($cart_id, $sheet_id);
        }

        $query = "UPDATE cart_items SET quantity = :quantity 
                  WHERE cart_id = :cart_id AND sheet_id = :sheet_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->bindParam(':sheet_id', $sheet_id);

        return $stmt->execute();
    }

    public function removeItem($cart_id, $sheet_id)
    {
        $query = "DELETE FROM cart_items WHERE cart_id = :cart_id AND sheet_id = :sheet_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->bindParam(':sheet_id', $sheet_id);

        return $stmt->execute();
    }

    public function clearCart($cart_id)
    {
        $query = "DELETE FROM cart_items WHERE cart_id = :cart_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cart_id', $cart_id);

        return $stmt->execute();
    }

    public function getCartTotal($cart_id)
    {
        $query = "SELECT SUM(quantity * price) as total 
                  FROM cart_items 
                  WHERE cart_id = :cart_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function mergeCarts($session_cart_id, $user_id)
    {
        // Get or create user cart
        $user_cart = $this->getOrCreateCart($user_id, null);

        // Get session cart items
        $session_items = $this->getCartItems($session_cart_id);

        // Move items to user cart
        foreach ($session_items as $item) {
            $this->addItem($user_cart['id'], $item['sheet_id'], $item['quantity']);
        }

        // Delete session cart
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $session_cart_id);
        $stmt->execute();

        return $user_cart;
    }
}
?>
