<?php
// models/Product.php
class Product
{
    private $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Obtiene productos con paginación
    public function getAll($limit = 10, $offset = 0)
    {
        // Asegurar que sean enteros para evitar SQL injection
        $limit = (int) $limit;
        $offset = (int) $offset;

        // Directamente en la consulta para LIMIT/OFFSET
        // Actual DB has 'sale_price'. Aliasing as 'price' for compatibility.
        $sql = "SELECT id, sku, name, description, image, stock, available, cost, sale_price, sale_price as price, utility_percent, tax_percent
                FROM products
                LIMIT $limit OFFSET $offset";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene conteo total de productos
    public function getTotalCount()
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM products");
        return (int) $stmt->fetchColumn();
    }

    // Otros métodos ...
    public function getById($id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT sku,id,name,description,image,stock,available,cost,sale_price,sale_price as price,utility_percent,tax_percent
             FROM products
             WHERE id = ?"
        );
        $stmt->execute([(int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCostTax($id, $cost, $utility, $tax)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE products SET cost = ?, utility_percent = ?, tax_percent = ? WHERE id = ?"
        );
        return $stmt->execute([$cost, $utility, $tax, $id]);
    }

    public function updateSalePrice($id, $salePrice)
    {
        // Database column is 'sale_price'
        $stmt = $this->pdo->prepare("UPDATE products SET sale_price = ? WHERE id = ?");
        return $stmt->execute([$salePrice, $id]);
    }

    public function updateStock($id, $delta)
    {
        $stmt = $this->pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        return $stmt->execute([$delta, $id]);
    }
}
