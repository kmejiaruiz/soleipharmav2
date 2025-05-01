<?php
// controllers/AuthController.php
require_once 'BaseController.php';
require_once 'models/User.php';
require_once 'config/config.php';

class AuthController extends BaseController
{
    private $userModel;

    public function __construct()
    {
        global $pdo;
        $this->userModel = new User($pdo);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login()
    {
        // Asegurarnos de tener sesión iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si llegamos por POST, intentamos autenticar
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            // Buscamos el usuario por username
            $user = $this->userModel->findByUsername($username);

            if ($user && password_verify($password, $user['password'])) {
                // Construir el nombre completo
                $fullName = trim(
                    ($user['first_name'] ?? '') . ' ' .
                    ($user['second_name'] ?? '') . ' ' .
                    ($user['last_name'] ?? '') . ' ' .
                    ($user['second_surname'] ?? '')
                );

                // Guardar en sesión solo los campos que necesitamos
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'first_name' => $user['first_name'],
                    'second_name' => $user['second_name'],
                    'last_name' => $user['last_name'],
                    'second_surname' => $user['second_surname'],
                    'full_name' => $fullName,
                ];

                // Redirigir a la página principal
                header("Location: index.php");
                exit;
            } else {
                // Falló la autenticación: volvemos a mostrar el modal con el error
                $error = "Credenciales inválidas";
                $this->render('login_modal', ['error' => $error]);
                return;
            }
        }

        // Si no es POST, simplemente abrimos el modal
        $this->render('login_modal');
    }


    public function logout()
    {
        // Limpiar el carrito antes de cerrar sesión
        require_once __DIR__ . '/CartController.php';
        $cartController = new CartController();
        $cartController->clear();

        session_destroy();
        header("Location: index.php");
    }


    // Este metodo esta en desuso, esta desfasado, el nuevo metodo que se usa es registerAjax.
    public function register()
    {
        global $pdo;
        session_start();

        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $pass = $_POST['password'] ?? '';

        // … validaciones …

        // Generación de username
        $base = strtolower(substr($first, 0, 1) . preg_replace('/\s+/', '', $last));
        $username = $base;
        // Si ya existe, usa dos letras del nombre…
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmtCheck->execute([$username]);
        if ($stmtCheck->fetchColumn() > 0) {
            $username = strtolower(substr($first, 0, 2) . preg_replace('/\s+/', '', $last));
        }

        // Inserción
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
      INSERT INTO users (first_name, last_name, username, password, role)
      VALUES (?, ?, ?, ?, 'user')
    ");
        if ($stmt->execute([$first, $last, $username, $hash])) {
            // **Guardamos el username para mostrarlo una sola vez en el modal**
            $_SESSION['registered_username'] = $username;

            $_SESSION['flash'] = "¡Registro exitoso!";
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['flash'] = "Error al registrar usuario.";
            header("Location: index.php?controller=auth&action=showRegister");
            exit;
        }
    }
    // controllers/AuthController.php
    public function registerAjax()
    {
        global $pdo;
        ini_set('display_errors', '0');
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        // 1) Leer campos
        $fn1 = trim($_POST['first_name1'] ?? '');
        $fn2 = trim($_POST['first_name2'] ?? '');
        $ln1 = trim($_POST['last_name1'] ?? '');
        $ln2 = trim($_POST['last_name2'] ?? '');
        $pass = $_POST['password'] ?? '';

        if (!$fn1 || !$fn2 || !$ln1 || !$ln2 || !$pass) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => "Completa todos los campos."]);
            exit;
        }

        // 2) Generar username
        $base = strtolower(substr($fn1, 0, 1) . preg_replace('/\s+/', '', $ln1));
        $username = $base;
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmtCheck->execute([$username]);
        if ($stmtCheck->fetchColumn() > 0) {
            // usar segunda letra del primer nombre
            $secondChar = substr($fn1, 1, 1) ?: substr($fn1, 0, 1);
            $username = strtolower($secondChar . preg_replace('/\s+/', '', $ln1));
        }

        // 3) Insertar usuario
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
      INSERT INTO users
        (first_name, second_name, last_name, second_surname, username, password, role)
      VALUES (?, ?, ?, ?, ?, ?, 'user')
    ");

        try {
            $stmt->execute([$fn1, $fn2, $ln1, $ln2, $username, $hash]);
            ob_clean();
            echo json_encode([
                'success' => true,
                'username' => $username,
                'message' => "Registro exitoso."
            ]);
        } catch (PDOException $e) {
            // Volcar en log y en respuesta para depurar
            error_log("registerAjax fallo: " . $e->getMessage());
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => "Error interno al registrar usuario: " . $e->getMessage()
            ]);
        }
        exit;
    }

}
?>