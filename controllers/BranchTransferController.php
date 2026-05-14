<?php
// controllers/BranchTransferController.php
require_once 'AdminController.php';
require_once 'config/config.php';
require_once __DIR__ . '/../helpers/BranchStock.php';

class BranchTransferController extends AdminController
{
    private string $currentBranch;

    public function __construct()
    {
        parent::__construct();
        $this->currentBranch = defined('BRANCH') && BRANCH !== '' ? BRANCH : 'Sucursal';
    }

    // ── index(): Lista de traslados ────────────────────────────────────────
    public function index(): void
    {
        global $pdo;

        // Traslados PENDIENTES dirigidos a esta sucursal
        $stmtPending = $pdo->prepare("
            SELECT bt.*,
                   CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS creator_name,
                   u.username AS creator_username,
                   (SELECT COUNT(*) FROM branch_transfer_items WHERE transfer_id = bt.id) AS item_count,
                   (SELECT SUM(quantity_sent) FROM branch_transfer_items WHERE transfer_id = bt.id) AS total_units
            FROM branch_transfers bt
            JOIN users u ON u.id = bt.created_by
            WHERE bt.to_branch = ? AND bt.status = 'pendiente'
            ORDER BY bt.created_at DESC
        ");
        $stmtPending->execute([$this->currentBranch]);
        $pendingTransfers = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

        // Historial de traslados relacionados con esta sucursal
        // Los traslados cancelados solo los ve la sucursal que los inició (from_branch)
        $stmtHistory = $pdo->prepare("
            SELECT bt.*,
                   CONCAT(COALESCE(uc.first_name,''),' ',COALESCE(uc.last_name,'')) AS creator_name,
                   uc.username AS creator_username,
                   CONCAT(COALESCE(ur.first_name,''),' ',COALESCE(ur.last_name,'')) AS receiver_name,
                   (SELECT COUNT(*) FROM branch_transfer_items WHERE transfer_id = bt.id) AS item_count,
                   (SELECT SUM(quantity_sent) FROM branch_transfer_items WHERE transfer_id = bt.id) AS total_units
            FROM branch_transfers bt
            JOIN users uc ON uc.id = bt.created_by
            LEFT JOIN users ur ON ur.id = bt.received_by
            WHERE (bt.from_branch = ? OR bt.to_branch = ?)
              AND (bt.status != 'cancelado' OR bt.from_branch = ?)
            ORDER BY bt.created_at DESC
            LIMIT 100
        ");
        $stmtHistory->execute([$this->currentBranch, $this->currentBranch, $this->currentBranch]);
        $allTransfers = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/branch_transfer_index', [
            'pendingTransfers' => $pendingTransfers,
            'allTransfers'     => $allTransfers,
            'currentBranch'    => $this->currentBranch,
        ]);
    }

