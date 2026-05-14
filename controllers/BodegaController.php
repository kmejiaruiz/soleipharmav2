<?php
// controllers/BodegaController.php
require_once 'AdminController.php';
require_once 'config/config.php';
require_once __DIR__ . '/../helpers/BranchStock.php';

class BodegaController extends AdminController
{
    private function branch(): string
    {
        return defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');
    }

    // ── Etiquetas legibles de cada bodega ──────────────────────────────────
    private function labels(): array
    {
        return [
            'sucursal' => $this->branch() ?: 'Sucursal',
            'debito'   => 'Bodega de Débito',
            'merma'    => 'Bodega de Merma',
        ];
    }

    // ── stock(): Ver inventario de una bodega ──────────────────────────────
    public function stock(): void
    {
        global $pdo;

        $branch = $this->branch();
        $bodega = $_GET['bodega'] ?? 'sucursal';
        $search = trim($_GET['q'] ?? '');
        $labels = $this->labels();

        if (!array_key_exists($bodega, $labels)) $bodega = 'sucursal';

        if ($bodega === 'sucursal') {
            // Sucursal: join con branch_product_stock filtrando por sucursal
            $sql    = "SELECT p.id, p.sku, p.name,
                              COALESCE(bps.stock, 0) AS stock
                       FROM products p
                       LEFT JOIN branch_product_stock bps
                              ON bps.product_id = p.id AND bps.branch = ?
                       WHERE p.available = 1";
            $params = [$branch];
            if ($search) {
                $sql     .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            $sql .= " ORDER BY p.name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {
            // Débito / Merma: filtrar por bodega Y sucursal
            $sql    = "SELECT p.id, p.sku, p.name, bs.stock
                       FROM bodega_stock bs
                       JOIN products p ON p.id = bs.product_id
                       WHERE bs.bodega = ?
                         AND bs.branch = ?
                         AND bs.stock > 0
                         AND p.available = 1";
            $params = [$bodega, $branch];
            if ($search) {
                $sql     .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            $sql .= " ORDER BY p.name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sinStock = empty($products) && !$search;
        }

        $this->renderAdmin('admin/bodega_stock', [
            'bodega'   => $bodega,
            'labels'   => $labels,
            'products' => $products,
            'search'   => $search,
            'sinStock' => $sinStock ?? false,
        ]);
    }

    // ── transfer(): Formulario de traslado interno ─────────────────────────
    public function transfer(): void
    {
        global $pdo;

        $labels   = $this->labels();
        $products = $pdo->query(
            "SELECT id, sku, name FROM products WHERE available = 1 ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/bodega_transfer', [
            'labels'   => $labels,
            'products' => $products,
        ]);
    }

    // ── saveTransfer(): POST AJAX guardar traslado interno ─────────────────
    public function saveTransfer(): void
    {
        global $pdo;
        header('Content-Type: application/json; charset=utf-8');
        ob_start();

        $branch     = $this->branch();
        $productId  = intval($_POST['product_id']  ?? 0);
        $fromBodega = trim($_POST['from_bodega']   ?? '');
        $toBodega   = trim($_POST['to_bodega']     ?? '');
        $quantity   = intval($_POST['quantity']    ?? 0);
        $reason     = trim($_POST['reason']        ?? '');
        $userId     = $_SESSION['user']['id']      ?? 0;

        $validBodegas = ['sucursal', 'debito', 'merma'];

        if (!$productId || !in_array($fromBodega, $validBodegas) || !in_array($toBodega, $validBodegas)) {
            ob_clean(); echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos.']); exit;
        }
        if ($fromBodega === $toBodega) {
            ob_clean(); echo json_encode(['success' => false, 'message' => 'El origen y el destino no pueden ser la misma bodega.']); exit;
        }
        if ($quantity < 1) {
            ob_clean(); echo json_encode(['success' => false, 'message' => 'La cantidad debe ser al menos 1.']); exit;
        }

        $stmtP = $pdo->prepare("SELECT id, name FROM products WHERE id = ? AND available = 1");
        $stmtP->execute([$productId]);
        $product = $stmtP->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            ob_clean(); echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']); exit;
        }

        $availableStock = BranchStock::getBodega($pdo, $productId, $fromBodega, $branch);
        if ($quantity > $availableStock) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => "Stock insuficiente en origen ({$branch}). Disponible: {$availableStock} uds."]);
            exit;
        }

        try {
            $pdo->beginTransaction();

            BranchStock::adjustBodega($pdo, $productId, $fromBodega, $branch, -$quantity);
            BranchStock::adjustBodega($pdo, $productId, $toBodega,   $branch, +$quantity);

            $pdo->prepare("
                INSERT INTO bodega_movements
                    (product_id, from_bodega, to_bodega, branch, quantity, reason, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([$productId, $fromBodega, $toBodega, $branch, $quantity, $reason ?: null, $userId]);

            $pdo->commit();
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => "Traslado registrado: {$quantity} uds. de '{$product['name']}' de {$fromBodega} a {$toBodega} [{$branch}].",
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Error al registrar el traslado: ' . $e->getMessage()]);
        }
        exit;
    }

    // ── history(): Historial filtrado por sucursal ─────────────────────────
    public function history(): void
    {
        global $pdo;

        $branch      = $this->branch();
        $labels      = $this->labels();
        $filterFrom  = $_GET['from_bodega'] ?? '';
        $filterTo    = $_GET['to_bodega']   ?? '';
        $filterDate1 = trim($_GET['date1']  ?? '');
        $filterDate2 = trim($_GET['date2']  ?? '');
        $filterQ     = trim($_GET['q']      ?? '');

        $sql = "SELECT bm.id, bm.created_at,
                       p.name AS product_name, p.sku,
                       bm.from_bodega, bm.to_bodega,
                       bm.quantity, bm.reason,
                       CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS user_name,
                       u.username
                FROM bodega_movements bm
                JOIN products p ON p.id = bm.product_id
                JOIN users u    ON u.id = bm.user_id
                WHERE bm.branch = ?";
        $params = [$branch];

        if ($filterFrom) { $sql .= " AND bm.from_bodega = ?"; $params[] = $filterFrom; }
        if ($filterTo)   { $sql .= " AND bm.to_bodega   = ?"; $params[] = $filterTo; }
        if ($filterDate1){ $sql .= " AND DATE(bm.created_at) >= ?"; $params[] = $filterDate1; }
        if ($filterDate2){ $sql .= " AND DATE(bm.created_at) <= ?"; $params[] = $filterDate2; }
        if ($filterQ) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR u.username LIKE ?)";
            $params[] = "%$filterQ%"; $params[] = "%$filterQ%"; $params[] = "%$filterQ%";
        }
        $sql .= " ORDER BY bm.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="traslados_bodega_' . date('Ymd_His') . '.csv"');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Fecha', 'SKU', 'Producto', 'Desde', 'Hasta', 'Cantidad', 'Motivo', 'Usuario']);
            foreach ($movements as $m) {
                fputcsv($out, [
                    $m['created_at'],
                    $m['sku'],
                    $m['product_name'],
                    $labels[$m['from_bodega']] ?? $m['from_bodega'],
                    $labels[$m['to_bodega']]   ?? $m['to_bodega'],
                    $m['quantity'],
                    $m['reason'] ?? '',
                    trim($m['user_name']) ?: $m['username'],
                ]);
            }
            fclose($out);
            exit;
        }

        $this->renderAdmin('admin/bodega_history', [
            'labels'      => $labels,
            'movements'   => $movements,
            'filterFrom'  => $filterFrom,
            'filterTo'    => $filterTo,
            'filterDate1' => $filterDate1,
            'filterDate2' => $filterDate2,
            'filterQ'     => $filterQ,
        ]);
    }

    // ── stockAjax(): stock de un producto en una bodega (JS) ──────────────
    public function stockAjax(): void
    {
        global $pdo;
        header('Content-Type: application/json; charset=utf-8');
        $productId    = intval($_GET['product_id'] ?? 0);
        $bodega       = trim($_GET['bodega'] ?? '');
        $validBodegas = ['sucursal', 'debito', 'merma'];
        if (!$productId || !in_array($bodega, $validBodegas)) {
            echo json_encode(['stock' => 0]); exit;
        }
        echo json_encode(['stock' => BranchStock::getBodega($pdo, $productId, $bodega, $this->branch())]);
        exit;
    }
}
?>
