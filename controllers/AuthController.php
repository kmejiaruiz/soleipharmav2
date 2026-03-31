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
    public function loginForm()
    {
        // En esta app, el login es un modal en el header, pero en caso de acceso directo:
        header("Location: /soleipharmav2/index.php?show_login=1");
        // O renderizamos una mini vista si prefieres, aunque index maneja openLogin
        exit;
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

            if ($user) {
                // Verificar si el usuario está bloqueado
                if ($user['is_locked'] == 1) {
                    $error = "Tu usuario está bloqueado. Por favor, ponte en contacto con un superadmin para reactivarlo.";
                    $this->render('login_modal', ['error' => $error, 'modalAlert' => true]);
                    return;
                }

                if (password_verify($password, $user['password'])) {
                    // --- VERIFICACIÓN DE ESTADO ACTIVO ---
                    if (($user['status'] ?? 'active') === 'disabled') {
                        $error = "Póngase en contacto con su administrador de TI ya que ocurrió un problema.";
                        $this->render('login_modal', ['error' => $error, 'modalAlert' => true]);
                        return;
                    }

                    // --- RESTRICCIÓN DE SUCURSAL ---
                    $userBranch = $user['branch'] ?? '';
                    $sysBranch = defined('BRANCH') ? BRANCH : '';
                    
                    $normalize = function($str) {
                        $unwanted_array = ['Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' ];
                        return strtolower(strtr(trim($str), $unwanted_array));
                    };

                    $cleanUserBranch = $normalize($userBranch);
                    $cleanSysBranch = $normalize($sysBranch);

                    if ($cleanSysBranch !== '' && $cleanUserBranch !== '' && $cleanUserBranch !== $cleanSysBranch) {
                        $error = "No tienes privilegios para iniciar sesión. Esta instancia pertenece a " . htmlspecialchars($sysBranch) . ", pero tu cuenta está registrada en " . htmlspecialchars($userBranch) . ".";
                        $this->render('login_modal', ['error' => $error, 'modalAlert' => true]);
                        return;
                    }
                    // --- FIN RESTRICCIÓN DE SUCURSAL ---

                    // Reiniciar intentos fallidos al tener éxito
                    $this->userModel->resetFailedAttempts($username);

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
                    'branch' => $user['branch'] ?? '',
                ];

                    // Redirigir a la página principal
                    header("Location: /soleipharmav2/product/index");
                    exit;
                } else {
                    // Contraseña incorrecta, incrementamos intentos
                    $this->userModel->incrementFailedAttempts($username);
                    $attempts = $user['failed_login_attempts'] + 1;
                    
                    if ($attempts >= 3) {
                        $this->userModel->lockUser($username);
                        $error = "Tu usuario está bloqueado. Por favor, ponte en contacto con un superadmin para reactivarlo.";
                        $this->render('login_modal', ['error' => $error, 'modalAlert' => true]);
                        return;
                    } else {
                        $error = "Contraseña incorrecta. Si se ingresa 3 veces la contraseña incorrecta el usuario se bloquea. (Llevas {$attempts} intentos fallidos).";
                        $this->render('login_modal', ['error' => $error, 'modalAlert' => true]);
                        return;
                    }
                }
            } else {
                // El usuario no existe en la base de datos
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
        header("Location: /soleipharmav2/product/index");
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
            header("Location: /soleipharmav2/product/index");
            exit;
        } else {
            $_SESSION['flash'] = "Error al registrar usuario.";
            header("Location: /soleipharmav2/auth/showRegister");
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


    // ── Recuperación de contraseña ──────────────────────────────

    /**
     * POST: recibe 'username', genera token y devuelve JSON con el enlace de recuperación.
     */
    public function requestReset()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE) session_start();

        $username = trim($_POST['username'] ?? '');
        if (!$username) {
            echo json_encode(['success' => false, 'message' => 'Ingresa tu nombre de usuario.']);
            exit;
        }

        $token = $this->userModel->createResetToken($username);
        if (!$token) {
            // No revelamos si el usuario existe o no por seguridad, pero aquí
            // la app no tiene correo, así que informamos al admin/usuario directamente.
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
            exit;
        }

        // Construir URL limpia (controller y action ocultos al usuario)
        $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        $base     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $resetUrl = $scheme . '://' . $host . $base . '/recuperar-contrasena/' . $token;

        echo json_encode(['success' => true, 'reset_url' => $resetUrl]);
        exit;
    }

    /**
     * GET : muestra el formulario de nueva contraseña (valida el token).
     * POST: actualiza la contraseña.
     */
    public function resetPassword()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $token = trim($_GET['token'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // --- Guardar nueva contraseña ---
            header('Content-Type: application/json; charset=utf-8');
            $postToken   = trim($_POST['token']        ?? '');
            $username    = trim($_POST['username']     ?? '');
            $newPassword = $_POST['new_password']      ?? '';
            $confirm     = $_POST['confirm_password']  ?? '';

            if (!$postToken || !$username || !$newPassword || !$confirm) {
                echo json_encode(['success' => false, 'message' => 'Completa todos los campos.']);
                exit;
            }
            if ($newPassword !== $confirm) {
                echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden.']);
                exit;
            }
            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
                exit;
            }

            // Verificar que el token pertenece al username indicado
            $tokenUsername = $this->userModel->validateResetToken($postToken);
            if (!$tokenUsername || $tokenUsername !== $username) {
                echo json_encode(['success' => false, 'message' => 'Enlace inválido o expirado.']);
                exit;
            }

            $ok = $this->userModel->resetPassword($postToken, $newPassword);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => '¡Contraseña actualizada! Ya puedes iniciar sesión.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña.']);
            }
            exit;
        }

        // --- GET: mostrar formulario ---
        if (!$token) {
            header('Location: index.php');
            exit;
        }

        // Validar token antes de mostrar el form
        $tokenUsername = $this->userModel->validateResetToken($token);
        if (!$tokenUsername) {
            $error = 'El enlace de recuperación es inválido o ha expirado.';
            $this->renderBare('reset_password', ['token' => '', 'tokenError' => $error]);
            return;
        }

        $this->renderBare('reset_password', ['token' => $token, 'tokenUsername' => $tokenUsername]);
    }
}
?>
