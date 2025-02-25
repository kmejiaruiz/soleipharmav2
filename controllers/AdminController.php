<?php
// controllers/AdminController.php
require_once 'BaseController.php';
require_once 'models/Product.php';
require_once 'config/config.php';

class AdminController extends BaseController {
    private $productModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Solo se permite acceso si el usuario es admin
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            die("Acceso denegado.");
        }
        global $pdo;
        $this->productModel = new Product($pdo);
    }

    // Dashboard principal del admin (con AdminLTE)
    public function index() {
        
        global $pdo;
        // Ventas del día
        $stmtSales = $pdo->prepare("SELECT SUM(total) AS total_sales FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completado'");
        $stmtSales->execute();
        $dailySalesData = $stmtSales->fetch(PDO::FETCH_ASSOC);
        $dailySales = $dailySalesData['total_sales'] ?? 0;
    
        // Top 10 productos más vendidos
        $stmtTop = $pdo->query("SELECT p.id, p.name, SUM(oi.quantity) AS total_quantity 
                                 FROM order_items oi 
                                 JOIN products p ON oi.product_id = p.id 
                                 GROUP BY p.id 
                                 ORDER BY total_quantity DESC 
                                 LIMIT 10");
        $topProducts = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
    
        // Productos con bajo stock (definimos bajo stock como stock < 5)
        $lowStockThreshold = 5;
        $stmtLow = $pdo->prepare("SELECT id, name, stock FROM products WHERE stock < ?");
        $stmtLow->execute([$lowStockThreshold]);
        $lowStockProducts = $stmtLow->fetchAll(PDO::FETCH_ASSOC);
    
        $this->renderAdmin('admin/admin_panel', [
            'dailySales' => $dailySales,
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts
        ]);


    }
    
    
    // Reporte de ventas
    public function salesReport() {
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
    public function invoice() {
        // Aquí se implementaría la lógica para generar facturas, ya sea en HTML o PDF
        $invoiceData = []; // Datos de ejemplo
        $this->renderAdmin('admin/invoice', ['invoiceData' => $invoiceData]);
    }
    
    // Emisión de notas de crédito y débito
    public function creditDebitNotes() {
        // Lógica para mostrar y emitir notas
        $this->renderAdmin('admin/credit_debit_notes');
    }
    
    // Carga masiva de productos mediante Excel
    public function bulkUpload() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Procesa el archivo Excel
            // Por ejemplo, usando PhpSpreadsheet:
            // require 'vendor/autoload.php';
            // $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($_FILES['excel']['tmp_name']);
            // $spreadsheet = $reader->load($_FILES['excel']['tmp_name']);
            // Procesa cada fila y agrega productos
            $message = "Productos importados correctamente.";
            $_SESSION['flash'] = $message;
            $_SESSION['flash_type'] = "success";
            header("Location: index.php?controller=admin&action=index");
            exit;
        } else {
            $this->renderAdmin('admin/bulk_upload');
        }
    }
    
    // Función auxiliar para renderizar vistas administrativas usando el layout de admin
    protected function renderAdmin($view, $data = []) {
        extract($data);
        require_once __DIR__ . '/../views/templates/admin_header.php';
        require_once __DIR__ . '/../views/' . $view . '.php';
        require_once __DIR__ . '/../views/templates/admin_footer.php';
    }

    public function addProduct() {
        // Renderiza un formulario para agregar un nuevo producto
        $this->renderAdmin('admin/add_product');
    }
    
    public function saveProduct() {
        global $pdo;
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $image = $_POST['image'] ?? '';
        $available = isset($_POST['available']) ? 1 : 0;
        $reason_unavailable = $_POST['reason_unavailable'] ?? null;
        
        // Validar credenciales
        $confirmUsername = trim($_POST['confirm_username'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        if(empty($confirmUsername) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => "Se requieren credenciales de administrador."]);
            exit;
        }
        $stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmtAdmin->execute([$confirmUsername]);
        $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
        if (!$adminData || !password_verify($confirmPassword, $adminData['password']) || $adminData['role'] != 'admin') {
            echo json_encode(['success' => false, 'message' => "Credenciales incorrectas o sin permisos de administrador."]);
            exit;
        }
        
        // Validar si el producto ya existe
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => "El producto ya existe."]);
            exit;
        }
        
        $stmtInsert = $pdo->prepare("INSERT INTO products (name, description, price, stock, image, available, reason_unavailable) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmtInsert->execute([$name, $description, $price, $stock, $image, $available, $reason_unavailable])) {
            echo json_encode(['success' => true, 'message' => "Producto agregado correctamente."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Error al agregar el producto."]);
        }
        exit;
    }
    
    public function editProduct($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            $_SESSION['flash'] = "Producto no encontrado.";
            $_SESSION['flash_type'] = "alert";
            header("Location: index.php?controller=admin&action=index");
            exit;
        }
        $this->renderAdmin('admin/edit_product', ['product' => $product]);
    }
    // Procesar la actualización de producto con validación de credenciales (AJAX)
    public function updateProduct($id) {
        global $pdo;
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $image = $_POST['image'] ?? '';
        $available = isset($_POST['available']) ? 1 : 0;
        $reason_unavailable = $_POST['reason_unavailable'] ?? null;
        
        
        // Validar credenciales
        $confirmUsername = trim($_POST['confirm_username'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        if(empty($confirmUsername) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => "Se requieren credenciales de administrador."]);
            exit;
        }
        $stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmtAdmin->execute([$confirmUsername]);
        $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
        if (!$adminData || !password_verify($confirmPassword, $adminData['password']) || $adminData['role'] != 'admin') {
            echo json_encode(['success' => false, 'message' => "Credenciales incorrectas o sin permisos de administrador."]);
            exit;
        }
        
        $stmtUpdate = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ?, available = ?, reason_unavailable = ? WHERE id = ?");
        if ($stmtUpdate->execute([$name, $description, $price, $stock, $image, $available, $reason_unavailable, $id])) {
            echo json_encode(['success' => true, 'message' => "Producto actualizado correctamente."]);
    //         // Registrar el cambio en la tabla de log de inventario
    // $admin_id = $_SESSION['user']['id'];
    // $admin_name = $_SESSION['user']['username'];
    // $changeType = 'stock_increase';
    // $description = "Se aumentó el stock en " . $additionalStock . " unidades. Stock previo: " . $previousStock . ", Stock nuevo: " . $newStock;
    // $stmtLog = $pdo->prepare("INSERT INTO inventory_log (product_id, admin_id, admin_name, change_type, previous_stock, new_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    // $stmtLog->execute([$id, $admin_id, $admin_name, $changeType, $previousStock, $newStock, $description]);
    
        } else {
            echo json_encode(['success' => false, 'message' => "Error al actualizar el producto."]);
        }
        exit;
      
    }

    
    
    public function inventory() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM products");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmtLog = $pdo->query("SELECT * FROM inventory_log ORDER BY created_at DESC");
        $logs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
        $this->renderAdmin('admin/inventory', ['products' => $products, 'logs' => $logs]);
    }
    
    // Método para mostrar el Top 10 de productos más vendidos
