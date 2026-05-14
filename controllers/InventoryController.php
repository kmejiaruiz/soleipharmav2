<?php
// controllers/InventoryController.php
require_once 'AdminController.php';
require_once 'config/config.php';

class InventoryController extends AdminController
{
  public function __construct()
  {
    parent::__construct();
  }

  public function movements()
  {
    global $pdo;

    $productFilter = trim($_GET['product'] ?? '');
    $typeFilter    = trim($_GET['type']    ?? '');
    $fromFilter    = trim($_GET['from']    ?? '');
    $toFilter      = trim($_GET['to']      ?? '');

    // Sucursal actual: constante BRANCH de config o rama del usuario en sesión
    $branch = defined('BRANCH') && BRANCH !== '' ? BRANCH : ($_SESSION['user']['branch'] ?? '');

    // ─── UNION de 4 fuentes — todas filtradas por sucursal ────────────────
    $sql = "
        -- 1. Entradas de mercadería
        SELECT
            ge.received_at                          AS fecha,
            p.sku,
            p.name                                  AS producto,
            'Entrada de Mercadería'                 AS tipo,
            'entrada_mercaderia'                    AS categoria,
            'entrada'                               AS direccion,
            gei.quantity_received                   AS cantidad,
            NULL                                    AS previous_stock,
            NULL                                    AS new_stock,
            CONCAT(
                'Pedido #', LPAD(ge.order_id,4,'0'),
                ' | Entrada #', LPAD(ge.id,4,'0'),
                IFNULL(CONCAT(' | Proveedor: ', s.name),''),
                ' | Sub Fac: C\$', FORMAT(ge.invoice_subtotal,2)
            )                                       AS referencia,
            CONCAT(IFNULL(u.first_name,''),' ',IFNULL(u.last_name,'')) AS usuario
        FROM goods_entry_items gei
        JOIN goods_entries ge  ON ge.id  = gei.goods_entry_id
        JOIN products p        ON p.id   = gei.product_id
        JOIN users u           ON u.id   = ge.received_by
        JOIN product_orders po ON po.id  = ge.order_id
        LEFT JOIN suppliers s  ON s.id   = po.supplier_id
        WHERE ge.branch = ?

        UNION ALL

        -- 2. Descartes aprobados
        SELECT
            dr.decision_at,
            p.sku,
            p.name,
            'Descarte'                              AS tipo,
            'descarte'                              AS categoria,
            'salida'                                AS direccion,
            -dr.quantity,
            NULL, NULL,
            CONCAT(
                'Descarte #', dr.id,
                ' | Motivo: ', LEFT(IFNULL(dr.reason,'—'),60),
                ' | Aprobado por: ', IFNULL(ua.username,'—')
            ),
            ur.username
        FROM discard_requests dr
        JOIN products p    ON p.id  = dr.product_id
        JOIN users ur      ON ur.id = dr.requested_by
        LEFT JOIN users ua ON ua.id = dr.decision_by
        WHERE dr.status = 'approved'
          AND ur.branch = ?

        UNION ALL

        -- 3. Ajustes de inventario / Ventas POS (inventory_log)
        SELECT
            il.created_at,
            p.sku,
            p.name,
            CASE il.change_type
              WHEN 'stock_increase' THEN 'Ajuste de Inventario (Aumento)'
              WHEN 'stock_decrease' THEN 'Ajuste de Inventario (Disminución)'
              WHEN 'venta'          THEN 'Venta POS (Cajero)'
              WHEN 'edit'           THEN 'Edición de Oficial de Inventario'
              ELSE il.change_type
            END,
            CASE il.change_type
              WHEN 'stock_increase' THEN 'oficial'
              WHEN 'stock_decrease' THEN 'oficial'
              WHEN 'venta'          THEN 'venta'
              ELSE 'manual'
            END,
            CASE il.change_type
              WHEN 'stock_increase' THEN 'entrada'
              ELSE 'salida'
            END,
            (il.new_stock - il.previous_stock),
            il.previous_stock,
            il.new_stock,
            CASE il.change_type
              WHEN 'venta' THEN IFNULL(il.description, '')
              ELSE CONCAT('Oficial: ', il.admin_name, IFNULL(CONCAT(' | Nota: ', il.description),''))
            END,
            il.admin_name
        FROM inventory_log il
        JOIN products p ON p.id = il.product_id
        WHERE il.change_type != 'supplier_entry'
          AND il.branch = ?

        ORDER BY fecha DESC
        ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$branch, $branch, $branch]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filtros adicionales en PHP
    if ($productFilter) {
      $q    = strtolower($productFilter);
      $rows = array_filter($rows, fn($r) =>
        str_contains(strtolower($r['producto']), $q) ||
        str_contains(strtolower($r['sku'] ?? ''), $q)
      );
    }
    if ($typeFilter) {
      $rows = array_filter($rows, fn($r) => $r['categoria'] === $typeFilter);
    }
    if ($fromFilter) {
      $rows = array_filter($rows, fn($r) => !empty($r['fecha']) && substr($r['fecha'], 0, 10) >= $fromFilter);
    }
    if ($toFilter) {
      $rows = array_filter($rows, fn($r) => !empty($r['fecha']) && substr($r['fecha'], 0, 10) <= $toFilter);
    }

