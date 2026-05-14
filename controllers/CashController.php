<?php
require_once 'BaseController.php';
require_once 'config/config.php';
require_once __DIR__ . '/../helpers/BranchStock.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

class CashController extends BaseController
{
    private $allowedRoles = ['cajero', 'admin', 'superadmin'];

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $this->allowedRoles)) {
            header('Location: ' . APP_BASE . '/admin/index');
            exit;
        }
    }

    private function requireAdminRole()
    {
        $role = $_SESSION['user']['role'] ?? '';
        if (!in_array($role, ['admin', 'superadmin'])) {
            $_SESSION['flash_error'] = 'Solo admin o superadmin puede realizar esta acción.';
            header('Location: ' . APP_BASE . '/cash/dashboard');
            exit;
        }
    }

    private function renderAdmin($view, $data = [])
    {
        extract($data);
        $isSuperAdmin = ($_SESSION['user']['role'] ?? '') === 'superadmin';
        require_once './views/templates/admin_header.php';
        require_once "./views/$view.php";
        require_once './views/templates/admin_footer.php';
    }

    private function getOpenSession($userId = null)
    {
        global $pdo;
        if ($userId === null) {
            $userId = $_SESSION['user']['id'] ?? 0;
        }
        $stmt = $pdo->prepare(
            "SELECT cs.*, CONCAT(u.first_name,' ',u.last_name) AS opener_name, u.username AS opener_username
             FROM cash_sessions cs
             JOIN users u ON u.id = cs.opened_by
             WHERE cs.status IN ('open','pending_close') AND cs.opened_by = ?
             ORDER BY cs.opened_at DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── requestClose: any cajero/admin can request close (sets pending_close) ─
    public function requestClose()
    {
        global $pdo;
        header('Content-Type: application/json');

        $session = $this->getOpenSession();
        if (!$session) {
            echo json_encode(['success' => false, 'message' => 'No hay caja activa.']);
            exit;
        }
        if ($session['status'] === 'pending_close') {
            echo json_encode(['success' => false, 'message' => 'La caja ya está en proceso de cierre.']);
            exit;
        }

        // Validate credentials
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Usuario y contraseña son requeridos.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active' AND is_locked = 0 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
            exit;
        }

        // Mark session as pending_close
        $pdo->prepare("UPDATE cash_sessions SET status='pending_close' WHERE id=?")
            ->execute([$session['id']]);

        echo json_encode(['success' => true, 'message' => 'Solicitud de cierre enviada. El administrador completará el cierre.']);
        exit;
    }

    // ── verifySessionPassword: lock screen — accessible by ALL roles ──────────
    public function verifySessionPassword()
    {
        global $pdo;
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false]);
            exit;
        }
        $pw     = $_POST['password'] ?? '';
        $userId = $_SESSION['user']['id'] ?? 0;
        if (!$pw || !$userId) {
            echo json_encode(['success' => false]);
            exit;
        }
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        echo json_encode(['success' => password_verify($pw, $hash)]);
        exit;
    }

    public function index()
    {
        $session = $this->getOpenSession();
        if ($session) {
            header('Location: ' . APP_BASE . '/cash/dashboard');
        } else {
            header('Location: ' . APP_BASE . '/cash/open');
        }
        exit;
    }

    // ── open: form to open a cash session ────────────────────────────────────
    public function open()
    {
        $session = $this->getOpenSession();
        if ($session) {
            header('Location: ' . APP_BASE . '/cash/dashboard');
            exit;
        }
        $this->renderAdmin('admin/cash_open', []);
    }

    // ── store: POST — create new cash session ─────────────────────────────────
    public function store()
    {
        global $pdo;
        $session = $this->getOpenSession();
        if ($session) {
            echo json_encode(['success' => false, 'message' => 'Ya hay una caja abierta.']);
            exit;
        }

        $openingAmount = floatval($_POST['opening_amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $userId = $_SESSION['user']['id'];

        $stmt = $pdo->prepare(
            "INSERT INTO cash_sessions (opened_by, opening_amount, notes, branch, status) VALUES (?, ?, ?, ?, 'open')"
        );
        $branch = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');
        $stmt->execute([$userId, $openingAmount, $notes, $branch]);

        echo json_encode(['success' => true, 'redirect' => APP_BASE . '/cash/dashboard']);
        exit;
    }

    // ── dashboard: session status + sales + withdrawals ───────────────────────
    public function dashboard()
    {
        global $pdo;
        $session = $this->getOpenSession();
        if (!$session) {
            header('Location: ' . APP_BASE . '/cash/open');
            exit;
        }

        // Sales in this session (from opened_at onwards) — only completado
        $stmtSales = $pdo->prepare(
            "SELECT COALESCE(SUM(total), 0) AS total_sales, COUNT(*) AS total_orders
             FROM orders
             WHERE status = 'completado' AND user_id = ? AND created_at >= ?"
        );
        $stmtSales->execute([$session['opened_by'], $session['opened_at']]);
        $salesData = $stmtSales->fetch(PDO::FETCH_ASSOC);

        // Breakdown by pay_method for this session
        $stmtPay = $pdo->prepare(
            "SELECT pay_method, COALESCE(SUM(total), 0) AS subtotal, COUNT(*) AS qty
             FROM orders
             WHERE status = 'completado' AND user_id = ? AND created_at >= ?
             GROUP BY pay_method"
        );
        $stmtPay->execute([$session['opened_by'], $session['opened_at']]);
        $payBreakdown = [];
        foreach ($stmtPay->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $payBreakdown[$row['pay_method'] ?? 'efectivo'] = $row;
        }

        // Withdrawals in this session
        $stmtW = $pdo->prepare(
            "SELECT cw.*, CONCAT(u.first_name,' ',u.last_name) AS withdrawer_name
             FROM cash_withdrawals cw
             JOIN users u ON u.id = cw.withdrawn_by
             WHERE cw.session_id = ?
             ORDER BY cw.created_at DESC"
        );
        $stmtW->execute([$session['id']]);
        $withdrawals = $stmtW->fetchAll(PDO::FETCH_ASSOC);

        $totalWithdrawn = array_sum(array_column($withdrawals, 'total_amount'));
        $cashSales      = floatval($payBreakdown['efectivo']['subtotal'] ?? 0);
        $expectedCash   = floatval($session['opening_amount']) + $cashSales - $totalWithdrawn;

        $isAdmin = in_array($_SESSION['user']['role'], ['admin', 'superadmin']);

        $this->renderAdmin('admin/cash_dashboard', [
            'session'        => $session,
            'salesData'      => $salesData,
            'payBreakdown'   => $payBreakdown,
            'withdrawals'    => $withdrawals,
            'totalWithdrawn' => $totalWithdrawn,
            'expectedCash'   => $expectedCash,
            'isAdmin'        => $isAdmin,
        ]);
    }

    // ── withdrawal: GET form ──────────────────────────────────────────────────
    public function withdrawal()
    {
        $session = $this->getOpenSession();
        if (!$session) {
            header('Location: ' . APP_BASE . '/cash/open');
            exit;
        }
        $this->renderAdmin('admin/cash_withdrawal', ['session' => $session]);
    }

    // ── pos: POS interface (requires open session, not pending_close) ──────────
    public function pos()
    {
        $session = $this->getOpenSession();
        if (!$session) {
            header('Location: ' . APP_BASE . '/cash/open'); exit;
        }
        if ($session['status'] === 'pending_close') {
            header('Location: ' . APP_BASE . '/cash/dashboard'); exit;
        }
        $this->renderAdmin('admin/cash_pos', ['session' => $session]);
    }

    // ── posSearch: AJAX — search products ────────────────────────────────────
    public function posSearch()
    {
        global $pdo;
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode([]); exit; }

        $branch = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');

        $stmt = $pdo->prepare(
            "SELECT p.id, p.name, p.sku, p.sale_price, p.tax_percent,
                    COALESCE(bps.stock, 0) AS stock
             FROM products p
             LEFT JOIN branch_product_stock bps
                    ON bps.product_id = p.id AND bps.branch = ?
             WHERE p.available = 1
               AND COALESCE(bps.stock, 0) > 0
               AND (p.name LIKE ? OR p.sku LIKE ?)
             ORDER BY p.name LIMIT 20"
        );
        $like = "%$q%";
        $stmt->execute([$branch, $like, $like]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // ── posSale: POST — process sale from POS ────────────────────────────────
    public function posSale()
    {
        global $pdo;
        header('Content-Type: application/json');

        $session = $this->getOpenSession();
        if (!$session || $session['status'] !== 'open') {
            echo json_encode(['success' => false, 'message' => 'No hay caja abierta.']);
            exit;
        }

        $items          = array_map('json_decode', array_map('stripslashes', $_POST['items'] ?? []));
        $clientName     = trim($_POST['client_name'] ?? 'Consumidor Final') ?: 'Consumidor Final';
        $payMethod      = trim($_POST['pay_method']  ?? 'efectivo');
        $amountPaid     = floatval($_POST['amount_paid']    ?? 0);
        $discountType   = trim($_POST['discount_type']  ?? 'fixed'); // 'fixed' | 'percent'
        $discountValue  = floatval($_POST['discount_value'] ?? 0);

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No hay productos en la venta.']);
            exit;
        }

        $branch = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');

        // Validate & build items
        $lineItems = [];
        $subtotal  = 0;
        foreach ($items as $item) {
            if (is_string($item)) $item = json_decode($item);
            $productId = intval($item->id  ?? 0);
            $qty       = intval($item->qty ?? 0);
            $price     = floatval($item->price ?? 0);
            if ($productId <= 0 || $qty <= 0 || $price < 0) continue;

            $stmtP = $pdo->prepare("SELECT id, name, sale_price FROM products WHERE id = ? AND available = 1");
            $stmtP->execute([$productId]);
            $product = $stmtP->fetch(PDO::FETCH_ASSOC);
            $branchStock = $product ? BranchStock::get($pdo, $productId, $branch) : 0;
            if (!$product || $branchStock < $qty) {
                echo json_encode(['success' => false, 'message' => "Stock insuficiente en {$branch} para: {$product['name']} (disponible: {$branchStock})"]);
                exit;
            }
            $lineItems[] = ['product' => $product, 'qty' => $qty, 'price' => $price, 'branchStock' => $branchStock];
            $subtotal   += $price * $qty;
        }

        if (empty($lineItems)) {
            echo json_encode(['success' => false, 'message' => 'Venta vacía o productos inválidos.']);
            exit;
        }

        // Calculate discount & total
        if ($discountType === 'percent') {
            $discountValue  = min(max($discountValue, 0), 100);
            $discountAmount = round($subtotal * $discountValue / 100, 2);
        } else {
            $discountAmount = min(max($discountValue, 0), $subtotal);
        }
        $total = $subtotal - $discountAmount;

        if ($payMethod === 'efectivo' && $amountPaid < $total - 0.01) {
            echo json_encode(['success' => false, 'message' => 'Monto recibido insuficiente.']);
            exit;
        }

        $userId = $_SESSION['user']['id'];

        $pdo->beginTransaction();
        try {
            // Create order with all new fields
            $pdo->prepare(
                "INSERT INTO orders (user_id, total, discount, pay_method, amount_paid, client_name, branch, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'completado', NOW())"
            )->execute([$userId, $total, $discountAmount, $payMethod, $amountPaid, $clientName, $branch]);
            $orderId = $pdo->lastInsertId();

            $adminName = trim(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? ''))
                         ?: ($_SESSION['user']['username'] ?? 'sistema');

            foreach ($lineItems as $li) {
                $pid       = $li['product']['id'];
                $prevStock = BranchStock::get($pdo, $pid, $branch);
                $newStock  = $prevStock - $li['qty'];

                $pdo->prepare(
                    "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
                )->execute([$orderId, $pid, $li['qty'], $li['price']]);

                // Descontar del stock de la sucursal
                BranchStock::adjust($pdo, $pid, $branch, -$li['qty']);

                $pdo->prepare(
                    "INSERT INTO inventory_log
                     (product_id, admin_id, admin_name, branch, change_type, previous_stock, new_stock, description)
                     VALUES (?, ?, ?, ?, 'venta', ?, ?, ?)"
                )->execute([
                    $pid, $userId, $adminName, $branch,
                    $prevStock, $newStock,
                    "Venta POS #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) .
                    " | Cajero: $adminName | Suc: {$branch} | {$li['product']['name']} x{$li['qty']}"
                ]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        $change = max(0, $amountPaid - $total);
        echo json_encode([
            'success'   => true,
            'order_id'  => $orderId,
            'subtotal'  => $subtotal,
            'discount'  => $discountAmount,
            'total'     => $total,
            'change'    => $change,
            'receipt_url' => APP_BASE . "/cash/posReceipt/$orderId",
        ]);
        exit;
    }

    // ── posReceipt: thermal receipt PDF (all data from DB) ───────────────────
    public function posReceipt($orderId)
    {
        global $pdo;
        require_once __DIR__ . '/../vendor/autoload.php';

        $orderId = intval($orderId);

        // Load order
        $stmtO = $pdo->prepare(
            "SELECT o.*, CONCAT(u.first_name,' ',u.last_name) AS cashier_name
             FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?"
        );
        $stmtO->execute([$orderId]);
        $order = $stmtO->fetch(PDO::FETCH_ASSOC);
        if (!$order) { http_response_code(404); echo 'Recibo no encontrado.'; exit; }

        // Load items
        $stmtI = $pdo->prepare(
            "SELECT oi.quantity, oi.price, p.name
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?"
        );
        $stmtI->execute([$orderId]);
        $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);

        // ── Derive all monetary values ────────────────────────────────────────
        $orderTotal  = floatval($order['total']);
        $amountPaid  = floatval($order['amount_paid'] ?? 0);
        $payMethod   = strtolower(trim($order['pay_method'] ?? 'efectivo'));
        $clientName  = trim($order['client_name'] ?? 'Consumidor Final') ?: 'Consumidor Final';

        // Subtotal = sum of items (reliable regardless of how order was saved)
        $subtotal = 0;
        foreach ($items as $it) {
            $subtotal += floatval($it['price']) * intval($it['quantity']);
        }
        // Discount = difference between items subtotal and stored total
        $discount = round($subtotal - $orderTotal, 2);
        if ($discount < 0.01) $discount = 0;  // ignore floating-point dust

        $change = ($payMethod === 'efectivo' && $amountPaid > 0)
                  ? max(0, $amountPaid - $orderTotal)
                  : 0;

        $dt = new DateTime($order['created_at']);
        $dt->setTimezone(new DateTimeZone('America/Managua'));
        $branch = defined('BRANCH') ? BRANCH : 'Farmacia Solei';

        // ── Build product rows ────────────────────────────────────────────────
        $rows = '';
        foreach ($items as $it) {
            $sub  = floatval($it['price']) * intval($it['quantity']);
            $name = htmlspecialchars($it['name']);
            $rows .= '<tr>'
                   . '<td>' . $name . '</td>'
                   . '<td style="text-align:center">' . $it['quantity'] . '</td>'
                   . '<td style="text-align:right">C$' . number_format($it['price'], 2) . '</td>'
                   . '<td style="text-align:right">C$' . number_format($sub, 2) . '</td>'
                   . '</tr>';
        }

        // ── Totals + Payment block ────────────────────────────────────────────
        $payLabel = match($payMethod) {
            'tarjeta'       => 'Tarjeta',
            'transferencia' => 'Transferencia',
            default         => 'Efectivo',
        };

        // ALWAYS show subtotal row
        $totalsHtml  = '<tr><td>Subtotal:</td>'
            . '<td class="right">C$&nbsp;' . number_format($subtotal, 2) . '</td></tr>';

        // Discount row — only when there is a discount
        if ($discount > 0) {
            $discountPct = $subtotal > 0 ? round($discount / $subtotal * 100, 1) : 0;
            $totalsHtml .= '<tr style="color:#c00"><td>Descuento (' . $discountPct . '%):</td>'
                . '<td class="right">-C$&nbsp;' . number_format($discount, 2) . '</td></tr>';
        }

        // Total row — always
        $totalsHtml .= '<tr class="total-row"><td>TOTAL:</td>'
            . '<td class="right">C$&nbsp;' . number_format($orderTotal, 2) . '</td></tr>';

        // Payment rows
        $payHtml = '<tr><td>Forma de pago:</td>'
            . '<td class="right"><strong>' . $payLabel . '</strong></td></tr>';

        // Monto recibido + cambio SOLO si se registró monto (efectivo)
        if ($payMethod === 'efectivo' && $amountPaid > 0) {
            $payHtml .= '<tr><td>Monto recibido:</td>'
                . '<td class="right">C$&nbsp;' . number_format($amountPaid, 2) . '</td></tr>';
            $payHtml .= '<tr style="font-weight:bold"><td>Cambio:</td>'
                . '<td class="right">C$&nbsp;' . number_format($change, 2) . '</td></tr>';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
  @page { margin: 6mm 5mm; }
  html, body {
    font-family: "Courier New", monospace;
    font-size: 12px;
    width: 72mm;
    margin: 0 auto;
    padding: 0;
    text-align: left;
    line-height: 1.5;
  }
  h2 {
    text-align: center;
    font-size: 15px;
    margin: 6px 0 3px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
  }
  .sub {
    text-align: center;
    font-size: 10.5px;
    margin: 0 0 6px;
    color: #333;
  }
  .sep-solid { border-top: 2px solid #000; margin: 7px 0; }
  .sep       { border-top: 1px dashed #555; margin: 7px 0; }
  table { width: 100%; border-collapse: collapse; }
  th {
    border-bottom: 1px solid #000;
    font-size: 10.5px;
    padding: 3px 3px 4px;
    text-align: left;
  }
  td {
    padding: 3px 3px;
    font-size: 11.5px;
    vertical-align: top;
    line-height: 1.45;
  }
  .right  { text-align: right; }
  .center { text-align: center; }
  .total-row td {
    font-size: 14px;
    font-weight: bold;
    padding-top: 5px;
    padding-bottom: 3px;
  }
  .pay-table td { padding: 3px 3px; font-size: 11.5px; }
  .footer {
    text-align: center;
    font-size: 10.5px;
    margin-top: 10px;
    line-height: 1.6;
  }
  .recibo-num td { padding: 2px 3px; font-size: 11px; line-height: 1.5; }
</style>
</head><body>
  <h2>' . htmlspecialchars($branch) . '</h2>
  <p class="sub">Farmacia &mdash; RUC: &mdash;</p>
  <div class="sep-solid"></div>

  <table class="recibo-num">
    <tr><td>Recibo N&deg;:</td><td class="right"><strong>#' . str_pad($orderId, 6, '0', STR_PAD_LEFT) . '</strong></td></tr>
    <tr><td>Fecha:</td><td class="right">' . $dt->format('d/m/Y H:i') . '</td></tr>
    <tr><td>Cajero:</td><td class="right">' . htmlspecialchars(ucwords(strtolower($order['cashier_name']))) . '</td></tr>
    <tr><td>Cliente:</td><td class="right">' . htmlspecialchars($clientName) . '</td></tr>
  </table>

  <div class="sep"></div>

  <table>
    <thead>
      <tr>
        <th style="width:44%">Producto</th>
        <th class="center" style="width:10%">Cant</th>
        <th class="right" style="width:21%">P.Unit</th>
        <th class="right" style="width:25%">Total</th>
      </tr>
    </thead>
    <tbody>' . $rows . '</tbody>
  </table>

  <div class="sep"></div>

  <table>
    ' . $totalsHtml . '
  </table>

  <div class="sep"></div>

  <table class="pay-table">
    ' . $payHtml . '
  </table>

  <div class="sep-solid"></div>
  <p class="footer">
    *** Gracias por su compra ***<br>
    ' . $dt->format('d/m/Y H:i:s') . '
  </p>
</body></html>';

        $dompdf = new \Dompdf\Dompdf();
        $options = $dompdf->getOptions();
        $options->setIsRemoteEnabled(false);
        $dompdf->setOptions($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper([0, 0, 226.77, 780], 'portrait');
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="recibo_' . $orderId . '.pdf"');
        echo $dompdf->output();
        exit;
    }

    // ── voidSale: POST — admin cancels a POS sale, restores stock ────────────
    public function voidSale()
    {
        global $pdo;
        header('Content-Type: application/json');

        $role = $_SESSION['user']['role'] ?? '';
        if (!in_array($role, ['admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Sin permisos.']);
            exit;
        }

        $orderId  = intval($_POST['order_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $userId   = $_SESSION['user']['id'] ?? 0;

        // Verify admin password
        $stmtPw = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmtPw->execute([$userId]);
        $hash = $stmtPw->fetchColumn();
        if (!$hash || !password_verify($password, $hash)) {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
            exit;
        }

        // Load order
        $stmtO = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND status = 'completado'");
        $stmtO->execute([$orderId]);
        $order = $stmtO->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Venta no encontrada o ya anulada.']);
            exit;
        }

        // ── Restrict annulment to TODAY only ─────────────────────────────────
        $orderDate = (new DateTime($order['created_at']))->format('Y-m-d');
        $today     = (new DateTime())->format('Y-m-d');
        if ($orderDate !== $today) {
            echo json_encode(['success' => false, 'message' => 'Solo se pueden anular ventas realizadas el día de hoy.']);
            exit;
        }

        // Load items
        $stmtI = $pdo->prepare(
            "SELECT oi.*, p.name FROM order_items oi
             JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?"
        );
        $stmtI->execute([$orderId]);
        $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);

        $adminName = trim(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? ''))
                     ?: ($_SESSION['user']['username'] ?? 'admin');

        $pdo->beginTransaction();
        try {
            // Mark order as anulado
            $pdo->prepare("UPDATE orders SET status = 'anulado' WHERE id = ?")->execute([$orderId]);

            foreach ($items as $it) {
                $pid = $it['product_id'];
                // Restaurar en la sucursal del usuario que anuló
                $branchVoid = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');
                $prevStock  = BranchStock::get($pdo, $pid, $branchVoid);
                $newStock   = $prevStock + $it['quantity'];

                BranchStock::adjust($pdo, $pid, $branchVoid, +$it['quantity']);

                $pdo->prepare(
                    "INSERT INTO inventory_log
                     (product_id, admin_id, admin_name, branch, change_type, previous_stock, new_stock, description)
                     VALUES (?, ?, ?, ?, 'stock_increase', ?, ?, ?)"
                )->execute([
                    $pid, $userId, $adminName, $branchVoid,
                    $prevStock, $newStock,
                    "Anulación Venta POS #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) .
                    " | Autorizado por: $adminName | {$it['name']} x{$it['quantity']}"
                ]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Venta anulada correctamente. Stock restaurado.']);
        exit;
    }

    // ── saleItems: GET — return items for a given order (admin only) ──────────
    public function saleItems()
    {
        global $pdo;
        header('Content-Type: application/json');

        $role = $_SESSION['user']['role'] ?? '';
        if (!in_array($role, ['admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Sin permisos.']);
            exit;
        }

        $orderId = intval($_GET['order_id'] ?? 0);
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'order_id requerido.']);
            exit;
        }

        // Load order header
        $stmtO = $pdo->prepare(
            "SELECT o.id, o.total, o.discount, o.pay_method, o.client_name, o.status, o.created_at,
                    CONCAT(u.first_name,' ',u.last_name) AS cashier_name
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE o.id = ?"
        );
        $stmtO->execute([$orderId]);
        $order = $stmtO->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Venta no encontrada.']);
            exit;
        }

        // Load items
        $stmtI = $pdo->prepare(
            "SELECT oi.id, oi.quantity, oi.price, p.name, p.sku
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY p.name"
        );
        $stmtI->execute([$orderId]);
        $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'order'   => $order,
            'items'   => $items,
        ]);
        exit;
    }

    public function storeWithdrawal()
    {
        global $pdo;
        $session = $this->getOpenSession();
        if (!$session) {
            echo json_encode(['success' => false, 'message' => 'No hay caja abierta.']);
            exit;
        }

        $denoms = $_POST['denominations'] ?? [];
        $reason = trim($_POST['reason'] ?? '');

        // NIO denominations
        $validDenoms = [1000, 500, 200, 100, 50, 20, 10, 5, 1];
        $denomData = [];
        $total = 0;
        foreach ($validDenoms as $d) {
            $qty = max(0, intval($denoms[$d] ?? 0));
            if ($qty > 0) {
                $denomData[$d] = $qty;
                $total += $d * $qty;
            }
        }

        if ($total <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ingrese al menos una denominación.']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $stmt = $pdo->prepare(
            "INSERT INTO cash_withdrawals (session_id, withdrawn_by, total_amount, denominations, reason)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$session['id'], $userId, $total, json_encode($denomData), $reason]);
        $withdrawalId = $pdo->lastInsertId();

        echo json_encode(['success' => true, 'withdrawal_id' => $withdrawalId, 'total' => $total]);
        exit;
    }

    // ── withdrawalPdf: generate withdrawal PDF ────────────────────────────────
    public function withdrawalPdf($withdrawalId)
    {
        global $pdo;
        $stmt = $pdo->prepare(
            "SELECT cw.*, cs.opening_amount, cs.opened_at,
                    CONCAT(u.first_name,' ',u.last_name) AS withdrawer_name,
                    u.username AS withdrawer_username
             FROM cash_withdrawals cw
             JOIN cash_sessions cs ON cs.id = cw.session_id
             JOIN users u ON u.id = cw.withdrawn_by
             WHERE cw.id = ?"
        );
        $stmt->execute([$withdrawalId]);
        $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$withdrawal) { die('Retiro no encontrado.'); }

        $denominations = json_decode($withdrawal['denominations'], true) ?: [];
        $branch  = defined('BRANCH') ? BRANCH : 'Sucursal Principal';
        $company = defined('COMPANY_NAME') ? COMPANY_NAME : 'Farmacia Solei';

        ob_start();
        require __DIR__ . '/../views/admin/cash_withdrawal_report.php';
        $html = ob_get_clean();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 226.77, 600], 'portrait'); // ~80mm ticket width
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="retiro_' . $withdrawalId . '.pdf"');
        echo $dompdf->output();
        exit;
    }

    // ── closeCash: POST — save count + generate closing PDF ──────────────────
    public function closeCash()
    {
        global $pdo;
        $this->requireAdminRole();

        // Admin can close ANY session by passing session_id in POST
        $sessionId = intval($_POST['session_id'] ?? 0);

        if ($sessionId > 0) {
            // Load the specific session (must be open)
            $stmt = $pdo->prepare(
                "SELECT cs.*, CONCAT(u.first_name,' ',u.last_name) AS opener_name
                 FROM cash_sessions cs JOIN users u ON u.id = cs.opened_by
                 WHERE cs.id = ? AND cs.status IN ('open','pending_close')"
            );
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$session) {
                echo json_encode(['success' => false, 'message' => 'Sesión no encontrada o ya cerrada.']);
                exit;
            }
        } else {
            $session = $this->getOpenSession();
            if (!$session) {
                echo json_encode(['success' => false, 'message' => 'No hay caja abierta.']);
                exit;
            }
        }

        $denoms = $_POST['denominations'] ?? [];
        $validDenoms = [1000, 500, 200, 100, 50, 20, 10, 5, 1];
        $denomData = [];
        $countedAmount = 0;
        foreach ($validDenoms as $d) {
            $qty = max(0, intval($denoms[$d] ?? 0));
            if ($qty > 0) {
                $denomData[$d] = $qty;
                $countedAmount += $d * $qty;
            }
        }

        // Recalculate expected
        $stmtSales = $pdo->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status='completado' AND created_at >= ?"
        );
        $stmtSales->execute([$session['opened_at']]);
        $totalSales = floatval($stmtSales->fetchColumn());

        $stmtW = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM cash_withdrawals WHERE session_id=?");
        $stmtW->execute([$session['id']]);
        $totalWithdrawn = floatval($stmtW->fetchColumn());

        $expected   = floatval($session['opening_amount']) + $totalSales - $totalWithdrawn;
        $difference = round($countedAmount - $expected, 2);

        $userId = $_SESSION['user']['id'];

        $pdo->beginTransaction();
        try {
            // Save closing count
            $pdo->prepare(
                "INSERT INTO cash_closing_counts
                 (session_id, counted_by, counted_amount, denominations, expected_amount, difference)
                 VALUES (?, ?, ?, ?, ?, ?)"
            )->execute([$session['id'], $userId, $countedAmount, json_encode($denomData), $expected, $difference]);

            // Close session
            $pdo->prepare(
                "UPDATE cash_sessions SET status='closed', closed_by=?, closed_at=NOW() WHERE id=?"
            )->execute([$userId, $session['id']]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'pdf_url' => APP_BASE . '/cash/closingPdf/' . $session['id'],
        ]);
        exit;
    }

    // ── closingPdf: full closing report ──────────────────────────────────────
    public function closingPdf($sessionId)
    {
        global $pdo;
        $this->requireAdminRole();

        // Session
        $stmt = $pdo->prepare(
            "SELECT cs.*,
                    CONCAT(uo.first_name,' ',uo.last_name) AS opener_name,
                    CONCAT(uc.first_name,' ',uc.last_name) AS closer_name
             FROM cash_sessions cs
             JOIN users uo ON uo.id = cs.opened_by
             LEFT JOIN users uc ON uc.id = cs.closed_by
             WHERE cs.id = ?"
        );
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$session) { die('Sesión no encontrada.'); }

        // Closing count
        $stmtC = $pdo->prepare(
            "SELECT ccc.*, CONCAT(u.first_name,' ',u.last_name) AS counter_name
             FROM cash_closing_counts ccc
             JOIN users u ON u.id = ccc.counted_by
             WHERE ccc.session_id = ?"
        );
        $stmtC->execute([$sessionId]);
        $closingCount = $stmtC->fetch(PDO::FETCH_ASSOC);
        $closingDenoms = $closingCount ? (json_decode($closingCount['denominations'], true) ?: []) : [];

        // Sales during session
        $stmtSales = $pdo->prepare(
            "SELECT COALESCE(SUM(total),0) AS total_sales, COUNT(*) AS order_count
             FROM orders WHERE status='completado' AND created_at >= ? AND created_at <= ?"
        );
        $closedAt = $session['closed_at'] ?? date('Y-m-d H:i:s');
        $stmtSales->execute([$session['opened_at'], $closedAt]);
        $salesData = $stmtSales->fetch(PDO::FETCH_ASSOC);

        // Withdrawals
        $stmtW = $pdo->prepare(
            "SELECT cw.*, CONCAT(u.first_name,' ',u.last_name) AS withdrawer_name
             FROM cash_withdrawals cw
             JOIN users u ON u.id = cw.withdrawn_by
             WHERE cw.session_id = ? ORDER BY cw.created_at ASC"
        );
        $stmtW->execute([$sessionId]);
        $withdrawals = $stmtW->fetchAll(PDO::FETCH_ASSOC);
        $totalWithdrawn = array_sum(array_column($withdrawals, 'total_amount'));

        $branch  = defined('BRANCH') ? BRANCH : 'Sucursal Principal';
        $company = defined('COMPANY_NAME') ? COMPANY_NAME : 'Farmacia Solei';

        ob_start();
        require __DIR__ . '/../views/admin/cash_closing_report.php';
        $html = ob_get_clean();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 226.77, 800], 'portrait'); // ~80mm thermal roll
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="cierre_caja_' . $sessionId . '.pdf"');
        echo $dompdf->output();
        exit;
    }
    // ── history: all sessions, admin/superadmin only ──────────────────────────
    public function history()
    {
        global $pdo;
        $this->requireAdminRole();

        $filterDateFrom  = trim($_GET['date_from'] ?? '');
        $filterDateTo    = trim($_GET['date_to']   ?? '');
        if (empty($filterDateFrom) && !empty($_GET['date'])) {
            $filterDateFrom = $filterDateTo = trim($_GET['date']);
        }
        $page    = max(1, intval($_GET['page'] ?? 1));
        $perPage = 5;

        $currentBranch = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');

        $where = ["1=1"];
        $params = [];
        // Filtrar siempre por la sucursal actual (branch registrado en la sesión de caja)
        $where[] = 'cs.branch = ?';
        $params[] = $currentBranch;
        if ($filterDateFrom) { $where[] = 'DATE(cs.opened_at) >= ?'; $params[] = $filterDateFrom; }
        if ($filterDateTo)   { $where[] = 'DATE(cs.opened_at) <= ?'; $params[] = $filterDateTo; }
        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        // Total count for pagination
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM cash_sessions cs $whereSQL");
        $stmtCount->execute($params);
        $totalSessions = (int) $stmtCount->fetchColumn();
        $totalPages    = max(1, (int) ceil($totalSessions / $perPage));
        $page          = min($page, $totalPages);
        $offset        = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("
            SELECT
                cs.id, cs.status, cs.opening_amount, cs.opened_at, cs.closed_at, cs.notes,
                cs.opened_by,
                CONCAT(uo.first_name,' ',uo.last_name) AS opener_name,
                uo.username AS opener_username,
                COALESCE(
                    (SELECT SUM(o.total) FROM orders o
                     WHERE o.status='completado' AND o.created_at >= cs.opened_at
                       AND (cs.closed_at IS NULL OR o.created_at <= cs.closed_at)
                    ), 0) AS total_sales,
                COALESCE(
                    (SELECT SUM(w.total_amount) FROM cash_withdrawals w
                     WHERE w.session_id = cs.id), 0) AS total_withdrawals,
                ccc.counted_amount,
                ccc.expected_amount,
                ccc.difference
            FROM cash_sessions cs
            JOIN users uo ON uo.id = cs.opened_by
            LEFT JOIN cash_closing_counts ccc ON ccc.session_id = cs.id
            $whereSQL
            ORDER BY cs.opened_at DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Withdrawals per session
        $withdrawalsBySession = [];
        if ($sessions) {
            $ids = implode(',', array_map('intval', array_column($sessions, 'id')));
            $wStmt = $pdo->query("
                SELECT cw.*, CONCAT(u.first_name,' ',u.last_name) AS withdrawer_name
                FROM cash_withdrawals cw
                JOIN users u ON u.id = cw.withdrawn_by
                WHERE cw.session_id IN ($ids)
                ORDER BY cw.created_at ASC
            ");
            foreach ($wStmt->fetchAll(PDO::FETCH_ASSOC) as $w) {
                $withdrawalsBySession[$w['session_id']][] = $w;
            }
        }

        // Users for filter dropdown
        $users = $pdo->query("
            SELECT DISTINCT u.id, u.username, CONCAT(u.first_name,' ',u.last_name) AS full_name
            FROM users u JOIN cash_sessions cs ON cs.opened_by = u.id
            ORDER BY u.first_name
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Current open session cash balance (current admin's own session)
        $openSession = $this->getOpenSession(); // scoped to current user by default
        $currentCash = null;
        if ($openSession) {
            $s = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='completado' AND created_at >= ?");
            $s->execute([$openSession['opened_at']]);
            $openSales = floatval($s->fetchColumn());
            $s2 = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM cash_withdrawals WHERE session_id=?");
            $s2->execute([$openSession['id']]);
            $openWithdrawals = floatval($s2->fetchColumn());
            $currentCash = floatval($openSession['opening_amount']) + $openSales - $openWithdrawals;
        }

        $this->renderAdmin('admin/cash_history', [
            'sessions'            => $sessions,
            'withdrawalsBySession' => $withdrawalsBySession,
            'users'               => $users,
            'filterDateFrom'      => $filterDateFrom,
            'filterDateTo'        => $filterDateTo,
            'filterDate'          => $filterDateFrom,
            'page'                => $page,
            'perPage'             => $perPage,
            'totalPages'          => $totalPages,
            'totalSessions'       => $totalSessions,
            'openSession'         => $openSession,
            'currentCash'         => $currentCash,
        ]);
    }

    // ── reprintAuth: POST — verify admin/superadmin credentials ──────────────
    public function reprintAuth()
    {
        global $pdo;
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Usuario y contraseña son requeridos.']);
            exit;
        }

        $stmt = $pdo->prepare(
            "SELECT * FROM users
             WHERE username = ? AND status = 'active' AND is_locked = 0
             LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
            exit;
        }

        if (!in_array($user['role'], ['admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Solo admin o superadmin pueden acceder a las reimpresiones.']);
            exit;
        }

        echo json_encode(['success' => true]);
        exit;
    }

    // ── reprintSales: GET — list sales for reprint panel (AJAX) ──────────────
    public function reprintSales()
    {
        global $pdo;
        header('Content-Type: application/json');

        $sessionId  = intval($_GET['session_id'] ?? 0);
        $dateFrom   = trim($_GET['date_from']   ?? '');
        $dateTo     = trim($_GET['date_to']     ?? '');
        $searchUser = trim($_GET['search_user'] ?? '');

        $where  = ["o.status = 'completado'"];
        $params = [];

        if ($sessionId > 0) {
            // Ventas pertenecientes a una sesión concreta (por el usuario que abrió caja)
            $stmtSess = $pdo->prepare("SELECT opened_by, opened_at FROM cash_sessions WHERE id = ? LIMIT 1");
            $stmtSess->execute([$sessionId]);
            $sess = $stmtSess->fetch(PDO::FETCH_ASSOC);

            if ($sess) {
                $where[]  = 'o.user_id = ?';
                $params[] = $sess['opened_by'];
                $where[]  = 'o.created_at >= ?';
                $params[] = $sess['opened_at'];
            }
        } else {
            // Filtro por rango de fechas
            if ($dateFrom) {
                $where[]  = 'DATE(o.created_at) >= ?';
                $params[] = $dateFrom;
            }
            if ($dateTo) {
                $where[]  = 'DATE(o.created_at) <= ?';
                $params[] = $dateTo;
            }
            // Filtro por usuario/cajero (nombre o username)
            if ($searchUser) {
                $like     = "%$searchUser%";
                $where[]  = "(u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name,' ',u.last_name) LIKE ?)";
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        $stmt = $pdo->prepare(
            "SELECT o.id, o.total, o.pay_method, o.client_name, o.created_at,
                    CONCAT(u.first_name,' ',u.last_name) AS cashier_name
             FROM orders o
             JOIN users u ON u.id = o.user_id
             $whereSQL
             ORDER BY o.created_at DESC
             LIMIT 200"
        );
        $stmt->execute($params);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($sales);
        exit;
    }
}
