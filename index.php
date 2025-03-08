<?php
// index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

// Determinar controlador y acción según la URL
$controller = isset($_GET['controller']) ? $_GET['controller'] : 'product';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// Mapear el nombre del controlador a su archivo y clase
$controllerName = ucfirst($controller) . 'Controller';
$controllerFile = "controllers/{$controllerName}.php";

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $obj = new $controllerName();
    if (method_exists($obj, $action)) {
        if (isset($_GET['id'])) {
            $obj->$action($_GET['id']);
        } else {
            $obj->$action();
        }
    } else {
        ?>
        <a href="index.php" role="/button>">Regresar</a>
        <?php
        die("No hay nada por aqui");
    }
} else {
    die("El controlador no existe.");
}
?>