    $this->renderAdmin('admin/inventory_movements', [
      'movements'     => array_values($rows),
      'productFilter' => $productFilter,
      'typeFilter'    => $typeFilter,
      'fromFilter'    => $fromFilter,
      'toFilter'      => $toFilter,
    ]);
  }

  // ─── Reporte de Bodega por Proveedor (Conteo Cíclico) ─────────────────────
  public function report()
  {
    global $pdo;

    $suppliers = $pdo->query(
      "SELECT id, name FROM suppliers WHERE active = 1 ORDER BY name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $supplierId   = intval($_GET['supplier_id'] ?? 0);
    $bodega       = trim($_GET['bodega'] ?? '');
    $reportRows   = [];
    $supplierName = '';
    $generated    = false;

    if ($supplierId && $bodega) {
      $s = $pdo->prepare("SELECT name FROM suppliers WHERE id = ?");
      $s->execute([$supplierId]);
      $supplierName = $s->fetchColumn() ?: '—';

      // Todos los productos del catálogo de ese proveedor con su stock actual
      $currentBranch = defined('BRANCH') && BRANCH !== '' ? BRANCH : '';
      $stmt = $pdo->prepare(
        "SELECT p.sku, p.name AS producto,
                COALESCE(bps.stock, 0) AS existencia_sistema
         FROM supplier_products sp
         JOIN products p ON p.id = sp.product_id
         LEFT JOIN branch_product_stock bps
                ON bps.product_id = p.id AND bps.branch = ?
         WHERE sp.supplier_id = ?
         ORDER BY p.name ASC"
      );
      $stmt->execute([$currentBranch, $supplierId]);
      $reportRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Verificar si hay stock real en esta sucursal
      $totalStock = array_sum(array_column($reportRows, 'existencia_sistema'));
      if ($totalStock > 0 && !empty($reportRows)) {
          $generated = true;
      } else {
          $generated    = false;
          $reportError  = empty($reportRows)
              ? 'Este proveedor no tiene productos registrados en el catálogo.'
              : 'Esta sucursal (<strong>' . htmlspecialchars($currentBranch ?: 'sin nombre') . '</strong>) no tiene existencias de ningún producto de este proveedor. No se puede generar el conteo cíclico.';
      }
    }

    $bodegaLabels = [
      'merma'  => 'Merma / Descarte',
      'debito' => 'Bodega de Débito — Devoluciones al Proveedor',
      'leon'   => defined('BRANCH') && BRANCH !== '' ? BRANCH : 'Sucursal León',
    ];

    $this->renderAdmin('admin/inventory_report', [
      'suppliers'    => $suppliers,
      'supplierId'   => $supplierId,
      'supplierName' => $supplierName,
      'bodega'       => $bodega,
      'bodegaLabels' => $bodegaLabels,
      'reportRows'   => $reportRows,
      'generated'    => $generated,
      'reportError'  => $reportError ?? null,
    ]);
  }

  // ─── Exportar movimientos a CSV ────────────────────────────────────────────
  public function exportCsv()
  {
    global $pdo;
    // Re-run the same UNION query (no filters — export everything)
    $sql = "
        SELECT ge.received_at AS fecha, p.sku, p.name AS producto,
            'Entrada de Mercadería' AS tipo, 'entrada_mercaderia' AS categoria, 'entrada' AS direccion,
            gei.quantity_received AS cantidad, NULL AS previous_stock, NULL AS new_stock,
            CONCAT('Pedido #',LPAD(ge.order_id,4,'0'),' | Entrada #',LPAD(ge.id,4,'0'),
                IFNULL(CONCAT(' | Proveedor: ',s.name),''),
                ' | Sub Fac: C$',FORMAT(ge.invoice_subtotal,2)) AS referencia,
            CONCAT(IFNULL(u.first_name,''),' ',IFNULL(u.last_name,'')) AS usuario
        FROM goods_entry_items gei
        JOIN goods_entries ge ON ge.id=gei.goods_entry_id
        JOIN products p ON p.id=gei.product_id
        JOIN users u ON u.id=ge.received_by
        JOIN product_orders po ON po.id=ge.order_id
        LEFT JOIN suppliers s ON s.id=po.supplier_id
        UNION ALL
        SELECT o.created_at, p.sku, p.name, 'Salida por Venta','venta','salida',
            -oi.quantity, NULL, NULL,
            CONCAT('Venta #',LPAD(o.id,4,'0'),' | Total: C$',FORMAT(o.total,2)),
            u.username
        FROM order_items oi
        JOIN orders o ON o.id=oi.order_id
        JOIN products p ON p.id=oi.product_id
        LEFT JOIN users u ON u.id=o.user_id
        WHERE o.status='completado'
        UNION ALL
        SELECT dr.decision_at, p.sku, p.name, 'Descarte','descarte','salida',
            -dr.quantity, NULL, NULL,
            CONCAT('Descarte #',dr.id,' | Motivo: ',LEFT(IFNULL(dr.reason,'—'),60),
                ' | Aprobado por: ',IFNULL(ua.username,'—')),
            ur.username
        FROM discard_requests dr
        JOIN products p ON p.id=dr.product_id
        LEFT JOIN users ur ON ur.id=dr.requested_by
        LEFT JOIN users ua ON ua.id=dr.decision_by
        WHERE dr.status='approved'
        UNION ALL
        SELECT il.created_at, p.sku, p.name,
            CASE il.change_type
              WHEN 'stock_increase' THEN 'Ajuste de Inventario (Aumento)'
              WHEN 'stock_decrease' THEN 'Ajuste de Inventario (Disminución)'
              WHEN 'edit' THEN 'Edición de Oficial de Inventario'
              ELSE il.change_type END,
            CASE il.change_type WHEN 'stock_increase' THEN 'oficial' ELSE 'manual' END,
            CASE il.change_type WHEN 'stock_increase' THEN 'entrada' ELSE 'salida' END,
            (il.new_stock - il.previous_stock), il.previous_stock, il.new_stock,
            CONCAT('Oficial: ',il.admin_name,IFNULL(CONCAT(' | Nota: ',il.description),'')),
            il.admin_name
        FROM inventory_log il
        JOIN products p ON p.id=il.product_id
        WHERE il.change_type != 'supplier_entry'
        ORDER BY fecha DESC
    ";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="movimientos_inventario_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    // BOM para que Excel lo abra correctamente
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['Fecha', 'SKU', 'Producto', 'Tipo', 'Dirección', 'Cantidad', 'Stock Anterior', 'Stock Nuevo', 'Referencia', 'Usuario']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['fecha'],
            $r['sku'],
            $r['producto'],
            $r['tipo'],
            $r['direccion'],
            $r['cantidad'],
            $r['previous_stock'] ?? '',
            $r['new_stock'] ?? '',
            $r['referencia'],
            $r['usuario'],
        ]);
    }
    fclose($out);
    exit;
  }
}
