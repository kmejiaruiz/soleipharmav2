<?php
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
        parent::__construct(); // Verifica que el usuario tenga rol admin o superadmin.
        global $pdo;
        $this->productModel = new Product($pdo);
        $this->orderModel = new ProductOrder($pdo);
    }

    // Muestra el formulario para realizar un pedido
    public function create()
    {
        $products = $this->productModel->getAll();
        $this->renderAdmin('admin/orders_create', ['products' => $products]);
    }

    // Procesa el pedido. Se envía vía AJAX para mostrar modal de éxito o error.
    public function store()
    {
        global $pdo;
        $quantities = $_POST['quantities'] ?? [];
        $orderItems = [];
        foreach ($quantities as $product_id => $qty) {
            if (intval($qty) > 0) {
                $orderItems[$product_id] = intval($qty);
            }
        }
        if (empty($orderItems)) {
            echo json_encode(['success' => false, 'message' => "No se seleccionó ningún producto para el pedido."]);
            exit;
        }
        $admin_id = $_SESSION['user']['id'];
        $admin_name = $_SESSION['user']['username'];
        $order_id = $this->orderModel->createOrder($admin_id, $admin_name);
        if (!$order_id) {
            echo json_encode(['success' => false, 'message' => "Error al crear el pedido."]);
            exit;
        }
        foreach ($orderItems as $product_id => $qty) {
            $this->orderModel->addOrderItem($order_id, $product_id, $qty);
        }
        echo json_encode(['success' => true, 'message' => "Pedido realizado correctamente."]);
        exit;
    }

    // Lista todos los pedidos
    public function index()
    {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM product_orders ORDER BY order_date DESC");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->renderAdmin('admin/orders_list', ['orders' => $orders]);
    }

    // Cambia el estado de un pedido de 'pending' a 'applied'
    public function updateStatus($id)
    {
        global $pdo;
        // Obtener pedido
        $stmt = $pdo->prepare("SELECT * FROM product_orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => "Pedido no encontrado."]);
            exit;
        }
        if ($order['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => "El pedido ya fue aplicado o cancelado."]);
            exit;
        }
        // Aplicar y registrar quién y cuándo
        $userId = $_SESSION['user']['id'];
        $stmtUpdate = $pdo->prepare("
        UPDATE product_orders 
        SET status = 'applied', applied_by = ?, applied_at = NOW() 
        WHERE id = ?
    ");
        if ($stmtUpdate->execute([$userId, $id])) {
            echo json_encode(['success' => true, 'message' => "El pedido fue aplicado exitosamente."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Error al actualizar el estado del pedido."]);
        }
        exit;
    }



    // (Opcional) Método para editar un pedido
    public function edit($orderId)
    {
        global $pdo;

        // Obtener los datos del pedido usando el ID
        $stmt = $pdo->prepare("SELECT * FROM product_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $_SESSION['flash'] = "Pedido no encontrado.";
            $_SESSION['flash_type'] = "alert";
            header("Location: index.php?controller=order&action=index");
            exit;
        }

        // Obtener los ítems del pedido usando el ID
        $stmtItems = $pdo->prepare("SELECT oi.*, p.name, p.sku FROM product_order_items oi 
                                    JOIN products p ON oi.product_id = p.id 
                                    WHERE oi.order_id = ?");
        $stmtItems->execute([$orderId]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/orders_edit', [
            'order' => $order,
            'orderItems' => $orderItems
        ]);
    }



    // (Opcional) Actualiza los datos del pedido editado.
    public function update($id)
    {
        global $pdo;

        // Verificar que el pedido existe
        $stmt = $pdo->prepare("SELECT * FROM product_orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => "Pedido no encontrado."]);
            exit;
        }

        // Solo se permite editar si el pedido está pendiente
        if ($order['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => "Solo se pueden editar pedidos pendientes."]);
            exit;
        }

        // Recoger los datos enviados: se espera un arreglo 'quantities' donde la clave es el product_id y el valor es la cantidad actualizada.
        $updatedQuantities = $_POST['quantities'] ?? [];

        // Procesar cada ítem del pedido: actualizar la cantidad o eliminar el ítem si la cantidad es 0.
        foreach ($updatedQuantities as $product_id => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                // Actualizar la cantidad en el ítem del pedido
                $stmtUpdateItem = $pdo->prepare("UPDATE product_order_items SET quantity = ? WHERE order_id = ? AND product_id = ?");
                $stmtUpdateItem->execute([$qty, $id, $product_id]);
            } else {
                // Si la cantidad es 0, eliminar ese ítem del pedido
                $stmtDeleteItem = $pdo->prepare("DELETE FROM product_order_items WHERE order_id = ? AND product_id = ?");
                $stmtDeleteItem->execute([$id, $product_id]);
            }
        }

        echo json_encode(['success' => true, 'message' => "Pedido actualizado correctamente."]);
        exit;
    }

    public function goodsEntry($orderId)
    {
        global $pdo;
        // Obtener el pedido
        $stmt = $pdo->prepare("SELECT * FROM product_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            $_SESSION['flash'] = "Pedido no encontrado.";
            $_SESSION['flash_type'] = "alert";
            header("Location: index.php?controller=order&action=index");
            exit;
        }
        // Solo se permite dar entrada a pedidos con estado 'applied'
        if ($order['status'] != 'applied') {
            $_SESSION['flash'] = "Solo se puede dar entrada a pedidos aplicados.";
            $_SESSION['flash_type'] = "alert";
            header("Location: index.php?controller=order&action=index");
            exit;
        }
        // Obtener los ítems del pedido junto con información del producto
        $stmtItems = $pdo->prepare("SELECT oi.*, p.name, p.sku FROM product_order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmtItems->execute([$orderId]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/goods_entry', [
            'order' => $order,
            'orderItems' => $orderItems
        ]);
    }

    // Procesa la entrada de mercadería para el pedido
    public function storeGoodsEntry($orderId)
    {
        global $pdo;
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        // 1) Validar pedido y estado
        $stmt = $pdo->prepare("SELECT * FROM product_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => "Pedido no encontrado."]);
            exit;
        }
        if ($order['status'] !== 'applied') {
            echo json_encode(['success' => false, 'message' => "Solo pedidos aplicados pueden recibir entrada."]);
            exit;
        }

        // 2) Recoger datos
        $receivedQuantities = $_POST['received_quantities'] ?? [];
        $justifications = $_POST['justifications'] ?? [];

        // 3) Obtener ítems ordenados
        $stmtItems = $pdo->prepare("
      SELECT product_id, quantity AS ordered_qty
      FROM product_order_items
      WHERE order_id = ?
    ");
        $stmtItems->execute([$orderId]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // 4) Validar cantidades
        $errors = [];
        foreach ($orderItems as $it) {
            $pid = $it['product_id'];
            $oQty = (int) $it['ordered_qty'];
            $rQty = isset($receivedQuantities[$pid]) ? (int) $receivedQuantities[$pid] : null;

            if ($rQty === null) {
                $errors[] = "Producto $pid: falta cantidad recibida.";
            } elseif ($rQty > $oQty) {
                $errors[] = "Producto $pid: recibido $rQty > ordenado $oQty.";
            } elseif ($rQty < $oQty) {
                $just = trim($justifications[$pid] ?? '');
                if ($just === '') {
                    $errors[] = "Producto $pid: ordenado $oQty, recibido $rQty. Falta justificación.";
                }
            }
        }
        if ($errors) {
            echo json_encode(['success' => false, 'message' => implode(" ", $errors)]);
            exit;
        }

        // 5) Intentar registrar la entrada y actualizar stock
        try {
            $pdo->beginTransaction();

            // 5.1 Insertar cabecera
            $uid = $_SESSION['user']['id'];
            $stmtE = $pdo->prepare("INSERT INTO goods_entries (order_id, received_by) VALUES (?, ?)");
            $stmtE->execute([$orderId, $uid]);
            $entryId = $pdo->lastInsertId();

            // 5.2 Insertar detalle y sumar stock
            $stmtD = $pdo->prepare("
          INSERT INTO goods_entry_items
            (goods_entry_id, product_id, quantity_received, justification)
          VALUES (?, ?, ?, ?)
        ");
            $stmtS = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");

            foreach ($orderItems as $it) {
                $pid = $it['product_id'];
                $rQty = (int) $receivedQuantities[$pid];
                $just = $justifications[$pid] ?? null;

                $stmtD->execute([$entryId, $pid, $rQty, $just]);
                $stmtS->execute([$rQty, $pid]);
            }

            // 5.3 Marcar orden como recibida
            $pdo->prepare("UPDATE product_orders SET status = 'received' WHERE id = ?")
                ->execute([$orderId]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Entrada y stock actualizados correctamente."]);
        } catch (\PDOException $e) {
            $pdo->rollBack();
            error_log("storeGoodsEntry fallo: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => "Error interno al registrar entrada: " . $e->getMessage()
            ]);
        }
        exit;
    }



    public function goodsEntryReport($orderId)
    {
        global $pdo;
        // (1) Cargar pedido y applied_by / applied_at
        $stmt = $pdo->prepare("
      SELECT po.*, u.username AS applied_by_name 
      FROM product_orders po
      LEFT JOIN users u ON po.applied_by = u.id
      WHERE po.id = ?
    ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order)
            die("Pedido no encontrado.");

        // (2) Cargar detalles: ordered vs received
        $stmtItems = $pdo->prepare("
    SELECT
      poi.product_id,
      p.sku,
      p.name,
      poi.quantity      AS ordered_qty,
      gei.quantity_received AS received_qty,
      gei.justification
    FROM goods_entries ge
    JOIN goods_entry_items gei ON ge.id = gei.goods_entry_id
    JOIN product_order_items poi 
      ON poi.order_id   = ge.order_id 
     AND poi.product_id = gei.product_id
    JOIN products p ON p.id = poi.product_id
    WHERE ge.order_id = ?
");
        $stmtItems->execute([$orderId]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // (3) Fecha / usuario
        $receptionDate = $order['applied_at']
            ? date("d/m/Y H:i:s", strtotime($order['applied_at']))
            : date("d/m/Y H:i:s");
        $appliedUser = $order['applied_by_name'] ?? '—';

        // (4) Capturar la vista
        ob_start();
        $companyName = COMPANY_NAME;
        require __DIR__ . '/../views/admin/goods_entry_report.php';
        $html = ob_get_clean();

        // (5) Generar PDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $pdf = $dompdf->output();

        // (6) Enviar inline
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="entrada_' . $orderId . '.pdf"');
        echo $pdf;
        exit;
    }

    // Generar PDF de la boleta de recepción
    // public function generateEntryPDF($orderId)
    // {
    //     global $pdo;
    //     // 1) Cargar la orden junto a applied_by y applied_at
    //     $stmt = $pdo->prepare("
    //         SELECT po.*, u.username AS applied_by_name 
    //         FROM product_orders po
    //         LEFT JOIN users u ON po.applied_by = u.id
    //         WHERE po.id = ?
    //     ");
    //     $stmt->execute([$orderId]);
    //     $order = $stmt->fetch(PDO::FETCH_ASSOC);
    //     if (!$order) {
    //         die("Pedido no encontrado.");
    //     }

    //     // 2) Obtener los ítems de la orden (detalle)
    //     $stmtItems = $pdo->prepare("
    //         SELECT poi.*, p.name, p.sku 
    //         FROM product_order_items poi
    //         JOIN products p ON poi.product_id = p.id
    //         WHERE poi.order_id = ?
    //     ");
    //     $stmtItems->execute([$orderId]);
    //     $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    //     // 3) Preparar variables para la vista
    //     $receptionDate = $order['applied_at']
    //         ? date("d/m/Y H:i:s", strtotime($order['applied_at']))
    //         : date("d/m/Y H:i:s");
    //     $appliedUser = $order['applied_by_name'] ?? '—';

    //     // 4) Capturar la vista HTML
    //     ob_start();
    //     // La vista espera: $order, $orderItems, $receptionDate, $appliedUser
    //     require __DIR__ . '/../views/admin/goods_entry_report.php';
    //     $html = ob_get_clean();

    //     // 5) Generar PDF con Dompdf
    //     $dompdf = new Dompdf();
    //     $dompdf->loadHtml($html);
    //     $dompdf->setPaper('A4', 'portrait');
    //     $dompdf->render();
    //     $pdfOutput = $dompdf->output();

    //     // 6) Enviar al navegador inline
    //     header('Content-Type: application/pdf');
    //     header('Content-Disposition: inline; filename="entrada_' . $orderId . '.pdf"');
    //     echo $pdfOutput;
    //     exit;
    // }

}
?>