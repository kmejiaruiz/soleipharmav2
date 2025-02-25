<?php
// controllers/OrderController.php
require_once 'BaseController.php';
require_once 'models/Cart.php';
require_once 'models/Product.php';
require_once 'config/config.php';

class OrderController extends BaseController {
    private $cartModel;
    private $productModel;

    public function __construct() {
        global $pdo;
        $this->cartModel = new Cart($pdo);
        $this->productModel = new Product($pdo);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function checkout() {
        $session_id = session_id();
        $cartItems = $this->cartModel->getCartItems($session_id);
        if (empty($cartItems)) {
            $_SESSION['flash'] = "El carrito está vacío.";
            $_SESSION['flash_type'] = "alert";
            header("Location: index.php");
            exit;
        }
    
        global $pdo;
        // Calcular total de la orden
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    
        // Insertar orden
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, ?)");
        $user_id = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
        $status = 'completado'; // Compra simulada
        $stmt->execute([$user_id, $total, $status]);
        $order_id = $pdo->lastInsertId();
    
        // Insertar ítems y actualizar stock
        foreach ($cartItems as $item) {
            // Insertar ítem de la orden
            $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            // Actualizar stock: restar la cantidad vendida
            $stmt_update = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt_update->execute([$item['quantity'], $item['product_id']]);
        }
    
        // Limpiar el carrito
        $this->cartModel->clearCart($session_id);
    
        $_SESSION['flash'] = "Compra realizada con éxito.";
        $_SESSION['flash_type'] = "success";
        header("Location: index.php");
        exit;
    }
    
}
?>
