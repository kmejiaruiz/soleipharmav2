<?php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmacy');
define('DB_USER', 'root');
define('DB_PASS', '');

// Información de la empresa para el recibo
define('COMPANY_NAME', 'Farmacia Solei.');
define('BRANCH', 'Sucursal Leon');

define('LOW_STOCK_THRESHOLD', 9);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>