    // ── create(): Formulario de creación (solo superadmin) ─────────────────
    public function create(): void
    {
        global $pdo;
        $this->requireSuperAdmin();

        $products = $pdo->query(
            "SELECT p.id, p.sku, p.name,
                    COALESCE(bps.stock, 0) AS stock
             FROM products p
             LEFT JOIN branch_product_stock bps
                    ON bps.product_id = p.id AND bps.branch = '" . addslashes($this->currentBranch) . "'
             WHERE p.available = 1
             ORDER BY p.name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Sucursales conocidas (de traslados anteriores + usuarios)
        $knownBranches = $pdo->query(
            "SELECT DISTINCT CONVERT(branch USING utf8mb4) COLLATE utf8mb4_unicode_ci AS branch FROM users
                WHERE branch IS NOT NULL AND branch != ''
             UNION
             SELECT DISTINCT CONVERT(from_branch USING utf8mb4) COLLATE utf8mb4_unicode_ci FROM branch_transfers
             UNION
             SELECT DISTINCT CONVERT(to_branch USING utf8mb4) COLLATE utf8mb4_unicode_ci FROM branch_transfers
             ORDER BY branch ASC"
        )->fetchAll(PDO::FETCH_COLUMN);

        $knownBranches = array_filter($knownBranches, fn($b) => $b !== $this->currentBranch);

        $this->renderAdmin('admin/branch_transfer_create', [
            'products'      => $products,
            'knownBranches' => array_values($knownBranches),
            'currentBranch' => $this->currentBranch,
        ]);
    }

    // ── save(): POST — guardar traslado nuevo ──────────────────────────────
    public function save(): void
    {
        global $pdo;
        header('Content-Type: application/json; charset=utf-8');
        $this->requireSuperAdmin(true);

        $toBranch  = trim($_POST['to_branch'] ?? '');
        $notes     = trim($_POST['notes']     ?? '');
        $items     = $_POST['items']          ?? [];   // [{product_id, quantity}]
        $userId    = $_SESSION['user']['id']  ?? 0;

        if (!$toBranch) {
            echo json_encode(['success' => false, 'message' => 'Indica la sucursal de destino.']);
            exit;
        }
        if ($toBranch === $this->currentBranch) {
            echo json_encode(['success' => false, 'message' => 'El destino no puede ser la misma sucursal.']);
            exit;
        }
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Agrega al menos un producto.']);
            exit;
        }

        // Validar cada ítem
        $validItems = [];
        foreach ($items as $item) {
            $pid = intval($item['product_id'] ?? 0);
            $qty = intval($item['quantity']   ?? 0);
            if (!$pid || $qty < 1) continue;

            $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ? AND available = 1");
            $stmt->execute([$pid]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);
            $branchQty = $prod ? BranchStock::get($pdo, $pid, $this->currentBranch) : 0;

            if (!$prod) {
                echo json_encode(['success' => false, 'message' => "Producto ID {$pid} no encontrado."]);
                exit;
            }
            if ($qty > $branchQty) {
                echo json_encode([
                    'success' => false,
                    'message' => "Stock insuficiente en {$this->currentBranch} para '{$prod['name']}'. Disponible: {$branchQty} uds.",
                ]);
                exit;
            }
            $validItems[] = ['product_id' => $pid, 'quantity' => $qty];
        }

        if (empty($validItems)) {
            echo json_encode(['success' => false, 'message' => 'No hay ítems válidos para trasladar.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Crear cabecera
            $stmt = $pdo->prepare("
                INSERT INTO branch_transfers (from_branch, to_branch, status, notes, created_by)
                VALUES (?, ?, 'pendiente', ?, ?)
            ");
            $stmt->execute([$this->currentBranch, $toBranch, $notes ?: null, $userId]);
            $transferId = $pdo->lastInsertId();

            // Insertar ítems y descontar del stock de la sucursal origen
            foreach ($validItems as $item) {
                $pdo->prepare("
                    INSERT INTO branch_transfer_items (transfer_id, product_id, quantity_sent)
                    VALUES (?, ?, ?)
                ")->execute([$transferId, $item['product_id'], $item['quantity']]);

                BranchStock::adjust($pdo, $item['product_id'], $this->currentBranch, -$item['quantity']);
            }

            $pdo->commit();

            echo json_encode([
                'success'     => true,
                'message'     => 'Traslado creado correctamente.',
                'transfer_id' => $transferId,
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ── receive($id): Ver formulario de recepción ──────────────────────────
    public function receive($id = null): void
    {
        global $pdo;
        $id = intval($id ?? $_GET['id'] ?? 0);

        $transfer = $this->getTransferOrFail($pdo, $id);
        if ($transfer['status'] !== 'pendiente') {
            die('Este traslado no está pendiente de recepción.');
        }

        $items = $pdo->prepare("
            SELECT bti.*, p.name AS product_name, p.sku,
                   COALESCE(p.cost, 0) AS unit_cost
            FROM branch_transfer_items bti
            JOIN products p ON p.id = bti.product_id
            WHERE bti.transfer_id = ?
            ORDER BY p.name ASC
        ");
        $items->execute([$id]);
        $items = $items->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/branch_transfer_receive', [
            'transfer'      => $transfer,
            'items'         => $items,
            'currentBranch' => $this->currentBranch,
        ]);
    }

    // ── confirmReceive(): POST AJAX — confirmar recepción ──────────────────
    public function confirmReceive(): void
    {
        global $pdo;
        header('Content-Type: application/json; charset=utf-8');

        $transferId = intval($_POST['transfer_id'] ?? 0);
        $quantities = $_POST['quantities'] ?? [];  // [item_id => qty_received]
        $userId     = $_SESSION['user']['id'] ?? 0;

        $transfer = $this->getTransferOrFail($pdo, $transferId);
        if ($transfer['status'] !== 'pendiente') {
            echo json_encode(['success' => false, 'message' => 'Este traslado ya fue procesado.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            foreach ($quantities as $itemId => $qtyReceived) {
                $itemId      = intval($itemId);
                $qtyReceived = max(0, intval($qtyReceived));

                // Obtener el ítem
                $stmt = $pdo->prepare(
                    "SELECT product_id, quantity_sent FROM branch_transfer_items WHERE id = ? AND transfer_id = ?"
                );
                $stmt->execute([$itemId, $transferId]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) continue;

                // Guardar cantidad recibida
                $pdo->prepare(
                    "UPDATE branch_transfer_items SET quantity_received = ? WHERE id = ?"
                )->execute([$qtyReceived, $itemId]);

                // Sumar al stock de destino (sucursal que recibe)
                if ($qtyReceived > 0) {
                    BranchStock::adjust($pdo, $item['product_id'], $transfer['to_branch'], $qtyReceived);
                }
            }

            // Marcar como recibido
            $pdo->prepare("
                UPDATE branch_transfers
                SET status = 'recibido', received_by = ?, received_at = NOW()
                WHERE id = ?
            ")->execute([$userId, $transferId]);

            $pdo->commit();

            echo json_encode([
                'success'     => true,
                'message'     => 'Traslado recibido correctamente.',
                'receipt_url' => APP_BASE . "/branchTransfer/receipt/{$transferId}",
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ── receipt($id): Boleta imprimible ────────────────────────────────────
    public function receipt($id = null): void
    {
        global $pdo;
        $id = intval($id ?? $_GET['id'] ?? 0);

        $transfer = $this->getTransferOrFail($pdo, $id);

        $items = $pdo->prepare("
            SELECT bti.*, p.name AS product_name, p.sku,
                   COALESCE(p.cost, 0) AS unit_cost
            FROM branch_transfer_items bti
            JOIN products p ON p.id = bti.product_id
            WHERE bti.transfer_id = ?
            ORDER BY p.name ASC
        ");
        $items->execute([$id]);
        $items = $items->fetchAll(PDO::FETCH_ASSOC);

        $creator  = $this->getUserName($pdo, $transfer['created_by']);
        $receiver = $transfer['received_by'] ? $this->getUserName($pdo, $transfer['received_by']) : null;

        $this->renderAdmin('admin/branch_transfer_receipt', [
            'transfer'      => $transfer,
            'items'         => $items,
            'creator'       => $creator,
            'receiver'      => $receiver,
            'currentBranch' => $this->currentBranch,
        ]);
    }

    // ── printReceipt($id): PDF con DomPDF — A4 portrait ───────────────────
    public function printReceipt($id = null): void
    {
        global $pdo;
        require_once __DIR__ . '/../vendor/autoload.php';

        $id = intval($id ?? $_GET['id'] ?? 0);

        $transfer = $this->getTransferOrFail($pdo, $id);

        $items = $pdo->prepare("
            SELECT bti.*, p.name AS product_name, p.sku,
                   COALESCE(p.cost, 0) AS unit_cost
            FROM branch_transfer_items bti
            JOIN products p ON p.id = bti.product_id
            WHERE bti.transfer_id = ?
            ORDER BY p.name ASC
        ");
        $items->execute([$id]);
        $items = $items->fetchAll(PDO::FETCH_ASSOC);

        $creator  = $this->getUserName($pdo, $transfer['created_by']);
        $receiver = $transfer['received_by']
            ? $this->getUserName($pdo, $transfer['received_by'])
            : null;

        // Capturar el HTML del template
        ob_start();
        require __DIR__ . '/../views/admin/branch_transfer_print.php';
        $html = ob_get_clean();

        // Generar PDF con DomPDF
        $dompdf = new \Dompdf\Dompdf();
        $options = $dompdf->getOptions();
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('Arial');
        $options->setDpi(96);
        $dompdf->setOptions($options);

        $dompdf->loadHtml($html, 'UTF-8');
        // Carta: 612 x 792 pt (8.5" x 11")
        $dompdf->setPaper([0, 0, 612, 792], 'portrait');
        $dompdf->render();

        $transferNum = str_pad($id, 4, '0', STR_PAD_LEFT);
        header('Content-Type: application/pdf');
        header("Content-Disposition: inline; filename=\"traslado_BT-{$transferNum}.pdf\"");
        echo $dompdf->output();
        exit;
    }

    // ── cancel($id): Cancelar traslado pendiente ───────────────────────────
    public function cancel($id = null): void
    {
        global $pdo;
        header('Content-Type: application/json; charset=utf-8');
        $this->requireSuperAdmin(true);

        $id = intval($id ?? $_POST['id'] ?? 0);
        $transfer = $this->getTransferOrFail($pdo, $id);

        if ($transfer['status'] !== 'pendiente') {
            echo json_encode(['success' => false, 'message' => 'Solo se pueden cancelar traslados pendientes.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Revertir stock a origen
            $items = $pdo->prepare(
                "SELECT product_id, quantity_sent FROM branch_transfer_items WHERE transfer_id = ?"
            );
            $items->execute([$id]);
            foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
                    ->execute([$item['quantity_sent'], $item['product_id']]);
            }

            $userId = $_SESSION['user']['id'] ?? 0;
            $pdo->prepare("
                UPDATE branch_transfers
                SET status = 'cancelado', cancelled_by = ?, cancelled_at = NOW()
                WHERE id = ?
            ")->execute([$userId, $id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Traslado cancelado. Stock revertido.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ── verifyCredentials(): AJAX — verificar credenciales admin/superadmin ────
    public function verifyCredentials(): void
    {
        global $pdo;
        header('Content-Type: application/json; charset=utf-8');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Ingresa usuario y contraseña.']);
            exit;
        }

        $stmt = $pdo->prepare(
            "SELECT id, password, role FROM users WHERE username = ? AND status = 'active' AND is_locked = 0 LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
            exit;
        }
        if (!in_array($user['role'], ['admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Se requiere rol admin o superadmin.']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'OK']);
        exit;
    }

    // ── stockAjax(): Stock disponible de un producto ────────────────────────
    public function stockAjax(): void
    {
        global $pdo;
        header('Content-Type: application/json; charset=utf-8');
        $pid = intval($_GET['product_id'] ?? 0);
        if (!$pid) { echo json_encode(['stock' => 0]); exit; }
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        echo json_encode(['stock' => (int)($stmt->fetchColumn() ?: 0)]);
        exit;
    }

    // ── Helpers privados ───────────────────────────────────────────────────

    private function getTransferOrFail(PDO $pdo, int $id): array
    {
        $stmt = $pdo->prepare("SELECT * FROM branch_transfers WHERE id = ?");
        $stmt->execute([$id]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$t) die('Traslado no encontrado.');
        return $t;
    }

    private function getUserName(PDO $pdo, int $userId): string
    {
        $stmt = $pdo->prepare(
            "SELECT CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) AS name, username FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        return trim($u['name'] ?? '') ?: ($u['username'] ?? "#{$userId}");
    }

    private function requireSuperAdmin(bool $jsonResponse = false): void
    {
        $role = $_SESSION['user']['role'] ?? '';
        if ($role !== 'superadmin') {
            if ($jsonResponse) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo superadmin.']);
                exit;
            }
            die('Acceso denegado. Solo superadmin puede realizar esta acción.');
        }
    }
}
?>
