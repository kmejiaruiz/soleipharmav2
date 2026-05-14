<?php
// controllers/SetupController.php

class SetupController
{
    // ── Constantes internas ────────────────────────────────────────────────
    private string $appJsonPath;
    private string $configPhpPath;
    private string $dbRootPath;

    public function __construct()
    {
        $root = dirname(__DIR__);
        $this->appJsonPath   = $root . '/config/app.json';
        $this->configPhpPath = $root . '/config/config.php';
        $this->dbRootPath    = $root . '/database';
    }

    // ── index ──────────────────────────────────────────────────────────────
    /** Renderiza el wizard (standalone, sin header/footer del proyecto). */
    public function index(): void
    {
        if (file_exists($this->appJsonPath)) {
            // Detectar la base dinámica: /soleipharmav2 o /soleipharmav2leon
            $parts = explode('/', trim($_SERVER['REQUEST_URI'] ?? '', '/'));
            $base  = '/' . ($parts[0] ?? '');
            header('Location: ' . $base . '/');
            exit;
        }
        require_once dirname(__DIR__) . '/views/setup_wizard.php';
        exit;
    }

    // ── testConnection ─────────────────────────────────────────────────────
    /** POST AJAX: prueba credenciales de BD. Devuelve JSON. */
    public function testConnection(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $host = trim($_POST['db_host'] ?? 'localhost');
        $port = trim($_POST['db_port'] ?? '3306');
        $user = trim($_POST['db_user'] ?? '');
        $pass = $_POST['db_pass'] ?? '';

        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
            echo json_encode(['success' => true, 'message' => "Conexión exitosa (MySQL {$ver})"]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ── save ───────────────────────────────────────────────────────────────
    /** POST AJAX: valida, crea BD, migra, crea superadmin, escribe app.json. */
    public function save(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_start();

        // 1) Recoger datos del formulario
        $dbHost     = trim($_POST['db_host']       ?? 'localhost');
        $dbPort     = trim($_POST['db_port']       ?? '3306');
        $dbName     = trim($_POST['db_name']       ?? '');
        $dbUser     = trim($_POST['db_user']       ?? '');
        $dbPass     = $_POST['db_pass']            ?? '';
        $companyName= trim($_POST['company_name']  ?? '');
        $branch     = trim($_POST['branch']        ?? '');
        $timezone   = trim($_POST['timezone']      ?? 'America/Managua');
        $lowStock   = (int) ($_POST['low_stock']   ?? 9);
        $adminFn    = trim($_POST['admin_fn']      ?? '');
        $adminLn    = trim($_POST['admin_ln']      ?? '');
        $adminUser  = trim($_POST['admin_user']    ?? '');
        $adminPass  = $_POST['admin_pass']         ?? '';

        // 2) Validaciones básicas
        $required = compact('dbHost', 'dbPort', 'dbName', 'dbUser', 'companyName', 'branch', 'adminFn', 'adminLn', 'adminUser', 'adminPass');
        foreach ($required as $key => $val) {
            if ($val === '') {
                ob_clean();
                echo json_encode(['success' => false, 'message' => "El campo '{$key}' es requerido."]);
                exit;
            }
        }
        if (strlen($adminPass) < 6) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'La contraseña del superadmin debe tener al menos 6 caracteres.']);
            exit;
        }

