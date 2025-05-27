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
            header("Location: index.php?controller=product&action=index");
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
            header("Location: index.php?controller=product&action=index");
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
        header("Location: index.php?controller=product&action=index");
        exit;
    }
}
