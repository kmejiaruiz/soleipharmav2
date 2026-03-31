<?php
// index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

// Iniciar sesión para el manejador global
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario actual fue forzado a salir por cambio de privilegios
if (isset($_SESSION['user']['id']) && !isset($_SESSION['forcing_logout'])) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT force_logout FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $forceLogout = $stmt->fetchColumn();

    if ($forceLogout) {
        // Apagar la bandera
        $stmtOff = $pdo->prepare("UPDATE users SET force_logout = 0 WHERE id = ?");
        $stmtOff->execute([$_SESSION['user']['id']]);

        // Destruir la sesión controladamente
        $_SESSION['forcing_logout'] = true;
        session_unset();
        session_destroy();

        // Mostrar el modal forzoso en lugar de redirección automática
        require_once 'views/force_logout.php';
        exit;
    }
}

// Determinar ruta de ejecución
$controller = 'product';
$action = 'index';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Parse Friendly URLs if no explicit $_GET['controller'] is passed
if (!isset($_GET['controller'])) {
    // Basic root string parsing, removing query strings
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // Remove base directory (assuming the app lives in /soleipharmav2/)
    $basePath = '/soleipharmav2/';
    if (strpos($requestUri, $basePath) === 0) {
        $route = substr($requestUri, strlen($basePath));
    } else {
        $route = ltrim($requestUri, '/');
    }

    if (!empty($route) && $route !== 'index.php') {
        $parts = explode('/', rtrim($route, '/'));
        
        $controller = !empty($parts[0]) && $parts[0] !== 'index.php' ? strtolower($parts[0]) : 'product';
        $action = isset($parts[1]) && !empty($parts[1]) ? $parts[1] : 'index';
        
        // Asignar ID si existe un 3er parámetro
        if (isset($parts[2]) && !empty($parts[2])) {
            $id = $parts[2];
            // Tambien lo ponemos en $_GET['id'] por si algún controlador viejo lo lee directo
            $_GET['id'] = $id; 
        }
    }
} else {
    // Retrocompatibilidad con el sistema antiguo: index.php?controller=X&action=Y
    $controller = $_GET['controller'];
    $action = isset($_GET['action']) ? $_GET['action'] : 'index';
    $id = isset($_GET['id']) ? $_GET['id'] : null;
}

// Mapear el nombre del controlador a su archivo y clase
$controllerName = ucfirst($controller) . 'Controller';
$controllerFile = "controllers/{$controllerName}.php";

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $obj = new $controllerName();
    if (method_exists($obj, $action)) {
        if ($id !== null) {
            $obj->$action($id);
        } else {
            $obj->$action();
        }
    } else {
        echo "<a href='/soleipharmav2/' role='button'>Regresar</a>";
        die("<br>No se encontró la ruta solicitada ({$controller}/{$action}).");
    }
} else {
    die("El controlador '{$controllerName}' no existe.");
}