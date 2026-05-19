<?php
// index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── Detección de primera ejecución ──────────────────────────────────────────
// Si aún no existe config/app.json, mostrar el wizard de configuración inicial.
if (!file_exists(__DIR__ . '/config/app.json')) {
    require_once __DIR__ . '/controllers/SetupController.php';

    // Resolver la acción desde query string o desde URL amigable
    // Soporta: ?action=X  o  /setup/X
    $setupAction = $_GET['action'] ?? '';
    if ($setupAction === '') {
        $uri   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $parts = explode('/', rtrim($uri, '/'));
        // /soleipharmav2/setup/testConnection  → partes[3]
        // /soleipharmav2/index.php             → sin acción
        foreach ($parts as $i => $part) {
            if (strtolower($part) === 'setup' && isset($parts[$i + 1])) {
                $setupAction = $parts[$i + 1];
                break;
            }
        }
    }

    $setup = new SetupController();
    match ($setupAction) {
        'testConnection' => $setup->testConnection(),
        'save'           => $setup->save(),
        default          => $setup->index(),
    };
    exit;
}
// ── Fin detección de primera ejecución ─────────────────────────────────────


require_once 'config/config.php';

// ── Detectar fallo de conexión a la BD ───────────────────────────────────────
// Si config.php cargó app.json pero no pudo conectar (ej: BD eliminada,
// servidor nuevo, credenciales cambiadas), redirigir al wizard de setup.
if (!empty($GLOBALS['_setup_required'])) {
    $appJsonPath = __DIR__ . '/config/app.json';
    $errorMsg    = $GLOBALS['_setup_error'] ?? 'Error de conexión a la base de datos.';

    // Guardar el error en sesión para mostrarlo en el wizard
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['setup_db_error'] = $errorMsg;

    // Eliminar el app.json inválido para que el wizard arranque limpio
    if (file_exists($appJsonPath)) {
        @unlink($appJsonPath);
    }

    // Redirigir al wizard de configuración inicial
    $appBase = '/' . basename(__DIR__);
    header('Location: ' . $appBase . '/');
    exit;
}
// ── Fin detección de fallo de BD ────────────────────────────────────────────


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
    // Detectar el basePath dinámicamente según el nombre de la carpeta del proyecto
    // Funciona para /soleipharmav2/, /soleipharmav2leon/, o cualquier otro nombre
    $appFolder = basename(__DIR__);           // ej: "soleipharmav2" o "soleipharmav2leon"
    $basePath  = '/' . $appFolder . '/';

    if (strpos($requestUri, $basePath) === 0) {
        $route = substr($requestUri, strlen($basePath));
    } else {
        $route = ltrim($requestUri, '/');
        // Quitar el folder si aparece como primer segmento (evita tomarlo como controlador)
        if (strpos($route, $appFolder . '/') === 0) {
            $route = substr($route, strlen($appFolder) + 1);
        }
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
        $appFolder = basename(__DIR__);
        echo "<a href='/{$appFolder}/' role='button'>Regresar</a>";
        die("<br>No se encontró la ruta solicitada ({$controller}/{$action}).");
    }
} else {
    $appFolder = basename(__DIR__);
    echo "<a href='/{$appFolder}/' role='button'>Regresar</a>";
    die("<br>El controlador '{$controllerName}' no existe.");
}