public function topProducts() {
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
public function lowStock() {
    global $pdo;
    $lowStockThreshold = 5;
    $stmtLow = $pdo->prepare("SELECT * FROM products WHERE stock < ?");
    $stmtLow->execute([$lowStockThreshold]);
    $lowStockProducts = $stmtLow->fetchAll(PDO::FETCH_ASSOC);
    $this->renderAdmin('admin/low_stock', ['lowStockProducts' => $lowStockProducts]);
}
// Muestra el formulario para aumentar el stock de un producto
public function increaseStock($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        $_SESSION['flash'] = "Producto no encontrado.";
        $_SESSION['flash_type'] = "alert";
        header("Location: index.php?controller=admin&action=inventory");
        exit;
    }
    $this->renderAdmin('admin/increase_stock', ['product' => $product]);
}

// Procesa el aumento de stock

public function updateStock($id) {
    global $pdo;
    // Obtener cantidad a aumentar y credenciales confirmadas
    $additionalStock = isset($_POST['additional_stock']) ? (int)$_POST['additional_stock'] : 0;
    $confirmUsername = trim($_POST['confirm_username'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    if ($additionalStock <= 0) {
        $response = ['success' => false, 'message' => "La cantidad debe ser mayor a 0."];
        echo json_encode($response);
        exit;
    }
    
    // Validar que se hayan ingresado ambos campos
    if (empty($confirmUsername) || empty($confirmPassword)) {
        $response = ['success' => false, 'message' => "Debe ingresar usuario y contraseña."];
        echo json_encode($response);
        exit;
    }
    
    // Obtener datos del usuario que confirma
    $stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmtAdmin->execute([$confirmUsername]);
    $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
    
    // Validar credenciales: si no se encuentra el usuario, o la contraseña no coincide, o el usuario no es admin, abortar
    if (!$adminData || !password_verify($confirmPassword, $adminData['password']) || $adminData['role'] != 'admin') {
        $response = [
            'success' => false, 
            'message' => "Credenciales incorrectas o no tienes permisos de administrador. No se realizó la actualización."
        ];
        echo json_encode($response);
        exit;
    }
    
    // Obtener el producto a actualizar
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        $response = ['success' => false, 'message' => "Producto no encontrado."];
        echo json_encode($response);
        exit;
    }
    $previousStock = $product['stock'];
    $newStock = $previousStock + $additionalStock;
    
    // Actualizar el stock
    $stmtUpdate = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmtUpdate->execute([$newStock, $id]);
    
    // Registrar el cambio en el log de inventario
    $admin_id = $adminData['id'];
    $admin_name = $adminData['username'];
    $changeType = 'stock_increase';
    $description = "Se aumentó el stock en " . $additionalStock . " unidades. Stock previo: " . $previousStock . ", Stock nuevo: " . $newStock;
    $stmtLog = $pdo->prepare("INSERT INTO inventory_log (product_id, admin_id, admin_name, change_type, previous_stock, new_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtLog->execute([$id, $admin_id, $admin_name, $changeType, $previousStock, $newStock, $description]);
    
    // Preparar datos de la boleta (recibo)
    $company = COMPANY_NAME;
    $branch = BRANCH;
    $date = date("d/m/Y H:i:s");
    
    $receipt = [
        'product_id' => $id,
        'product_name' => $product['name'],
        'previous_stock' => $previousStock,
        'additional_stock' => $additionalStock,
        'new_stock' => $newStock,
        'admin_aplica' => $admin_name,
        'admin_autoriza' => $admin_name, // En este ejemplo, ambos son iguales
        'date' => $date,
        'company' => $company,
        'branch' => $branch
    ];
    
    $response = ['success' => true, 'receipt' => $receipt];
    echo json_encode($response);
    exit;
}

    
}
?>