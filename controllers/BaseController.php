<?php
// controllers/BaseController.php
class BaseController {
    protected function render($view, $data = []) {
        extract($data);
        require_once "./views/templates/header.php";
        require_once "./views/$view.php";
        require_once "./views/templates/footer.php";
    }

    /**
     * Renderiza una vista con layout mínimo (sin navbar, sin modales).
     * Solo muestra el nombre de la empresa y el contenido de la vista.
     */
    protected function renderBare($view, $data = []) {
        extract($data);
        require_once "./views/templates/bare_header.php";
        require_once "./views/$view.php";
        require_once "./views/templates/bare_footer.php";
    }
}
?>
