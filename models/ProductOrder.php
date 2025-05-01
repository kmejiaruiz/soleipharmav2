<?php
// models/ProductOrder.php
require_once 'BaseModel.php';

class ProductOrder extends BaseModel
{
    // Crea un pedido y retorna el id generado o false en caso de error.
    public function createOrder($admin_id, $admin_name)
    {
        $stmt = $this->pdo->prepare("INSERT INTO product_orders (admin_id, admin_name) VALUES (?, ?)");
        if ($stmt->execute([$admin_id, $admin_name])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    // Agrega un ítem al pedido.
    public function addOrderItem($order_id, $product_id, $quantity)
    {
        $stmt = $this->pdo->prepare("INSERT INTO product_order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
        return $stmt->execute([$order_id, $product_id, $quantity]);
    }
}
?>