<?php
// database/migrate_password_resets.php
// Ejecutar una sola vez: php database/migrate_password_resets.php

require_once __DIR__ . '/../config/config.php';

$sql = "
CREATE TABLE IF NOT EXISTS password_resets (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username  VARCHAR(100) NOT NULL,
    token     VARCHAR(64)  NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    used       TINYINT(1)  DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sql);
    echo "Tabla 'password_resets' creada (o ya existía).\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