        // 3) Conectar al servidor MySQL (sin seleccionar BD todavía)
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'No se pudo conectar al servidor MySQL: ' . $e->getMessage()]);
            exit;
        }

        // 4) Crear la base de datos si no existe
        try {
            $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$safeName}`");
        } catch (PDOException $e) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Error al crear la base de datos: ' . $e->getMessage()]);
            exit;
        }

        // 5) Ejecutar migraciones
        $migrationErrors = [];

        // 5a) SQL maestro principal
        $masterSql = $this->dbRootPath . '/migration_pharmacy_20260331.sql';
        if (file_exists($masterSql)) {
            $errors = $this->runSqlFile($pdo, $masterSql);
            $migrationErrors = array_merge($migrationErrors, $errors);
        }

        // 5b) Migraciones individuales (en orden numérico)
        $migrationsDir = $this->dbRootPath . '/migrations';
        if (is_dir($migrationsDir)) {
            $files = glob($migrationsDir . '/*.sql');
            natsort($files);
            foreach ($files as $file) {
                $errors = $this->runSqlFile($pdo, $file);
                $migrationErrors = array_merge($migrationErrors, $errors);
            }
        }

        // 6) Crear el primer usuario superadmin
        try {
            // Verificar si ya existe ese username
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$adminUser]);
            if ($stmt->fetchColumn() == 0) {
                $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (first_name, second_name, last_name, second_surname, username, password, role, branch, status)
                    VALUES (?, '', ?, '', ?, ?, 'superadmin', ?, 'active')
                ");
                $stmt->execute([$adminFn, $adminLn, $adminUser, $hash, $branch]);
            }
        } catch (PDOException $e) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Error al crear el superadmin: ' . $e->getMessage()]);
            exit;
        }

        // 7) Escribir config/app.json
        $config = [
            'db_host'      => $dbHost,
            'db_port'      => $dbPort,
            'db_name'      => $dbName,
            'db_user'      => $dbUser,
            'db_pass'      => $dbPass,
            'company_name' => $companyName,
            'branch'       => $branch,
            'timezone'     => $timezone,
            'low_stock'    => $lowStock,
            'setup_at'     => date('Y-m-d H:i:s'),
        ];

        $written = file_put_contents(
            $this->appJsonPath,
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        if ($written === false) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'No se pudo escribir config/app.json. Verifica permisos de escritura.']);
            exit;
        }

        ob_clean();
        echo json_encode([
            'success'          => true,
            'message'          => '¡Sistema inicializado correctamente!',
            'migration_errors' => $migrationErrors,
        ]);
        exit;
    }

    // ── Helper: ejecutar un archivo SQL ───────────────────────────────────
    private function runSqlFile(PDO $pdo, string $filePath): array
    {
        $errors = [];

        // Detectar si el archivo es UTF-16 LE (BOM: FF FE) y convertir
        $raw = file_get_contents($filePath);
        if (substr($raw, 0, 2) === "\xFF\xFE") {
            $raw = mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');
        }

        // Dividir en sentencias individuales
        $statements = $this->splitSql($raw);

        foreach ($statements as $sql) {
            $sql = trim($sql);
            if ($sql === '' || stripos($sql, '--') === 0) continue;
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                // Ignorar "table already exists" y errores similares no fatales
                $code = $e->errorInfo[1] ?? 0;
                if (!in_array($code, [1050, 1060, 1061, 1062, 1091])) {
                    $errors[] = basename($filePath) . ': ' . $e->getMessage();
                }
            }
        }

        return $errors;
    }

    /** Divide un string SQL en sentencias individuales respetando delimitadores. */
    private function splitSql(string $sql): array
    {
        // Eliminar comentarios de bloque /* ... */
        $sql = preg_replace('#/\*.*?\*/#s', '', $sql);
        // Eliminar comentarios de línea --
        $sql = preg_replace('/--[^\n]*/', '', $sql);

        $statements = [];
        $buffer     = '';
        $delimiter  = ';';
        $lines      = preg_split('/\r\n|\n|\r/', $sql);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Soporte básico para DELIMITER (para triggers/procedures)
            if (stripos($trimmed, 'DELIMITER') === 0) {
                $parts     = preg_split('/\s+/', $trimmed);
                $delimiter = $parts[1] ?? ';';
                continue;
            }

            $buffer .= $line . "\n";

            if (str_ends_with(rtrim($trimmed), $delimiter)) {
                $stmt = substr(trim($buffer), 0, -strlen($delimiter));
                if ($stmt !== '') {
                    $statements[] = $stmt;
                }
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }
}
?>
