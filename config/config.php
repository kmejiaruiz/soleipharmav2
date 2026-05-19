<?php
// config/config.php
// Lee la configuración desde config/app.json si existe.
// Si no existe, el wizard de configuración inicial tomará el control antes
// de que este archivo sea relevante para la conexión a la BD.

$_appJsonPath = __DIR__ . '/app.json';

if (file_exists($_appJsonPath)) {
    // ── Cargar configuración guardada ────────────────────────────────────
    $_cfg = json_decode(file_get_contents($_appJsonPath), true) ?? [];

    $tz = $_cfg['timezone'] ?? 'America/Managua';
    date_default_timezone_set($tz);

    define('DB_HOST', $_cfg['db_host'] ?? 'localhost');
    define('DB_PORT', $_cfg['db_port'] ?? '3306');
    define('DB_NAME', $_cfg['db_name'] ?? 'pharmacy');
    define('DB_USER', $_cfg['db_user'] ?? 'root');
    define('DB_PASS', $_cfg['db_pass'] ?? '');
    define('COMPANY_NAME', $_cfg['company_name'] ?? 'Farmacia Solei');
    define('BRANCH', $_cfg['branch'] ?? '');
    define('LOW_STOCK_THRESHOLD', (int) ($_cfg['low_stock'] ?? 9));
    // Base URL dinámica — funciona para /soleipharmav2/ y /soleipharmav2leon/
    define('APP_BASE', '/' . basename(dirname(__DIR__)));

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        // No morir con error crudo — señalizar que se necesita configuración.
        // El index.php detectará esto y redirigirá al wizard de setup.
        $pdo = null;
        $GLOBALS['_setup_required'] = true;
        $GLOBALS['_setup_error'] = $e->getMessage();
    }

    unset($_cfg, $_appJsonPath);
} else {
    // ── No hay configuración todavía — el wizard se encargará ───────────
    // Define constantes con valores neutros para no romper includes que
    // verifiquen si están definidas, pero NO abre conexión PDO.
    date_default_timezone_set('America/Managua');

    if (!defined('DB_HOST'))
        define('DB_HOST', '');
    if (!defined('DB_PORT'))
        define('DB_PORT', '3306');
    if (!defined('DB_NAME'))
        define('DB_NAME', '');
    if (!defined('DB_USER'))
        define('DB_USER', '');
    if (!defined('DB_PASS'))
        define('DB_PASS', '');
    if (!defined('COMPANY_NAME'))
        define('COMPANY_NAME', 'Farmacia Solei');
    if (!defined('BRANCH'))
        define('BRANCH', '');
    if (!defined('LOW_STOCK_THRESHOLD'))
        define('LOW_STOCK_THRESHOLD', 9);
    if (!defined('APP_BASE'))
        define('APP_BASE', '/' . basename(dirname(__DIR__)));

    $pdo = null; // No hay conexión disponible aún
    unset($_appJsonPath);
}
?>