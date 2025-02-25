<?php
// controllers/AuthController.php
require_once 'BaseController.php';
require_once 'models/User.php';
require_once 'config/config.php';

class AuthController extends BaseController {
    private $userModel;

    public function __construct() {
        global $pdo;
        $this->userModel = new User($pdo);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            $user = $this->userModel->findByUsername($username);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;
                header("Location: index.php");
                exit;
            } else {
                $error = "Credenciales inválidas";
                // Renderiza el modal de login con error (se mantiene abierto)
                $this->render('login_modal', ['error' => $error]);
                return;
            }
        } else {
            $this->render('login_modal');
        }
    }

    public function logout() {
        // Limpiar el carrito antes de cerrar sesión
        require_once __DIR__ . '/CartController.php';
        $cartController = new CartController();
        $cartController->clear();
        
        session_destroy();
        header("Location: index.php");
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            if ($this->userModel->findByUsername($username)) {
                $error = "El usuario ya existe.";
            } else {
                $this->userModel->create($username, $password);
                $_SESSION['flash'] = "¡Te has registrado con éxito!";
                $_SESSION['flash_type'] = "success";
                header("Location: index.php");
                exit;
            }
        }
        $this->render('register_modal', ['error' => $error ?? null]);
    }
}
?>
