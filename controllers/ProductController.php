<?php
// controllers/ProductController.php
require_once 'BaseController.php';
require_once 'models/Product.php';
require_once 'config/config.php';

class ProductController extends BaseController {

    private $productModel;

    public function __construct() {
        global $pdo;
        $this->productModel = new Product($pdo);
    }

    public function index(){
        global $pdo;
        // Obtener productos (ejemplo: 10 productos por página)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $products = $this->productModel->getAll($limit, $offset);
        $totalProducts = $this->productModel->getTotalCount();
        $totalPages = ceil($totalProducts / $limit);
    
        // Cargar slides del carousel
        require_once 'models/Carousel.php';
        $carouselModel = new Carousel($pdo);
        $slides = $carouselModel->getAllSlides();
    
        $this->render('product_list', [
             'products' => $products,
             'slides' => $slides,
             'currentPage' => $page,
             'totalPages' => $totalPages
        ]);
    }
    

    public function detail($id, $error = null) {
        $product = $this->productModel->getById($id);
        $this->render('product_detail', ['product' => $product, 'error' => $error]);
    }
}
?>