<?php
require 'config/config.php';
$cols = $pdo->query("SHOW COLUMNS FROM product_orders")->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $cols) . PHP_EOL;
