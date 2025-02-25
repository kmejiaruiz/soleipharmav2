<?php
// models/Product.php
require_once 'BaseModel.php';

class Product extends BaseModel {

    public function getAll($limit = 10, $offset = 0) {
        $stmt = $this->pdo->prepare("SELECT * FROM products LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM products");
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
