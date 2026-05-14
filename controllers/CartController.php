<?php
// controllers/CartController.php
require_once 'BaseController.php';
require_once 'models/Cart.php';
require_once 'models/Product.php'; // Para verificar stock
require_once 'config/config.php';

class CartController extends BaseController
{

    private $cartModel;
    private $productModel;

    public function __construct()
    {
        global $pdo;
        $this->cartModel = new Cart($pdo);
        $this->productModel = new Product($pdo);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function checkout()
    {
        // Iniciar la sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Obtener el identificador de la sesión y los ítems del carrito
        $session_id = session_id();
        $cartItems = $this->cartModel->getCartItems($session_id);

        // Verificar que el carrito no esté vacío
        if (empty($cartItems)) {
            $_SESSION['flash'] = "El carrito está vacío.";
            $_SESSION['flash_type'] = "alert";
            header("Location: " . APP_BASE . "/cart/view");
            exit;
        }

        global $pdo;

        // Calcular el total de la orden
        $total = 0;
        foreach ($cartItems as $item) {
            // Se asume que cada item tiene 'price' y 'quantity'
            $total += $item['price'] * $item['quantity'];
        }

        // Insertar la orden en la tabla orders
        // Se asume que la tabla orders tiene los campos: id, user_id, total, status, order_date (default CURRENT_TIMESTAMP)
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, ?)");
        $user_id = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
        $status = 'completado'; // Compra simulada
        if (!$stmt->execute([$user_id, $total, $status])) {
            $_SESSION['flash'] = "Error al crear la orden.";
            $_SESSION['flash_type'] = "alert";
            header("Location: " . APP_BASE . "/cart/view");
            exit;
        }
        $order_id = $pdo->lastInsertId();

        // Insertar cada ítem del carrito en la tabla order_items y actualizar el stock en products
        foreach ($cartItems as $item) {
            // Insertar ítem en order_items
            $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);

            // Actualizar stock del producto: restar la cantidad vendida
            $stmt_update = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt_update->execute([$item['quantity'], $item['product_id']]);
        }

        // Limpiar el carrito de la sesión
        $this->cartModel->clearCart($session_id);

        $_SESSION['flash'] = "Compra realizada con éxito.";
        $_SESSION['flash_type'] = "success";
        header("Location: " . APP_BASE . "/index.php");
        exit;
    }


    public function add()
    {
        // Si el usuario no está logueado, se envía un mensaje de alerta
        if (!isset($_SESSION['user'])) {
            $_SESSION['flash'] = "Debes iniciar sesión para agregar productos al carrito.";
            $_SESSION['flash_type'] = "alert";
            header("Location: " . APP_BASE . "/index.php");
            exit;
        }

        $session_id = session_id();
        $product_id = $_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

        // Verificar stock
        $product = $this->productModel->getById($product_id);
        if ($quantity > $product['stock']) {
            $error = "No hay suficiente stock disponible. Stock: " . $product['stock'];
            // Redirige al detalle del producto mostrando el error
            require_once __DIR__ . '/ProductController.php';
            $pc = new ProductController();
            $pc->detail($product_id, $error);
            return;
        }

        // Agregar al carrito
        $this->cartModel->addToCart($session_id, $product_id, $quantity);
        // Establecer mensaje flash tipo "cart" para mostrar el toast
        $_SESSION['flash'] = "Artículo agregado al carrito. ¿Desea ver el carrito?";
        $_SESSION['flash_type'] = "cart";
        header("Location: " . APP_BASE . "/index.php");
        exit;
    }

    public function view()
    {
        $session_id = session_id();
        $cartItems = $this->cartModel->getCartItems($session_id);
        $this->render('cart_view', ['cartItems' => $cartItems]);
    }

    public function remove()
    {
        if (isset($_GET['id'])) {
            $this->cartModel->removeFromCart($_GET['id']);
        }
        header("Location: " . APP_BASE . "/cart/view");
    }

    // Método para limpiar el carrito (se invoca al cerrar sesión)
    public function clear()
    {
        $session_id = session_id();
        $this->cartModel->clearCart($session_id);
    }

}

?>