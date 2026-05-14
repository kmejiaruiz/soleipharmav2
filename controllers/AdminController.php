<?php
// controllers/AdminController.php
require_once 'BaseController.php';
require_once 'models/Product.php';
require_once 'config/config.php';

class AdminController extends BaseController
{
    private $productModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Solo se permite acceso si el usuario es admin
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'superadmin')) {
            ?>
            <a role="button" href="index.php">Regresar</a> <br>
            <hr>
            <?php
            die("No hay nada por aqui");
        }
        global $pdo;
        $this->productModel = new Product($pdo);
    }

    // Dashboard principal del admin (con AdminLTE)
    public function index()
    {
        global $pdo;
        // Ventas del día
        $stmtSales = $pdo->prepare(
            "SELECT SUM(total) AS total_sales 
             FROM orders 
             WHERE DATE(created_at)=CURDATE() AND status='completado'"
        );
        $stmtSales->execute();
        $dailySales = $stmtSales->fetchColumn() ?: 0;

        // Top 10 productos más vendidos
        $topProducts = $pdo->query(
            "SELECT p.id,p.name,SUM(oi.quantity) AS total_quantity 
             FROM order_items oi 
             JOIN products p ON oi.product_id=p.id 
             GROUP BY p.id 
             ORDER BY total_quantity DESC 
             LIMIT 10"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Productos con bajo stock (filtrado por sucursal)
        $currentBranch = defined('BRANCH') && BRANCH !== '' ? BRANCH : '';
        $lowStockProducts = $pdo->prepare(
            "SELECT p.id, p.name, COALESCE(bps.stock, 0) AS stock
             FROM products p
             LEFT JOIN branch_product_stock bps
                    ON bps.product_id = p.id AND bps.branch = ?
             WHERE COALESCE(bps.stock, 0) < 5 AND p.available = 1"
        );
        $lowStockProducts->execute([$currentBranch]);
        $lowStockProducts = $lowStockProducts->fetchAll(PDO::FETCH_ASSOC);


        // *** Productos para el panel (stock de la sucursal actual) ***
        $currentBranch = defined('BRANCH') && BRANCH !== '' ? BRANCH : '';
        $products = $pdo->query(
            "SELECT p.id, p.name, p.cost, p.sale_price,
                    COALESCE(bps.stock, 0) AS stock,
                    p.available
             FROM products p
             LEFT JOIN branch_product_stock bps
                    ON bps.product_id = p.id AND bps.branch = '" . addslashes($currentBranch) . "'"
        )->fetchAll(PDO::FETCH_ASSOC);


        // Alertas de usuarios bloqueados (solo para superadmin)
        $lockedUsersAlerts = [];
        $unreadNotifications = [];
        if (isset($_SESSION['user']['role'])) {
            require_once 'models/Notification.php';
            $notifModel = new Notification($pdo);
            $unreadNotifications = $notifModel->getUnread($_SESSION['user']['id']);

            if ($_SESSION['user']['role'] === 'superadmin') {
                require_once 'models/User.php';
                $userModel = new User($pdo);
                $lockedUsersAlerts = $userModel->getLockedUsers();
            }
        }

        // Traslados entre sucursales pendientes para ESTA sucursal
        $pendingTransfers = [];
        if (!empty($currentBranch) && in_array($_SESSION['user']['role'] ?? '', ['admin', 'superadmin'])) {
            $stmtPT = $pdo->prepare("
                SELECT bt.id, bt.from_branch, bt.created_at,
                       COUNT(bti.id) AS item_count,
                       SUM(bti.quantity_sent) AS total_units,
                       CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS creator_name
                FROM branch_transfers bt
                JOIN branch_transfer_items bti ON bti.transfer_id = bt.id
                JOIN users u ON u.id = bt.created_by
                WHERE bt.to_branch = ? AND bt.status = 'pendiente'
                GROUP BY bt.id
                ORDER BY bt.created_at DESC
            ");
            $stmtPT->execute([$currentBranch]);
            $pendingTransfers = $stmtPT->fetchAll(PDO::FETCH_ASSOC);
        }
        $roleUpgradeData = null;
        if (isset($_SESSION['user']['id'])) {
            $stmtUpg = $pdo->prepare("SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as admin_name FROM role_change_logs r JOIN users u ON r.changed_by = u.id WHERE r.target_user = ? AND r.acknowledged = 0 ORDER BY r.id DESC LIMIT 1");
            $stmtUpg->execute([$_SESSION['user']['id']]);
            $upgradeEvent = $stmtUpg->fetch(PDO::FETCH_ASSOC);

            if ($upgradeEvent) {
                // Confirm it's a promotion
                if (($upgradeEvent['old_role'] === 'user' && in_array($upgradeEvent['new_role'], ['admin', 'superadmin'])) ||
                    ($upgradeEvent['old_role'] === 'admin' && $upgradeEvent['new_role'] === 'superadmin')) {
                    $roleUpgradeData = $upgradeEvent;
                }
            }
        }

        // Gráfico Ventas de últimos 7 días
        $stmtChart = $pdo->query("
            SELECT DATE(created_at) as sale_date, SUM(total) as daily_total
            FROM orders
            WHERE status='completado' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC
        ");
        $chartDataRaw = $stmtChart->fetchAll(PDO::FETCH_ASSOC);
        
        $last7DaysSales = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $last7DaysSales[$date] = 0;
        }
        foreach ($chartDataRaw as $row) {
            $last7DaysSales[$row['sale_date']] = (float)$row['daily_total'];
        }

        $this->renderAdmin('admin/admin_panel', [
            'dailySales'                       => $dailySales,
            'topProducts'                      => $topProducts,
            'lowStockProducts'                 => $lowStockProducts,
            'products'                         => $products,
            'lockedUsersAlerts_ParaSuperadmin' => $lockedUsersAlerts,
            'unreadNotificationsDashboard'     => $unreadNotifications,
            'roleUpgradeData'                  => $roleUpgradeData,
            'last7DaysSales'                   => $last7DaysSales,
            'pendingTransfers'                 => $pendingTransfers,
        ]);
    }

    // Marcar notificaciones como leídas
    public function markNotificationsRead()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit;
        }

        global $pdo;
        require_once 'models/Notification.php';
        $notifModel = new Notification($pdo);
        
        // El array viene como notification_ids
        $ids = $_POST['notification_ids'] ?? [];
        
        if (is_array($ids)) {
            foreach ($ids as $id) {
                // Validación de que la notificación le pertenece
                $stmt = $pdo->prepare("SELECT user_id FROM notifications WHERE id = ?");
                $stmt->execute([$id]);
                $ownerId = $stmt->fetchColumn();
                
                if ($ownerId == $_SESSION['user']['id']) {
                    $notifModel->markRead($id);
                }
            }
        }
        
        echo json_encode(['success' => true]);
        exit;
    }

    // Reporte de ventas
    public function salesReport()
    {
        global $pdo;
        // Consulta para obtener las ventas completadas agrupadas por fecha
        $stmt = $pdo->query("SELECT DATE(created_at) AS sale_date, SUM(total) AS total_sales FROM orders WHERE status = 'completado' GROUP BY DATE(created_at) ORDER BY sale_date ASC");
        $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Determinar si hay datos
        $hasData = count($salesData) > 0;

        $labels = [];
        $totals = [];
        if ($hasData) {
            foreach ($salesData as $sale) {
                $labels[] = $sale['sale_date'];
                $totals[] = $sale['total_sales'];
            }
        }

        // Envía los datos codificados en JSON y la variable $hasData a la vista
        $this->renderAdmin('admin/sales_report', [
            'labels' => json_encode($labels),
            'totals' => json_encode($totals),
            'hasData' => $hasData
        ]);
    }


    // Generador de facturas y boletas
    public function invoice()
    {
        // Módulo de boletas eliminado. Redirigir al Dashboard.
        header('Location: ' . APP_BASE . '/admin/index');
        exit;
    }

    // --- Módulo Usuarios Bloqueados (Solo Superadmin) ---
    public function lockedUsers()
    {
        if ($_SESSION['user']['role'] !== 'superadmin') {
            die("Acceso denegado.");
        }
        global $pdo;
        require_once 'models/User.php';
        $userModel = new User($pdo);
        $lockedUsers = $userModel->getLockedUsers();
        
        $this->renderAdmin('admin/locked_users', [
            'lockedUsers' => $lockedUsers
        ]);
    }

    public function unlockUserAction()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit;
        }

        // Validación de CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido (CSRF). Por favor, recargue la página.']);
            exit;
        }

        $userId = trim($_POST['user_id'] ?? '');
        $superadminPassword = trim($_POST['superadmin_password'] ?? '');

        if (!$userId || !$superadminPassword) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos.']);
            exit;
        }

        global $pdo;
        require_once 'models/User.php';
        $userModel = new User($pdo);

        // Verificar contraseña del superadmin usando su ID en sesión
        if (!$userModel->verifyPasswordById($_SESSION['user']['id'], $superadminPassword)) {
            echo json_encode(['success' => false, 'message' => 'Contraseña de súper administrador incorrecta.']);
            exit;
        }

        if ($userModel->unlockUser($userId)) {
            echo json_encode(['success' => true, 'message' => 'Usuario desbloqueado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al desbloquear el usuario.']);
        }
        exit;
    }
    // ----------------------------------------------------

    // Emisión de notas de crédito y débito
    // public function creditDebitNotes()

    // Función auxiliar para renderizar vistas administrativas usando el layout de admin
    protected function renderAdmin($view, $data = [])
    {
        extract($data);
        require_once __DIR__ . '/../views/templates/admin_header.php';
        require_once __DIR__ . '/../views/' . $view . '.php';
        require_once __DIR__ . '/../views/templates/admin_footer.php';
    }

    public function addProduct()
    {
        // Renderiza un formulario para agregar un nuevo producto
        $this->renderAdmin('admin/add_product');
    }

    public function saveProduct()
    {
        global $pdo;
        // Campos básicos
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $stock = intval($_POST['stock']);
        $image = trim($_POST['image'] ?? '');
        $available = isset($_POST['available']) ? 1 : 0;
        $reason = trim($_POST['reason_unavailable'] ?? null);

        // Nuevos campos de cost y tasas
        $cost = floatval($_POST['cost'] ?? 0.00);
        $utility = floatval($_POST['utility_percent'] ?? 0.00);
        $tax = floatval($_POST['tax_percent'] ?? 0.00);
        // Calcular sale_price
        $sale_price = round($cost * (1 + $utility / 100) * (1 + $tax / 100), 2);

        // Validar credenciales de admin
        $cu = trim($_POST['confirm_username'] ?? '');
        $cp = trim($_POST['confirm_password'] ?? '');
        if (!$cu || !$cp) {
            echo json_encode(['success' => false, 'message' => 'Se requieren credenciales.']);
            exit;
        }
        $admin = $pdo->prepare("SELECT * FROM users WHERE username=?");
        $admin->execute([$cu]);
        $a = $admin->fetch(PDO::FETCH_ASSOC);
        if (!$a || !password_verify($cp, $a['password']) || $a['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Credenciales inválidas.']);
            exit;
        }

        // Verificar duplicado
        $dup = $pdo->prepare("SELECT id FROM products WHERE name=?");
        $dup->execute([$name]);
        if ($dup->rowCount()) {
            echo json_encode(['success' => false, 'message' => 'El producto ya existe.']);
            exit;
        }

        // Insertar incluyendo cost y sale_price
        $ins = $pdo->prepare(
            "INSERT INTO products 
              (name,description,cost,utility_percent,tax_percent,sale_price,stock,image,available,reason_unavailable)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        );
        $ok = $ins->execute([
            $name,
            $description,
            $cost,
            $utility,
            $tax,
            $sale_price,
            $stock,
            $image,
            $available,
            $reason
        ]);

        echo json_encode([
            'success' => (bool) $ok,
            'message' => $ok ? 'Producto agregado correctamente.' : 'Error al agregar producto.'
        ]);
        exit;
    }


    public function editProduct($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            $_SESSION['flash'] = "Producto no encontrado.";
            $_SESSION['flash_type'] = "alert";
            header("Location: " . APP_BASE . "/admin/index");
            exit;
        }
        $this->renderAdmin('admin/edit_product', ['product' => $product]);
    }
    // Procesar la actualización de producto con validación de credenciales (AJAX)
    public function updateProduct($id)
    {
        global $pdo;
        // Obtener producto actual para valores por defecto
        $stmtProd = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmtProd->execute([$id]);
        $product = $stmtProd->fetch(PDO::FETCH_ASSOC);

        // Recoger datos del formulario o usar valores actuales
        $name = trim($_POST['name'] ?? $product['name']);
        $description = trim($_POST['description'] ?? $product['description']);
        $stock = intval($_POST['stock'] ?? $product['stock']);
        $image = trim($_POST['image'] ?? $product['image']);
        $available = isset($_POST['available']) ? 1 : 0;
        $reason_unavailable = trim($_POST['reason_unavailable'] ?? $product['reason_unavailable']);

        // Solo Superadmin puede editar costos e impuestos
        $cost = isset($_POST['cost']) ? floatval($_POST['cost']) : floatval($product['cost']);
        $utility = isset($_POST['utility_percent']) ? floatval($_POST['utility_percent']) : floatval($product['utility_percent']);
        $tax = isset($_POST['tax_percent']) ? floatval($_POST['tax_percent']) : floatval($product['tax_percent']);

        // Calcular precio de venta
        $sale_price = round($cost * (1 + $utility / 100), 2);

        // Credenciales para permisos
        $confirmUsername = trim($_POST['confirm_username'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        if (empty($confirmUsername) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => 'Se requieren credenciales de administrador.']);
            exit;
        }
        // Validar usuario y rol
        $stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmtAdmin->execute([$confirmUsername]);
        $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
        if (
            !$adminData || !password_verify($confirmPassword, $adminData['password'])
            || !in_array($adminData['role'], ['superadmin'])
        ) {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas o sin permisos de superadmin.']);
            exit;
        }

        // Actualizar producto
        $stmtUpdate = $pdo->prepare(
            "UPDATE products SET 
                name = ?,
                description = ?,
                cost = ?,
                utility_percent = ?,
                tax_percent = ?,
                sale_price = ?,
                stock = ?,
                image = ?,
                available = ?,
                reason_unavailable = ?
             WHERE id = ?"
        );
        $success = $stmtUpdate->execute([
            $name,
            $description,
            $cost,
            $utility,
            $tax,
            $sale_price,
            $stock,
            $image,
            $available,
            $reason_unavailable,
            $id
        ]);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el producto.']);
        }
        exit;
    }


    public function inventory()
    {
        global $pdo;
        $products = $pdo->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
        $logs = $pdo->query("SELECT * FROM inventory_log ORDER BY created_at DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
        $this->renderAdmin('admin/inventory', ['products' => $products, 'logs' => $logs]);
    }

    // Método para mostrar el Top 10 de productos más vendidos
    public function topProducts()
    {
        global $pdo;
        $stmtTop = $pdo->query("SELECT p.id, p.name, SUM(oi.quantity) AS total_quantity 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             GROUP BY p.id 
                             ORDER BY total_quantity DESC 
                             LIMIT 10");
        $topProducts = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
        $this->renderAdmin('admin/top_products', ['topProducts' => $topProducts]);
    }

    // Método para mostrar los productos con bajo stock (por ejemplo, stock menor a 5)
    public function lowStock()
    {
        global $pdo;
        $lowStockThreshold = 5;
        $currentBranch = defined('BRANCH') && BRANCH !== '' ? BRANCH : '';
        $stmtLow = $pdo->prepare(
            "SELECT p.id, p.name, p.sku, COALESCE(bps.stock, 0) AS stock
             FROM products p
             LEFT JOIN branch_product_stock bps
                    ON bps.product_id = p.id AND bps.branch = ?
             WHERE COALESCE(bps.stock, 0) < ? AND p.available = 1"
        );
        $stmtLow->execute([$currentBranch, $lowStockThreshold]);
        $lowStockProducts = $stmtLow->fetchAll(PDO::FETCH_ASSOC);
        $this->renderAdmin('admin/low_stock', ['lowStockProducts' => $lowStockProducts]);
    }
    // El aumento de stock se gestiona exclusivamente a través del módulo de Pedidos
    public function increaseStock($id = null)
    {
        header('Location: ' . APP_BASE . '/order/create');
        exit;
    }

    // El aumento de stock se gestiona exclusivamente a través del módulo de Pedidos
    public function updateStock($id = null)
    {
        echo json_encode(['success' => false, 'message' => 'El aumento de stock directo está deshabilitado. Use el módulo de Pedidos.']);
        exit;
    }


    // --- GESTION DE ROLES (Superadmin) ---

    public function manageRoles()
    {
        if ($_SESSION['user']['role'] !== 'superadmin') {
            header('Location: ' . APP_BASE . '/admin/index');
            exit;
        }

        global $pdo;
        // Listar todos los usuarios, excepto el propio superadmin
        $stmt = $pdo->prepare("SELECT id, username, first_name, last_name, role, status FROM users WHERE id != ? ORDER BY role, first_name");
        $stmt->execute([$_SESSION['user']['id']]);
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $activeUsers = [];
        $disabledUsers = [];
        foreach ($allUsers as $u) {
            if (($u['status'] ?? 'active') === 'disabled') {
                $disabledUsers[] = $u;
            } else {
                $activeUsers[] = $u;
            }
        }

        $this->renderAdmin('admin/role_management', [
            'users' => $activeUsers,
            'disabledUsers' => $disabledUsers
        ]);
    }

    public function changeRoleAction()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit;
        }

        // Validación de CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido (CSRF). Por favor, recargue la página.']);
            exit;
        }

        $targetUserId = intval($_POST['user_id'] ?? 0);
        $newRole = trim($_POST['new_role'] ?? '');
        $password = $_POST['superadmin_password'] ?? '';

        if (!$targetUserId || !$newRole || !$password) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            exit;
        }

        global $pdo;
        // Verificar contraseña del superadmin actual
        $stmtAdmin = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmtAdmin->execute([$_SESSION['user']['id']]);
        $adminHash = $stmtAdmin->fetchColumn();

        if (!password_verify($password, $adminHash)) {
            echo json_encode(['success' => false, 'message' => 'Contraseña de súper administrador incorrecta.']);
            exit;
        }

        // Obtener el rol actual del usuario objetivo
        $stmtTarget = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmtTarget->execute([$targetUserId]);
        $oldRole = $stmtTarget->fetchColumn();

        if (!$oldRole) {
            echo json_encode(['success' => false, 'message' => 'Usuario objetivo no encontrado.']);
            exit;
        }

        if ($oldRole === $newRole) {
            echo json_encode(['success' => false, 'message' => 'El usuario ya tiene asignado ese rol.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Actualizar rol y obligar a cerrar sesión
            $stmtUpdate = $pdo->prepare("UPDATE users SET role = ?, force_logout = 1 WHERE id = ?");
            $stmtUpdate->execute([$newRole, $targetUserId]);

            // Guardar en la bitácora
            $stmtLog = $pdo->prepare("INSERT INTO role_change_logs (target_user, changed_by, old_role, new_role) VALUES (?, ?, ?, ?)");
            $stmtLog->execute([$targetUserId, $_SESSION['user']['id'], $oldRole, $newRole]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Privilegios actualizados correctamente. La sesión del usuario ha sido invalidada.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error al actualizar base de datos: ' . $e->getMessage()]);
        }
        exit;
    }

    public function editUserAction()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit;
        }

        // Validación de CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido (CSRF). Por favor, recargue la página.']);
            exit;
        }

        $userId = intval($_POST['edit_user_id'] ?? 0);
        $firstName = trim($_POST['first_name'] ?? '');
        $secondName = trim($_POST['second_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $secondSurname = trim($_POST['second_surname'] ?? '');
        $branch = trim($_POST['branch'] ?? '');

        if (!$userId || !$firstName || !$lastName || !$branch) {
            echo json_encode(['success' => false, 'message' => 'Nombres, apellidos y sucursal son obligatorios.']);
            exit;
        }

        global $pdo;

        // Fetch current user data
        $stmtUser = $pdo->prepare("SELECT first_name, second_name, last_name, second_surname, branch, personal_data_updated_at FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$currentUser) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
            exit;
        }

        $personalDataChanged = (
            $currentUser['first_name'] !== $firstName || 
            $currentUser['second_name'] !== $secondName || 
            $currentUser['last_name'] !== $lastName || 
            $currentUser['second_surname'] !== $secondSurname
        );

        $branchChanged = ($currentUser['branch'] !== $branch);

        if (!$personalDataChanged && !$branchChanged) {
            echo json_encode(['success' => true, 'message' => 'No hubo cambios que guardar.']);
            exit;
        }

        if ($personalDataChanged && !empty($currentUser['personal_data_updated_at'])) {
            $lastUpdateDate = new DateTime($currentUser['personal_data_updated_at']);
            $now = new DateTime();
            $interval = $lastUpdateDate->diff($now);
            
            // Check if 6 months have passed
            $monthsPassed = ($interval->y * 12) + $interval->m;
            if ($monthsPassed < 6) {
                echo json_encode(['success' => false, 'message' => 'Los datos personales solo pueden modificarse una vez cada 6 meses.']);
                exit;
            }
        }

        try {
            $pdo->beginTransaction();

            $updateFields = [];
            $params = [];

            if ($personalDataChanged) {
                $updateFields[] = "first_name = ?";
                $updateFields[] = "second_name = ?";
                $updateFields[] = "last_name = ?";
                $updateFields[] = "second_surname = ?";
                $updateFields[] = "personal_data_updated_at = NOW()";
                array_push($params, $firstName, $secondName, $lastName, $secondSurname);
            }

            if ($branchChanged) {
                $updateFields[] = "branch = ?";
                $updateFields[] = "force_logout = 1";
                $params[] = $branch;
            }

            $params[] = $userId;
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->execute($params);

            $pdo->commit();

            $msg = 'Datos actualizados correctamente.';
            if ($branchChanged) {
                $msg .= ' La sesión del usuario ha sido invalidada por el cambio de sucursal.';
            }

            echo json_encode(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error al actualizar base de datos: ' . $e->getMessage()]);
        }
        exit;
    }

    public function roleChangeHistory()
    {
        if ($_SESSION['user']['role'] !== 'superadmin') {
            header('Location: ' . APP_BASE . '/admin/index');
            exit;
        }

        global $pdo;
        $stmt = $pdo->query("
            SELECT r.*, 
                   CONCAT(u1.first_name, ' ', u1.last_name) as target_name,
                   CONCAT(u2.first_name, ' ', u2.last_name) as admin_name
            FROM role_change_logs r
            JOIN users u1 ON r.target_user = u1.id
            JOIN users u2 ON r.changed_by = u2.id
            ORDER BY r.created_at DESC
        ");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/role_change_history', ['logs' => $logs]);
    }

    public function acknowledgeRoleUpgrade()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no válido']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user']['id'] ?? 0;
        
        if ($userId) {
            global $pdo;
            $stmt = $pdo->prepare("UPDATE role_change_logs SET acknowledged = 1 WHERE target_user = ?");
            if ($stmt->execute([$userId])) {
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Error al procesar']);
        exit;
    }

    /** =====================================================
     *  FEATURE: Mi Perfil
     *  ===================================================== */
    public function myProfile()
    {
        // Aseguramos que la sucursal esté en la sesión para los usuarios que ya tenían sesión iniciada
        if (!isset($_SESSION['user']['branch']) || empty($_SESSION['user']['branch'])) {
            global $pdo;
            $stmt = $pdo->prepare("SELECT branch FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user']['id']]);
            $branch = $stmt->fetchColumn();
            $_SESSION['user']['branch'] = $branch ?: 'N/A';
        }

        $this->renderAdmin('admin/my_profile');
    }

    public function changeMyPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no válido.']);
            exit;
        }
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Recargue la página.']);
            exit;
        }
        $currentPw = $_POST['current_password'] ?? '';
        $newPw     = trim($_POST['new_password'] ?? '');
        if (!$currentPw || !$newPw) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }
        if (strlen($newPw) < 8) {
            echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
            exit;
        }
        global $pdo;
        $userId = $_SESSION['user']['id'];
        $stmt   = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($currentPw, $hash)) {
            echo json_encode(['success' => false, 'message' => 'La contraseña actual no es correcta.']);
            exit;
        }
        $newHash = password_hash($newPw, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$newHash, $userId]);
        echo json_encode(['success' => true, 'message' => '¡Contraseña actualizada correctamente!']);
        exit;
    }

    /** =====================================================
     *  FEATURE: Verificar Sesión (Lock Screen Auto-Logout)
     *  ===================================================== */
    public function verifySessionPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false]);
            exit;
        }
        $pw     = $_POST['password'] ?? '';
        $userId = $_SESSION['user']['id'] ?? 0;
        if (!$pw || !$userId) {
            echo json_encode(['success' => false]);
            exit;
        }
        global $pdo;
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        echo json_encode(['success' => password_verify($pw, $hash)]);
        exit;
    }

    /** =====================================================
     *  FEATURE: User Management (Disable / Delete)
     *  ===================================================== */
    public function toggleUserStatusAction()
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere ser superadmin.']);
            exit;
        }
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        // Validate CSRF
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido o caducado. Recarga la página.']);
            exit;
        }

        $targetUserId = $_POST['user_id'] ?? null;
        $newStatus = $_POST['new_status'] ?? ''; // 'active' or 'disabled'
        $loggedUserId = $_SESSION['user']['id'] ?? null;
        $authUsername = trim($_POST['auth_username'] ?? '');
        $authPassword = $_POST['auth_password'] ?? '';

        if (!$targetUserId || !in_array($newStatus, ['active', 'disabled']) || !$authUsername || !$authPassword) {
            echo json_encode(['success' => false, 'message' => 'Faltan credenciales de autorización o datos incompletos.']);
            exit;
        }

        if ($targetUserId == $loggedUserId) {
            echo json_encode(['success' => false, 'message' => 'No puedes cambiar tu propio estado de acceso por seguridad.']);
            exit;
        }

        global $pdo;
        try {
            // Validate Authorization Credentials
            $stmtAuth = $pdo->prepare("SELECT password, role FROM users WHERE username = ? AND status = 'active'");
            $stmtAuth->execute([$authUsername]);
            $authUser = $stmtAuth->fetch(PDO::FETCH_ASSOC);

            if (!$authUser || $authUser['role'] !== 'superadmin' || !password_verify($authPassword, $authUser['password'])) {
                echo json_encode(['success' => false, 'message' => 'Credenciales de autorización inválidas. Se requiere un superusuario.']);
                exit;
            }

            // Check if target user exists
            $stmtCheck = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmtCheck->execute([$targetUserId]);
            $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Usuario objetivo no encontrado.']);
                exit;
            }

            // Update status
            $forceLogout = ($newStatus === 'disabled') ? 1 : 0;
            $stmtUpdate = $pdo->prepare("UPDATE users SET status = ?, force_logout = ? WHERE id = ?");
            if ($stmtUpdate->execute([$newStatus, $forceLogout, $targetUserId])) {
                $actionWord = $newStatus === 'disabled' ? 'deshabilitado' : 'activado';
                echo json_encode([
                    'success' => true, 
                    'message' => "La cuenta de {$user['username']} ha sido {$actionWord} exitosamente."
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado en base de datos.']);
            }
        } catch (\PDOException $e) {
            error_log("Error toggling user status: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error de conexión con la base de datos.']);
        }
    }

    public function deleteUserAction()
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere ser superadmin.']);
            exit;
        }
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        // Validate CSRF
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido o caducado. Recarga la página.']);
            exit;
        }

        $targetUserId = $_POST['user_id'] ?? null;
        $loggedUserId = $_SESSION['user']['id'] ?? null;
        $authUsername = trim($_POST['auth_username'] ?? '');
        $authPassword = $_POST['auth_password'] ?? '';

        if (!$targetUserId || !$authUsername || !$authPassword) {
            echo json_encode(['success' => false, 'message' => 'Faltan credenciales de autorización o el ID de usuario.']);
            exit;
        }

        if ($targetUserId == $loggedUserId) {
            echo json_encode(['success' => false, 'message' => 'No puedes auto-eliminarte.']);
            exit;
        }

        global $pdo;
        try {
            // Validate Authorization Credentials
            $stmtAuth = $pdo->prepare("SELECT password, role FROM users WHERE username = ? AND status = 'active'");
            $stmtAuth->execute([$authUsername]);
            $authUser = $stmtAuth->fetch(PDO::FETCH_ASSOC);

            if (!$authUser || $authUser['role'] !== 'superadmin' || !password_verify($authPassword, $authUser['password'])) {
                echo json_encode(['success' => false, 'message' => 'Credenciales de autorización inválidas. Se requiere un superusuario.']);
                exit;
            }

            // Delete user physically
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'El usuario ha sido eliminado de la plataforma.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontró el usuario para eliminar o ya fue eliminado.']);
            }
        } catch (\PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            // Error 23000 typically means a foreign key constraint violation
            if ($e->getCode() == '23000') {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Este usuario tiene registros asociados (como pedidos, historial de roles o descartes) cruciales para el sistema y no puede ser eliminado permanentemente.'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ocurrió un error inesperado en la base de datos al intentar eliminar.']);
            }
        }
    }
}
