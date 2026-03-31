<?php
// controllers/ProductController.php
require_once 'BaseController.php';
require_once './models/Product.php';
require_once './models/Carousel.php';
require_once './config/config.php';

class ProductController extends BaseController
{
    private $productModel;
    private $carouselModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // parent::__construct();
        global $pdo;
        $this->productModel = new Product($pdo);
        $this->carouselModel = new Carousel($pdo);
    }

    // Listado con paginación y carousel
    public function index()
    {
        // Paginación
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $products = $this->productModel->getAll($limit, $offset);
        $totalProducts = $this->productModel->getTotalCount();
        $totalPages = ceil($totalProducts / $limit);

        // Slides carousel
        $slides = $this->carouselModel->getAllSlides();

        $this->render('product_list', [
            'products' => $products,
            'slides' => $slides,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    // Detalle de producto (para ver detalle y agregar al carrito)
    public function detail($id, $error = null)
    {
        $product = $this->productModel->getById($id);
        $this->render('product_detail', [
            'product' => $product,
            'error' => $error
        ]);
    }

    // Formulario de edición (solo Superadmin ve campos de costo)
    public function edit($id)
    {
        // Carga el producto
        $product = $this->productModel->getById($id);
        if (!$product) {
            $_SESSION['flash'] = "Producto no encontrado.";
            header("Location: /soleipharmav2/product/index");
            exit;
        }
        $this->render('product_edit', ['product' => $product]);
    }

    // Guardar cambios (incluye costo/utilidad/impuesto) — Solo Superadmin
    public function update($id)
    {
        // Validar rol
        if ($_SESSION['user']['role'] !== 'superadmin') {
            $_SESSION['flash'] = "Sin permisos para editar.";
            header("Location: /soleipharmav2/product/index");
            exit;
        }

        // Recoger datos
        $cost = floatval($_POST['cost'] ?? 0.00);
        $utility = floatval($_POST['utility_percent'] ?? 0.00);
        $tax = floatval($_POST['tax_percent'] ?? 0.00);

        // Actualizar costo y parámetros
        $this->productModel->updateCostTax($id, $cost, $utility, $tax);

        // Recalcular precio de venta
        $salePrice = round($cost * (1 + $utility / 100), 2);
        $this->productModel->updateSalePrice($id, $salePrice);

        $_SESSION['flash'] = "Producto actualizado correctamente.";
        header("Location: /soleipharmav2/product/index");
        exit;
    }

    // Actualizar costos masivamente desde update_costs.php
    public function updateCostsForm()
    {
        // Auth Check
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'superadmin')) {
             header("Location: index.php");
             exit;
        }

        $products = $this->productModel->getAll(1000, 0); // Limit high to show all
        $this->renderAdmin('admin/update_costs', ['products' => $products]);
    }

    public function updateCosts()
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Requiere permiso de administrador.']);
            exit;
        }

        $costs = $_POST['costs'] ?? [];
        foreach ($costs as $id => $cost) {
            $product = $this->productModel->getById($id);
            if ($product) {
                $utility = $product['utility_percent'] ?? 0;
                $tax = $product['tax_percent'] ?? 0;
                $newCost = floatval($cost);
                
                $this->productModel->updateCostTax($id, $newCost, $utility, $tax);
                
                $newSalePrice = round($newCost * (1 + $utility / 100), 2);
                $this->productModel->updateSalePrice($id, $newSalePrice);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Costos actualizados correctamente.']);
        exit;
    }

    // Helper para renderizar vistas de admin
    private function renderAdmin($view, $data = [])
    {
        extract($data);
        require_once __DIR__ . '/../views/templates/admin_header.php';
        require_once __DIR__ . '/../views/' . $view . '.php';
        require_once __DIR__ . '/../views/templates/admin_footer.php';
    }
}
