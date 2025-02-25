<?php
// models/Cart.php
require_once 'BaseModel.php';

class Cart extends BaseModel {

    public function addToCart($session_id, $product_id, $quantity = 1) {
        // Verificar si el producto ya existe en el carrito
        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE session_id = ? AND product_id = ?");
        $stmt->execute([$session_id, $product_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $newQuantity = $item['quantity'] + $quantity;
            $update = $this->pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update->execute([$newQuantity, $item['id']]);
        } else {
            $insert = $this->pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert->execute([$session_id, $product_id, $quantity]);
        }
    }

    public function getCartItems($session_id) {
        $stmt = $this->pdo->prepare("SELECT c.*, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.session_id = ?");
        $stmt->execute([$session_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeFromCart($cart_id) {
        $stmt = $this->pdo->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->execute([$cart_id]);
    }

    public function clearCart($session_id) {
        $stmt = $this->pdo->prepare("DELETE FROM cart WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }
}
?>
