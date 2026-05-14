<?php
// helpers/BranchStock.php
// Funciones centralizadas para leer/escribir stock POR SUCURSAL
// Usa la tabla branch_product_stock como única fuente de verdad.
// products.stock ya no se toca en operaciones normales (deprecated).

class BranchStock
{
    /**
     * Retorna el stock de un producto en una sucursal.
     */
    public static function get(PDO $pdo, int $productId, string $branch): int
    {
        $stmt = $pdo->prepare(
            "SELECT COALESCE(stock, 0)
             FROM branch_product_stock
             WHERE product_id = ? AND branch = ?"
        );
        $stmt->execute([$productId, $branch]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    /**
     * Ajusta el stock de un producto en una sucursal (delta positivo o negativo).
     * Usa INSERT … ON DUPLICATE KEY UPDATE para ser idempotente.
     */
    public static function adjust(PDO $pdo, int $productId, string $branch, int $delta): void
    {
        $pdo->prepare("
            INSERT INTO branch_product_stock (product_id, branch, stock)
            VALUES (?, ?, GREATEST(0, ?))
            ON DUPLICATE KEY UPDATE stock = GREATEST(0, stock + ?)
        ")->execute([$productId, $branch, max(0, $delta), $delta]);
    }

    /**
     * Consulta el stock de múltiples productos en una sucursal de una sola vez.
     * Retorna [ product_id => stock ]
     */
    public static function getMany(PDO $pdo, array $productIds, string $branch): array
    {
        if (empty($productIds)) return [];
        $in = implode(',', array_map('intval', $productIds));
        $stmt = $pdo->prepare(
            "SELECT product_id, COALESCE(stock, 0) AS stock
             FROM branch_product_stock
             WHERE product_id IN ($in) AND branch = ?"
        );
        $stmt->execute([$branch]);
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(int)$row['product_id']] = (int)$row['stock'];
        }
        // Productos sin registro = stock 0
        foreach ($productIds as $pid) {
            if (!isset($result[(int)$pid])) $result[(int)$pid] = 0;
        }
        return $result;
    }

    /**
     * Mueve stock entre dos bodegas INTERNAS de la misma sucursal.
     * Para bodega_stock (débito/merma) filtra también por branch.
     */
    public static function adjustBodega(PDO $pdo, int $productId, string $bodega, string $branch, int $delta): void
    {
        if ($bodega === 'sucursal') {
            self::adjust($pdo, $productId, $branch, $delta);
            return;
        }
        $pdo->prepare("
            INSERT INTO bodega_stock (product_id, bodega, branch, stock)
            VALUES (?, ?, ?, GREATEST(0, ?))
            ON DUPLICATE KEY UPDATE stock = GREATEST(0, stock + ?)
        ")->execute([$productId, $bodega, $branch, max(0, $delta), $delta]);
    }

    /**
     * Obtiene el stock de una bodega interna (débito/merma) para una sucursal.
     */
    public static function getBodega(PDO $pdo, int $productId, string $bodega, string $branch): int
    {
        if ($bodega === 'sucursal') {
            return self::get($pdo, $productId, $branch);
        }
        $stmt = $pdo->prepare(
            "SELECT COALESCE(stock, 0)
             FROM bodega_stock
             WHERE product_id = ? AND bodega = ? AND branch = ?"
        );
        $stmt->execute([$productId, $bodega, $branch]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }
}
