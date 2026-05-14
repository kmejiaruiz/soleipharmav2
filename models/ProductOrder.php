<?php
// models/ProductOrder.php
require_once 'BaseModel.php';

class ProductOrder extends BaseModel
{
    // Crea un pedido y retorna el id generado o false en caso de error.
    public function createOrder($admin_id, $admin_name, $branch, $total = 0.00)
    {
        $stmt = $this->pdo->prepare("INSERT INTO product_orders (admin_id, admin_name, branch, total) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$admin_id, $admin_name, $branch, $total])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    // Agrega un ítem al pedido.
    public function addOrderItem($order_id, $product_id, $quantity, $price)
    {
        $stmt = $this->pdo->prepare("INSERT INTO product_order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$order_id, $product_id, $quantity, $price]);
    }
    
    public function updateOrderTotal($order_id, $total)
    {
        $stmt = $this->pdo->prepare("UPDATE product_orders SET total = ? WHERE id = ?");
        return $stmt->execute([$total, $order_id]);
    }
}
?>