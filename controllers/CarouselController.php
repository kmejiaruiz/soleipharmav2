<?php
// controllers/CarouselController.php
require_once 'AdminController.php';
require_once 'models/Carousel.php';
require_once 'config/config.php';

class CarouselController extends AdminController {
    private $carouselModel;
    
    public function __construct(){
         if(session_status() === PHP_SESSION_NONE){
              session_start();
         }
         // Solo administradores pueden acceder a la gestión del carousel
         if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin'){
              die("Acceso denegado.");
         }
         global $pdo;
         $this->carouselModel = new Carousel($pdo);
         // Llamamos al constructor del padre
         parent::__construct();
    }
    
    // Listar todos los slides
    public function index(){
         $slides = $this->carouselModel->getAllSlides();
         $this->renderAdmin('admin/carousel_list', ['slides' => $slides]);
    }
    
    // Mostrar formulario para agregar un nuevo slide
    public function add(){
         $this->renderAdmin('admin/carousel_add');
    }
    
    // Guardar un nuevo slide
    public function save(){
         global $pdo;
         $image = $_POST['image'];
         $title = $_POST['title'] ?? '';
         $description = $_POST['description'] ?? '';
         if($this->carouselModel->addSlide($image, $title, $description)){
              $_SESSION['flash'] = "Slide agregado correctamente.";
              $_SESSION['flash_type'] = "success";
         } else {
              $_SESSION['flash'] = "Error al agregar el slide.";
              $_SESSION['flash_type'] = "alert";
         }
         header("Location: index.php?controller=carousel&action=index");
         exit;
    }
    
    // Mostrar formulario para editar un slide
    public function edit($id){
         global $pdo;
         $slide = $this->carouselModel->getSlideById($id);
         if(!$slide){
              $_SESSION['flash'] = "Slide no encontrado.";
              $_SESSION['flash_type'] = "alert";
              header("Location: index.php?controller=carousel&action=index");
              exit;
         }
         $this->renderAdmin('admin/carousel_edit', ['slide' => $slide]);
    }
    
    // Actualizar un slide
    public function update($id){
         global $pdo;
         $image = $_POST['image'];
         $title = $_POST['title'] ?? '';
         $description = $_POST['description'] ?? '';
         if($this->carouselModel->updateSlide($id, $image, $title, $description)){
              $_SESSION['flash'] = "Slide actualizado correctamente.";
              $_SESSION['flash_type'] = "success";
         } else {
              $_SESSION['flash'] = "Error al actualizar el slide.";
              $_SESSION['flash_type'] = "alert";
         }
         header("Location: index.php?controller=carousel&action=index");
         exit;
    }
    
    // Eliminar un slide
    public function delete($id){
         global $pdo;
         if($this->carouselModel->deleteSlide($id)){
              $_SESSION['flash'] = "Slide eliminado correctamente.";
              $_SESSION['flash_type'] = "success";
         } else {
              $_SESSION['flash'] = "Error al eliminar el slide.";
              $_SESSION['flash_type'] = "alert";
         }
         header("Location: index.php?controller=carousel&action=index");
         exit;
    }
}
?>