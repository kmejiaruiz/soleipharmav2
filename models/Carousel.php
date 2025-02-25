<?php
// models/Carousel.php
require_once 'BaseModel.php';

class Carousel extends BaseModel {
    // Devuelve todos los slides ordenados por id ascendente
    public function getAllSlides() {
        $stmt = $this->pdo->prepare("SELECT * FROM carousel ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Devuelve un slide específico por id
    public function getSlideById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM carousel WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Agrega un nuevo slide al carousel
    public function addSlide($image, $title, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO carousel (image, title, description) VALUES (?, ?, ?)");
        return $stmt->execute([$image, $title, $description]);
    }
    
    // Actualiza un slide existente
    public function updateSlide($id, $image, $title, $description) {
        $stmt = $this->pdo->prepare("UPDATE carousel SET image = ?, title = ?, description = ? WHERE id = ?");
        return $stmt->execute([$image, $title, $description, $id]);
    }
    
    // Elimina un slide
    public function deleteSlide($id) {
        $stmt = $this->pdo->prepare("DELETE FROM carousel WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>