<?php
// controllers/OrderController.php

require_once 'AdminController.php';
require_once 'models/ProductOrder.php';
require_once 'models/Product.php';
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
        $products = $this->productModel->getAll();
        $this->renderAdmin('admin/orders_create', ['products' => $products]);
    }

    // Guardar nuevo pedido
    public function store()
    {
        global $pdo;
        $quantities = $_POST['quantities'] ?? [];
        $orderItems = [];
        foreach ($quantities as $pid => $qty) {
            if (intval($qty) > 0) {
                $orderItems[$pid] = intval($qty);
            }
        }
        if (empty($orderItems)) {
            echo json_encode(['success' => false, 'message' => 'No se seleccionó ningún producto.']);
            exit;
        }
        $adminId = $_SESSION['user']['id'];
        $orderId = $this->orderModel->createOrder($adminId, $_SESSION['user']['username']);
        foreach ($orderItems as $pid => $qty) {
            $this->orderModel->addOrderItem($orderId, $pid, $qty);
        }
        echo json_encode(['success' => true, 'message' => 'Pedido creado correctamente.']);
        exit;
    }

    // Listar pedidos
    public function index()
    {
        global $pdo;
        $orders = $pdo
            ->query("SELECT * FROM product_orders ORDER BY order_date DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
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

    // Formulario de entrada de mercancía
    public function goodsEntry($orderId)
    {
        global $pdo;
        // 1) Obtener el pedido
        $stmt = $pdo->prepare("SELECT * FROM product_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            $_SESSION['flash'] = "Pedido no encontrado.";
            header("Location: index.php?controller=order&action=index");
            exit;
        }
        // 2) Verificar estado
        if ($order['status'] !== 'applied') {
            $_SESSION['flash'] = "Solo se puede dar entrada a pedidos aplicados.";
            header("Location: index.php?controller=order&action=index");
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

        // 5) Renderizar la vista, incluyendo las 3 nuevas variables
        $this->renderAdmin('admin/goods_entry', [
            'order' => $order,
            'orderItems' => $orderItems,
            'systemSubtotal' => $systemSubtotal,
            'systemTax' => $systemTax,
            'systemTotal' => $systemTotal,
        ]);
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
        if ($errors) {
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit;
        }
        $sysTotal = $sysSub + $sysTax;

        // 6) Transacción: insertar cabecera, detalle, actualizar stock y marcar orden
        try {
            $pdo->beginTransaction();

            // 6.1) Cabecera de entrada con montos de factura y de sistema
            $stmtE = $pdo->prepare("
            INSERT INTO goods_entries
              (order_id, received_by, invoice_subtotal, invoice_tax,
               system_subtotal, system_tax, system_total)
            VALUES (?,?,?,?,?,?,?)
        ");
            $stmtE->execute([
                $orderId,
                $receiverId,
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
            $stmtS = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            foreach ($items as $it) {
                $pid = $it['product_id'];
                $rQty = max(0, intval($received[$pid] ?? 0));
                $just = $justifs[$pid] ?? null;

                $stmtD->execute([$entryId, $pid, $rQty, $just]);
                $stmtS->execute([$rQty, $pid]);
            }

            // 6.3) Marcar pedido como recibido
            $pdo->prepare("UPDATE product_orders SET status = 'received' WHERE id = ?")
                ->execute([$orderId]);

            $pdo->commit();

            // 6.4) Si factura física > sistema, generar nota de débito en PDF
            if ($invoiceTotal > $sysTotal) {
                $diff = $invoiceTotal - $sysTotal;
                $this->generateDebitNotePDF($entryId, $diff);
            }

            echo json_encode(['success' => true, 'message' => 'Entrada registrada correctamente.']);
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
        ge.received_at      AS applied_at,    -- tu campo real
        u.first_name,
        u.last_name
      FROM goods_entries ge
      LEFT JOIN users u ON ge.received_by = u.id
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

        // 4) Nombre completo de quien recibió
        $appliedUser = trim($entry['first_name'] . ' ' . $entry['last_name']);

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



    // Generar nota de débito
    /**
     * Genera y emite por el navegador el PDF de la Nota de Débito
     *
     * @param int   $entryId  ID de la entrada (goods_entries.id)
     * @param float $amount   Diferencia a debitar (invoice_total - system_total)
     */
    private function generateDebitNotePDF($entryId, $amount)
    {
        global $pdo;
        // 1) Cargar datos de la entrada y usuario
        $stmt = $pdo->prepare("
        SELECT ge.*, u.first_name, u.last_name, u.branch
        FROM goods_entries ge
        JOIN users u ON ge.received_by = u.id
        WHERE ge.id = ?
    ");
        $stmt->execute([$entryId]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$entry) {
            throw new \Exception("Entrada de mercancía #{$entryId} no encontrada para nota de débito.");
        }

        // 2) Cargar detalle de ítems
        $stmtItems = $pdo->prepare("
        SELECT gei.product_id, p.sku, p.name,
               gei.quantity_received AS qty,
               (gei.quantity_received * p.cost) AS line_system_sub,
               ROUND((gei.quantity_received * p.cost) * 0.15,2) AS line_system_tax
        FROM goods_entry_items gei
        JOIN products p ON gei.product_id = p.id
        WHERE gei.goods_entry_id = ?
    ");
        $stmtItems->execute([$entryId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // 3) Preparar datos generales
        $company = COMPANY_NAME;
        $branch = defined('BRANCH') ? BRANCH : '';
        $dateTime = date("d/m/Y H:i:s", strtotime($entry['created_at']));
        $userName = "{$entry['first_name']} {$entry['last_name']}";
        $reason = "Diferencia entre factura y sistema: \$" . number_format($amount, 2);
        $debitTotal = $amount;

        // 4) Construir HTML de la Nota de Débito
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <title>Nota de Débito #<?= $entryId ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    margin: 20px;
                }

                header,
                footer {
                    text-align: center;
                }

                .flex {
                    display: flex;
                    justify-content: space-between;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }

                th,
                td {
                    border: 1px solid #000;
                    padding: 5px;
                    text-align: left;
                }

                th {
                    background: #eee;
                }
            </style>
        </head>

        <body>
            <header>
                <h2>Nota de Débito</h2>
                <p><strong><?= htmlspecialchars($company) ?></strong> – <?= htmlspecialchars($branch) ?></p>
            </header>
            <section class="flex">
                <div>
                    <p><strong>Fecha:</strong> <?= $dateTime ?></p>
                    <p><strong>Usuario:</strong> <?= htmlspecialchars($userName) ?></p>
                </div>
                <div>
                    <p><strong>No. Entrada:</strong> <?= $entryId ?></p>
                </div>
            </section>

            <h4>Motivo</h4>
            <p><?= htmlspecialchars($reason) ?></p>

            <h4>Detalle de Ítems</h4>
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Subtotal S.</th>
                        <th>IVA S.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars($it['sku']) ?></td>
                            <td><?= htmlspecialchars($it['name']) ?></td>
                            <td><?= $it['qty'] ?></td>
                            <td>$<?= number_format($it['line_system_sub'], 2) ?></td>
                            <td>$<?= number_format($it['line_system_tax'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <footer>
                <p><strong>Total Notas de Débito:</strong> $<?= number_format($debitTotal, 2) ?></p>
            </footer>
        </body>

        </html>
        <?php
        $html = ob_get_clean();

        // 5) Generar y enviar PDF con Dompdf
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="nota_debito_' . $entryId . '.pdf"');
        echo $dompdf->output();
        exit;
    }

}
