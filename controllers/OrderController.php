<?php
// controllers/OrderController.php

require_once 'AdminController.php';
require_once 'models/ProductOrder.php';
require_once 'models/Product.php';
require_once __DIR__ . '/../helpers/BranchStock.php';
require_once 'config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

class OrderController extends AdminController
{
    private $productModel;
    private $orderModel;

    public function __construct()
    {
        parent::__construct(); // Verifica rol admin o superadmin
        global $pdo;
        $this->productModel = new Product($pdo);
        $this->orderModel = new ProductOrder($pdo);
    }

    // Formulario para crear pedido
    public function create()
    {
        global $pdo;
        $suppliers = $pdo->query(
            "SELECT id, name FROM suppliers WHERE active = 1 ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/orders_create', ['suppliers' => $suppliers]);
    }

    // Guardar nuevo pedido
    public function store()
    {
        global $pdo;
        $quantities  = $_POST['quantities']  ?? [];
        $unitCosts   = $_POST['unit_costs']  ?? [];
        $supplierId  = intval($_POST['supplier_id'] ?? 0) ?: null;

        $orderItems = [];
        foreach ($quantities as $pid => $qty) {
            if (intval($qty) > 0) {
                $orderItems[intval($pid)] = intval($qty);
            }
        }
        if (empty($orderItems)) {
            echo json_encode(['success' => false, 'message' => 'No se seleccionó ningún producto.']);
            exit;
        }

        $totalOrder   = 0;
        $itemsWithCost = [];
        foreach ($orderItems as $pid => $qty) {
            // Use supplier price sent from form, fall back to DB cost if missing
            $cost = floatval($unitCosts[$pid] ?? 0);
            if ($cost <= 0) {
                $stmt = $pdo->prepare("SELECT cost FROM products WHERE id = ?");
                $stmt->execute([$pid]);
                $cost = floatval($stmt->fetchColumn());
            }
            $totalOrder    += $cost * $qty;
            $itemsWithCost[] = ['pid' => $pid, 'qty' => $qty, 'cost' => $cost];
        }

        $adminId = $_SESSION['user']['id'];
        $branch  = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');
        $orderId = $this->orderModel->createOrder($adminId, $_SESSION['user']['username'], $branch, $totalOrder);

        // Guardar supplier_id en el pedido
        if ($supplierId) {
            $pdo->prepare("UPDATE product_orders SET supplier_id = ? WHERE id = ?")
                ->execute([$supplierId, $orderId]);
        }

        foreach ($itemsWithCost as $item) {
            $this->orderModel->addOrderItem($orderId, $item['pid'], $item['qty'], $item['cost']);
        }

        echo json_encode(['success' => true, 'message' => 'Pedido creado correctamente.']);
        exit;
    }

    // Listar pedidos — filtrado por sucursal actual
    public function index()
    {
        global $pdo;
        $branch = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');
        $stmt = $pdo->prepare(
            "SELECT po.*, s.name AS supplier_name
             FROM product_orders po
             LEFT JOIN suppliers s ON s.id  = po.supplier_id
             WHERE po.branch = ?
             ORDER BY po.order_date DESC"
        );
        $stmt->execute([$branch]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->renderAdmin('admin/orders_list', ['orders' => $orders]);
    }

    // Aplicar pedido (pending → applied)
    public function updateStatus($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM product_orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order || $order['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Estado no válido.']);
            exit;
        }
        $userId = $_SESSION['user']['id'];
        $pdo->prepare("UPDATE product_orders
                       SET status='applied', applied_by=?, applied_at=NOW()
                       WHERE id = ?")
            ->execute([$userId, $id]);
        echo json_encode(['success' => true, 'message' => 'Pedido aplicado.']);
        exit;
    }

    // Ver / Editar pedido
    public function edit($id)
    {
        global $pdo;
        $branch = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');
        // Cargar pedido verificando que pertenezca a esta sucursal (por branch del pedido, no del usuario)
        $stmt = $pdo->prepare(
            "SELECT po.*, s.name AS supplier_name
             FROM product_orders po
             LEFT JOIN suppliers s ON s.id  = po.supplier_id
             WHERE po.id = ? AND po.branch = ?"
        );
        $stmt->execute([$id, $branch]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            $_SESSION['flash'] = "Pedido no encontrado o no pertenece a esta sucursal.";
            header("Location: " . APP_BASE . "/order/index");
            exit;
        }

        $stmtItems = $pdo->prepare("
            SELECT poi.product_id, poi.quantity, p.name, p.sku, p.cost
            FROM product_order_items poi
            JOIN products p ON poi.product_id = p.id
            WHERE poi.order_id = ?
        ");
        $stmtItems->execute([$id]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Calcular totales
        $orderTotal = 0;
        foreach ($orderItems as $it) {
            $orderTotal += ($it['cost'] ?? 0) * $it['quantity'];
        }

        // Active suppliers for the selector
        $suppliers = $pdo->query(
            "SELECT id, name FROM suppliers WHERE active = 1 ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/orders_edit', [
            'order'      => $order,
            'orderItems' => $orderItems,
            'orderTotal' => $orderTotal,
            'suppliers'  => $suppliers,
        ]);
    }

    // Asignar / cambiar proveedor de un pedido (AJAX)
    public function updateSupplier()
    {
        global $pdo;
        header('Content-Type: application/json');
        $orderId    = intval($_POST['order_id'] ?? 0);
        $supplierId = intval($_POST['supplier_id'] ?? 0) ?: null;
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'ID de pedido inválido.']);
            exit;
        }
        $pdo->prepare("UPDATE product_orders SET supplier_id = ? WHERE id = ?")
            ->execute([$supplierId, $orderId]);
        echo json_encode(['success' => true]);
        exit;
    }

    // Actualizar pedido (solo si pending)
    public function update($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM product_orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order || $order['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Solo se pueden editar pedidos pendientes.']);
            exit;
        }

        $quantities = $_POST['quantities'] ?? [];
        $totalQty = 0;
        foreach ($quantities as $qty) {
            $totalQty += max(0, intval($qty));
        }

        if ($totalQty === 0) {
            echo json_encode(['success' => false, 'message' => 'El pedido no puede quedar en blanco. Debe ingresar al menos un artículo.']);
            exit;
        }

        $total = 0;

        foreach ($quantities as $pid => $qty) {
            $qty = max(0, intval($qty));
            $pdo->prepare("UPDATE product_order_items SET quantity = ? WHERE order_id = ? AND product_id = ?")
                ->execute([$qty, $id, intval($pid)]);

            // Calcular total
            $stmtP = $pdo->prepare("SELECT cost FROM products WHERE id = ?");
            $stmtP->execute([intval($pid)]);
            $cost = floatval($stmtP->fetchColumn() ?: 0);
            $total += $cost * $qty;
        }

        // Eliminar items con qty 0
        $pdo->prepare("DELETE FROM product_order_items WHERE order_id = ? AND quantity = 0")
            ->execute([$id]);

        // Actualizar total
        $pdo->prepare("UPDATE product_orders SET total = ? WHERE id = ?")
            ->execute([$total, $id]);

        echo json_encode(['success' => true, 'message' => 'Pedido actualizado correctamente.']);
        exit;
    }

    // Formulario de entrada de mercancía
    public function goodsEntry($orderId)
    {
        global $pdo;
        $branch = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');

        // 1) Obtener el pedido verificando que pertenezca a esta sucursal (por branch del pedido)
        $stmt = $pdo->prepare(
            "SELECT po.*, s.name AS supplier_name
             FROM product_orders po
             LEFT JOIN suppliers s ON s.id  = po.supplier_id
             WHERE po.id = ? AND po.branch = ?"
        );
        $stmt->execute([$orderId, $branch]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            $_SESSION['flash'] = "Pedido no encontrado o no pertenece a esta sucursal.";
            header("Location: " . APP_BASE . "/order/index");
            exit;
        }
        // 2) Verificar estado
        if ($order['status'] !== 'applied') {
            $_SESSION['flash'] = "Solo se puede dar entrada a pedidos aplicados.";
            header("Location: " . APP_BASE . "/order/index");
            exit;
        }
        // 3) Obtener los ítems: cantidad ordenada y costo unitario
        $stmtItems = $pdo->prepare(
            "SELECT 
            oi.product_id,
            oi.quantity AS ordered_qty,
            p.name,
            p.sku,
            p.cost
         FROM product_order_items oi
         JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = ?"
        );
        $stmtItems->execute([$orderId]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // 4) Calcular Subtotal / IVA / Total del Sistema
        $systemSubtotal = 0.00;
        foreach ($orderItems as $it) {
            $systemSubtotal += $it['cost'] * $it['ordered_qty'];
        }
        $systemTax = round($systemSubtotal * 0.15, 2);
        $systemTotal = round($systemSubtotal + $systemTax, 2);

        // 5) Renderizar la vista
        $this->renderAdmin('admin/goods_entry', [
            'order'          => $order,
            'orderItems'     => $orderItems,
            'systemSubtotal' => $systemSubtotal,
            'systemTax'      => $systemTax,
            'systemTotal'    => $systemTotal,
            'savedInvoiceSub' => $order['invoice_subtotal'],
            'savedInvoiceTax' => $order['invoice_tax'],
        ]);
    }

    // Guardar factura física en la orden (AJAX)
    public function saveInvoice($orderId)
    {
        global $pdo;
        $sub = floatval($_POST['invoice_subtotal'] ?? 0);
        $tax = floatval($_POST['invoice_tax'] ?? 0);

        $stmt = $pdo->prepare("UPDATE product_orders SET invoice_subtotal = ?, invoice_tax = ? WHERE id = ?");
        $ok = $stmt->execute([$sub, $tax, $orderId]);

        echo json_encode([
            'success' => (bool) $ok,
            'message' => $ok ? 'Factura guardada con éxito.' : 'Error al guardar factura.'
        ]);
        exit;
    }


    // Procesar entrada de mercancía
    public function storeGoodsEntry($orderId)
    {
        global $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1) Verificar que la orden exista y esté en estado 'applied'
        $stmt = $pdo->prepare("SELECT * FROM product_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order || $order['status'] !== 'applied') {
            echo json_encode(['success' => false, 'message' => 'Pedido inválido o ya procesado.']);
            exit;
        }

        // 2) Validar credenciales admin/superadmin (se inyectan desde el modal)
        $username = trim($_POST['confirm_username'] ?? '');
        $password = trim($_POST['confirm_password'] ?? '');
        if (!$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Credenciales obligatorias.']);
            exit;
        }
        $stmtU = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmtU->execute([$username]);
        $user = $stmtU->fetch(PDO::FETCH_ASSOC);
        if (
            !$user
            || !password_verify($password, $user['password'])
            || !in_array($user['role'], ['admin', 'superadmin'])
        ) {
            echo json_encode(['success' => false, 'message' => 'Credenciales inválidas o sin permiso.']);
            exit;
        }
        $receiverId = $user['id'];

        // 3) Recoger datos de la factura física y las cantidades recibidas
        $received = $_POST['received_quantities'] ?? [];
        $justifs = $_POST['justifications'] ?? [];
        $invoiceSub = floatval($_POST['invoice_subtotal'] ?? 0);
        $invoiceTax = floatval($_POST['invoice_tax'] ?? 0);
        $invoiceTotal = $invoiceSub + $invoiceTax;

        // 4) Cargar los ítems de la orden y el costo unitario
        $stmtIt = $pdo->prepare("
        SELECT 
          poi.product_id,
          poi.quantity   AS ordered_qty,
          p.cost,
          p.name
        FROM product_order_items poi
        JOIN products p ON poi.product_id = p.id
        WHERE poi.order_id = ?
    ");
        $stmtIt->execute([$orderId]);
        $items = $stmtIt->fetchAll(PDO::FETCH_ASSOC);

        // 5) Validar justificaciones y calcular montos del sistema
        $errors = [];
        $sysSub = 0;
        $sysTax = 0;
        foreach ($items as $it) {
            $pid = $it['product_id'];
            $oQty = (int) $it['ordered_qty'];
            $rQty = max(0, intval($received[$pid] ?? 0));

            if ($rQty < $oQty && empty(trim($justifs[$pid] ?? ''))) {
                $errors[] = "{$it['name']}: falta justificación.";
            }

            $lineSub = $it['cost'] * $rQty;
            $lineTax = $lineSub * 0.15; // IVA fijo 15%
            $sysSub += $lineSub;
            $sysTax += $lineTax;
        }
        
        $sysSub = round($sysSub, 4);
        $sysTax = round($sysTax, 4);
        $sysTotal = round($sysSub + $sysTax, 4);
        
        if ($errors) {
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit;
        }

        // 6) Transacción: insertar cabecera, detalle, actualizar stock y marcar orden
        try {
            $pdo->beginTransaction();

            // 6.1) Cabecera de entrada con branch tomado del pedido
            $stmtE = $pdo->prepare("
            INSERT INTO goods_entries
              (order_id, received_by, branch, invoice_subtotal, invoice_tax,
               system_subtotal, system_tax, system_total)
            VALUES (?,?,?,?,?,?,?,?)
        ");
            $stmtE->execute([
                $orderId,
                $receiverId,
                $order['branch'],   // branch fijo al momento de la entrada
                $invoiceSub,
                $invoiceTax,
                $sysSub,
                $sysTax,
                $sysTotal
            ]);
            $entryId = $pdo->lastInsertId();

            // 6.2) Detalle y actualización de stock
            $stmtD = $pdo->prepare("
            INSERT INTO goods_entry_items
              (goods_entry_id, product_id, quantity_received, justification)
            VALUES (?,?,?,?)
        ");
            $branch = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');
            foreach ($items as $it) {
                $pid  = $it['product_id'];
                $rQty = max(0, intval($received[$pid] ?? 0));
                $just = $justifs[$pid] ?? null;

                $stmtD->execute([$entryId, $pid, $rQty, $just]);
                // Sumar al stock de la sucursal que recibe
                BranchStock::adjust($pdo, $pid, $branch, $rQty);
            }

            // 6.3) Marcar pedido como recibido
            $pdo->prepare("UPDATE product_orders SET status = 'received' WHERE id = ?")
                ->execute([$orderId]);

            $pdo->commit();

            // 6.4) Detectar notas de débito
            // Aseguramos redondeo a 4 decimales para evitar diferencias de punto flotante en la suma manual
            $invoiceTotalRound = round($invoiceTotal, 4);
            $sysTotalRound = round($sysTotal, 4);
            
            // Nota de débito por COSTOS: factura > sistema
            $hasCostDebitNote = ($invoiceTotalRound > $sysTotalRound);

            // Nota de débito por CANTIDAD: algún recibido > ordenado
            $hasQtyDebitNote = false;
            foreach ($items as $it) {
                $pid = $it['product_id'];
                $oQty = (int) $it['ordered_qty'];
                $rQty = max(0, intval($received[$pid] ?? 0));
                if ($rQty > $oQty) {
                    $hasQtyDebitNote = true;
                    break;
                }
            }

            echo json_encode([
                'success' => true, 
                'message' => 'Entrada aplicada',
                'has_debit_note' => $hasCostDebitNote,
                'has_qty_debit_note' => $hasQtyDebitNote,
                'entry_id' => $entryId,
                'order_id' => $orderId
            ]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error interno al procesar la entrada: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    public function debitNote($id) {
        $this->generateDebitNotePDF($id);
    }

    public function qtyDebitNote($id) {
        $this->generateDebitNotePDF($id);
    }

    // Resumen de entrada de mercancía (vista inline)
    public function entrySummary($orderId)
    {
        global $pdo;

        // Obtener entrada
        $stmt = $pdo->prepare("
            SELECT ge.*, u.first_name, u.last_name
            FROM goods_entries ge
            LEFT JOIN users u ON ge.received_by = u.id
            WHERE ge.order_id = ?
            ORDER BY ge.id DESC LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$entry) {
            $_SESSION['flash'] = 'No se encontró entrada de mercancía.';
            header('Location: ' . APP_BASE . '/order/index');
            exit;
        }

        // Obtener items
        $stmtItems = $pdo->prepare("
            SELECT gei.product_id, p.sku, p.name, p.cost AS cost_unit,
                   poi.quantity AS ordered_qty,
                   gei.quantity_received AS received_qty,
                   gei.justification
            FROM goods_entry_items gei
            JOIN products p ON p.id = gei.product_id
            JOIN goods_entries ge ON ge.id = gei.goods_entry_id
            JOIN product_order_items poi
              ON poi.order_id = ge.order_id AND poi.product_id = gei.product_id
            WHERE gei.goods_entry_id = ?
        ");
        $stmtItems->execute([$entry['id']]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Calcular diferencias
        $qtyDiffs = [];
        $hasCostDebitNote = false;
        $hasQtyDebitNote = false;
        $invoiceTotal = floatval($entry['invoice_subtotal']) + floatval($entry['invoice_tax']);
        $systemTotal = floatval($entry['system_total']);

        if ($invoiceTotal > $systemTotal) {
            $hasCostDebitNote = true;
        }

        foreach ($items as $it) {
            $diff = $it['received_qty'] - $it['ordered_qty'];
            if ($diff != 0) {
                $qtyDiffs[] = $it;
            }
            if ($it['received_qty'] > $it['ordered_qty']) {
                $hasQtyDebitNote = true;
            }
        }

        $dt = new \DateTime($entry['received_at'] ?? $entry['created_at'], new \DateTimeZone('UTC'));
        $dt->setTimezone(new \DateTimeZone('America/Managua'));
        $receptionDate = $dt->format('d/m/Y H:i:s');
        $userName = trim(($entry['first_name'] ?? '') . ' ' . ($entry['last_name'] ?? ''));

        $this->renderAdmin('admin/entry_summary', [
            'entry' => $entry,
            'items' => $items,
            'qtyDiffs' => $qtyDiffs,
            'hasCostDebitNote' => $hasCostDebitNote,
            'hasQtyDebitNote' => $hasQtyDebitNote,
            'invoiceTotal' => $invoiceTotal,
            'systemTotal' => $systemTotal,
            'receptionDate' => $receptionDate,
            'userName' => $userName,
            'orderId' => $orderId,
        ]);
    }


    // Generar PDF de boleta de recepción
    // … dentro de OrderController …

    public function goodsEntryReport($orderId)
    {
        global $pdo;

        // 1) Leer la última entrada de mercancía
        $stmt = $pdo->prepare("
      SELECT
        ge.id               AS entry_id,
        ge.order_id,
        ge.invoice_subtotal,
        ge.invoice_tax,
        ge.received_at      AS applied_at,
        u.first_name,
        u.last_name,
        u.username          AS applied_username,
        po.status           AS order_status,
        s.name              AS supplier_name
      FROM goods_entries ge
      LEFT JOIN users u ON ge.received_by = u.id
      LEFT JOIN product_orders po ON ge.order_id = po.id
      LEFT JOIN suppliers s ON s.id = po.supplier_id
      WHERE ge.order_id = ?
      ORDER BY ge.id DESC
      LIMIT 1
    ");
        $stmt->execute([$orderId]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$entry) {
            die('Entrada de mercancía no encontrada.');
        }

        // 2) Obtener detalle trayendo el cost_unit desde products.cost
        $stmtItems = $pdo->prepare("
      SELECT
        gei.product_id,
        p.sku,
        p.name,
        poi.quantity           AS ordered_qty,
        gei.quantity_received  AS received_qty,
        p.cost                 AS cost_unit,
        gei.justification
      FROM goods_entry_items gei
      JOIN products p   ON p.id = gei.product_id
      JOIN goods_entries ge ON ge.id = gei.goods_entry_id
      JOIN product_order_items poi
        ON poi.order_id   = ge.order_id
       AND poi.product_id = gei.product_id
      WHERE gei.goods_entry_id = ?
    ");
        $stmtItems->execute([$entry['entry_id']]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // 3) Formatear fecha de UTC a America/Managua (UTC−6)
        $dt = new DateTime($entry['applied_at'], new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('America/Managua'));
        $receptionDate = $dt->format('d/m/Y H:i:s');

        // 4) Nombre completo de quien confirmó con credenciales
        $fullName    = trim($entry['first_name'] . ' ' . $entry['last_name']);
        $appliedUser = ucwords(strtolower($fullName)) ?: '—';

        // 5) Calcular total del sistema (costo_unit * recibido)
        $systemSubtotal = array_reduce($orderItems, function ($sum, $i) {
            return $sum + ($i['cost_unit'] * $i['received_qty']);
        }, 0);
        // 15% de IVA
        $systemTax = round($systemSubtotal * 0.15, 2);
        $systemTotal = round($systemSubtotal + $systemTax, 2);


        // 6) Preparar variables de factura
        $invoiceSubtotal = floatval($entry['invoice_subtotal']);
        $invoiceTax = floatval($entry['invoice_tax']);
        $invoiceTotal = $invoiceSubtotal + $invoiceTax;
        
        // Estado dinámico traducido
        $statusTranslations = [
            'pending' => 'PENDIENTE',
            'applied' => 'APLICADO',
            'received' => 'RECIBIDO'
        ];
        $rawStatus = strtolower($entry['order_status'] ?? 'applied');
        $orderStatus = $statusTranslations[$rawStatus] ?? strtoupper($rawStatus);

        // 7) Capturar la vista HTML con todas las vars definidas
        ob_start();
        // En la vista tendrás disponibles:
        //   $entry, $orderItems, $receptionDate, $appliedUser,
        //   $invoiceSubtotal, $invoiceTax, $invoiceTotal, $systemTotal
        require __DIR__ . '/../views/admin/goods_entry_report.php';
        $html = ob_get_clean();

        // 8) Generar el PDF landscape
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="entrada_' . $entry['entry_id'] . '.pdf"');
        echo $dompdf->output();
        exit;
    }



    // Nota de débito UNIFICADA (cantidades arriba + costos abajo en un solo PDF)
    /**
     * @param int $entryId ID de la entrada (goods_entries.id)
     */
    private function generateDebitNotePDF($entryId)
    {
        global $pdo;

        // 1) Cargar datos de la entrada y usuario
        $stmt = $pdo->prepare("
            SELECT ge.*, u.first_name, u.last_name, u.branch,
                   s.name AS supplier_name
            FROM goods_entries ge
            JOIN users u ON ge.received_by = u.id
            LEFT JOIN product_orders po ON po.id = ge.order_id
            LEFT JOIN suppliers s ON s.id = po.supplier_id
            WHERE ge.id = ?
        ");
        $stmt->execute([$entryId]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$entry) {
            die('Entrada de mercancía no encontrada.');
        }

        // 2) Cargar detalle de ítems
        $stmtItems = $pdo->prepare("
            SELECT gei.product_id, p.sku, p.name, p.cost,
                   gei.quantity_received AS qty_received,
                   poi.quantity AS qty_ordered,
                   (gei.quantity_received * p.cost) AS line_system_sub
            FROM goods_entry_items gei
            JOIN products p ON gei.product_id = p.id
            JOIN goods_entries ge ON gei.goods_entry_id = ge.id
            JOIN product_order_items poi
                 ON poi.order_id = ge.order_id AND poi.product_id = gei.product_id
            WHERE gei.goods_entry_id = ?
        ");
        $stmtItems->execute([$entryId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // 3) Sección CANTIDADES: exceso (recibido > ordenado)
        $excessItems = [];
        $totalQtyDebit = 0;
        foreach ($items as $it) {
            if ($it['qty_received'] > $it['qty_ordered']) {
                $excess = $it['qty_received'] - $it['qty_ordered'];
                $exVal = $excess * $it['cost'];
                $exTax = $exVal * 0.15;
                $it['excess'] = $excess;
                $it['excess_value'] = $exVal;
                $it['excess_tax'] = $exTax;
                $it['excess_total'] = $exVal + $exTax;
                $excessItems[] = $it;
                $totalQtyDebit += $it['excess_total'];
            }
        }
        $hasQtySection = count($excessItems) > 0;

        // 4) Sección COSTOS: factura > sistema
        $invoiceTotal = floatval($entry['invoice_subtotal']) + floatval($entry['invoice_tax']);
        $systemTotal = floatval($entry['system_total']);
        $costDiff = $invoiceTotal - $systemTotal;
        $hasCostSection = ($costDiff > 0.01);

        $grandTotal = 0;
        if ($hasQtySection) $grandTotal += $totalQtyDebit;
        if ($hasCostSection) $grandTotal += $costDiff;

        $company = defined('COMPANY_NAME') ? COMPANY_NAME : 'SoleiPharma';
        $branch = defined('BRANCH') ? BRANCH : '';
        $dateTime = date("d/m/Y H:i:s", strtotime($entry['created_at'] ?? 'now'));
        $userName = trim(($entry['first_name'] ?? '') . ' ' . ($entry['last_name'] ?? ''));

        ob_start();
        require __DIR__ . '/../views/admin/debit_note_report.php';
        $html = ob_get_clean();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="nota_debito_' . $entryId . '.pdf"');
        echo $dompdf->output();
        exit;
    }

    // Generar PDF de boleta para Pedido Aplicado
    public function appliedOrderReport($orderId)
    {
        global $pdo;

        // 1) Leer el pedido CON el nombre del usuario que lo aplicó
        $stmt = $pdo->prepare("
            SELECT po.*,
                   CONCAT(u.first_name, ' ', u.last_name) AS admin_name,
                   u.username AS admin_username
            FROM product_orders po
            LEFT JOIN users u ON u.id = po.applied_by
            WHERE po.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            die('Pedido no encontrado.');
        }

        if (!in_array($order['status'], ['applied', 'received'])) {
            die('El pedido debe estar aplicado o recibido para generar esta boleta.');
        }

        // 2) Obtener detalle trayendo el cost desde products.cost y name, sku
        $stmtItems = $pdo->prepare("
            SELECT poi.product_id, poi.quantity, p.name, p.sku, p.cost
            FROM product_order_items poi
            JOIN products p ON poi.product_id = p.id
            WHERE poi.order_id = ?
        ");
        $stmtItems->execute([$orderId]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // 3) Formatear fechas de UTC a America/Managua (UTC-6)
        $dtCreated = new DateTime($order['order_date'], new DateTimeZone('UTC'));
        $dtCreated->setTimezone(new DateTimeZone('America/Managua'));
        $orderDate = $dtCreated->format('d/m/Y H:i:s');

        $dtApplied = new DateTime($order['applied_at'], new DateTimeZone('UTC'));
        $dtApplied->setTimezone(new DateTimeZone('America/Managua'));
        $appliedDate = $dtApplied->format('d/m/Y H:i:s');

        // 4) Calcular totales
        $systemSubtotal = 0;
        foreach ($orderItems as $it) {
            $systemSubtotal += ($it['cost'] * $it['quantity']);
        }
        
        $systemTax = round($systemSubtotal * 0.15, 2);
        $systemTotal = round($systemSubtotal + $systemTax, 2);

        // 5) Capturar la vista HTML
        ob_start();
        require __DIR__ . '/../views/admin/applied_order_report.php';
        $html = ob_get_clean();

        // 6) Generar el PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="boleta_pedido_' . $order['id'] . '.pdf"');
        echo $dompdf->output();
        exit;
    }

}

