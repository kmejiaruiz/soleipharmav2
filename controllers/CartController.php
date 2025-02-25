<?php
// controllers/CartController.php
require_once 'BaseController.php';
require_once 'models/Cart.php';
require_once 'models/Product.php'; // Para verificar stock
require_once 'config/config.php';

class CartController extends BaseController {

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

    public function add() {
        // Si el usuario no está logueado, se envía un mensaje de alerta
        if (!isset($_SESSION['user'])) {
            $_SESSION['flash'] = "Debes iniciar sesión para agregar productos al carrito.";
            $_SESSION['flash_type'] = "alert";
            header("Location: index.php");
            exit;
        }
        
        $session_id = session_id();
        $product_id = $_POST['product_id'];
        $quantity   = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
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
        header("Location: index.php");
        exit;
    }
    
    public function view() {
        $session_id = session_id();
        $cartItems = $this->cartModel->getCartItems($session_id);
        $this->render('cart_view', ['cartItems' => $cartItems]);
    }
    
    public function remove() {
        if (isset($_GET['id'])) {
            $this->cartModel->removeFromCart($_GET['id']);
        }
        header("Location: index.php?controller=cart&action=view");
    }
    
    // Método para limpiar el carrito (se invoca al cerrar sesión)
    public function clear() {
        $session_id = session_id();
        $this->cartModel->clearCart($session_id);
    }
}
?>
