<?php
// controllers/SupplierController.php

require_once 'AdminController.php';
require_once 'config/config.php';

class SupplierController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    /** Verifica que el usuario actual sea superadmin; si no, aborta. */
    private function requireSuperAdmin(bool $isAjax = false): void
    {
        if (($_SESSION['user']['role'] ?? '') !== 'superadmin') {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo superadmin puede realizar esta acción.']);
                exit;
            }
            $_SESSION['flash'] = 'Acceso denegado. Solo el superadministrador puede editar proveedores.';
            header('Location: ' . APP_BASE . '/supplier/index');
            exit;
        }
    }

    // ─── Listado ──────────────────────────────────────────────────────────────
    public function index()
    {
        global $pdo;
        $suppliers = $pdo->query(
            "SELECT s.*,
                    COUNT(DISTINCT sp.id)  AS product_count,
                    COUNT(DISTINCT po.id)  AS order_count
             FROM suppliers s
             LEFT JOIN supplier_products sp ON sp.supplier_id = s.id
             LEFT JOIN product_orders po    ON po.supplier_id = s.id
             GROUP BY s.id
             ORDER BY s.name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $isSuperAdmin = ($_SESSION['user']['role'] === 'superadmin');
        $this->renderAdmin('admin/suppliers_list', [
            'suppliers'    => $suppliers,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    // ─── Formulario Crear (solo superadmin) ──────────────────────────────────
    public function create()
    {
        $this->requireSuperAdmin();
        $this->renderAdmin('admin/supplier_form', ['supplier' => null]);
    }

    // ─── Guardar Nuevo (solo superadmin) ─────────────────────────────────────
    public function store()
    {
        $this->requireSuperAdmin(true);
        global $pdo;
        header('Content-Type: application/json');

        $name    = trim($_POST['name'] ?? '');
        $ruc     = trim($_POST['ruc'] ?? '');
        $contact = trim($_POST['contact_name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (!$name) {
            echo json_encode(['success' => false, 'message' => 'El nombre del proveedor es requerido.']);
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO suppliers (name, ruc, contact_name, phone, email, address)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $ruc, $contact, $phone, $email, $address]);
        $newId = $pdo->lastInsertId();

        echo json_encode(['success' => true, 'message' => 'Proveedor creado correctamente.', 'id' => $newId]);
        exit;
    }

    // ─── Formulario Editar (solo superadmin) ─────────────────────────────────
    public function edit()
    {
        $this->requireSuperAdmin();
        global $pdo;
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: ' . APP_BASE . '/supplier/index'); exit; }

        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$supplier) { header('Location: ' . APP_BASE . '/supplier/index'); exit; }

        $this->renderAdmin('admin/supplier_form', ['supplier' => $supplier]);
    }

    // ─── Actualizar (solo superadmin) ─────────────────────────────────────────
    public function update()
    {
        $this->requireSuperAdmin(true);
        global $pdo;
        header('Content-Type: application/json');

        $id      = $_POST['id'] ?? null;
        $name    = trim($_POST['name'] ?? '');
        $ruc     = trim($_POST['ruc'] ?? '');
        $contact = trim($_POST['contact_name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (!$id || !$name) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            exit;
        }

        $stmt = $pdo->prepare(
            "UPDATE suppliers SET name=?, ruc=?, contact_name=?, phone=?, email=?, address=? WHERE id=?"
        );
        $stmt->execute([$name, $ruc, $contact, $phone, $email, $address, $id]);

        echo json_encode(['success' => true, 'message' => 'Proveedor actualizado correctamente.']);
        exit;
    }

    // ─── Activar / Desactivar (solo superadmin) ───────────────────────────────
    public function toggle()
    {
        $this->requireSuperAdmin(true);
        global $pdo;
        header('Content-Type: application/json');
        $id = $_POST['id'] ?? null;
        if (!$id) { echo json_encode(['success' => false]); exit; }

        $pdo->prepare("UPDATE suppliers SET active = NOT active WHERE id = ?")->execute([$id]);
        $active = $pdo->prepare("SELECT active FROM suppliers WHERE id = ?");
        $active->execute([$id]);
        $state = $active->fetchColumn();

        echo json_encode(['success' => true, 'active' => (bool)$state]);
        exit;
    }

    // ─── Catálogo del Proveedor ───────────────────────────────────────────────
    public function catalog()
    {
        global $pdo;
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: ' . APP_BASE . '/supplier/index'); exit; }

        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$supplier) { header('Location: ' . APP_BASE . '/supplier/index'); exit; }

        // Productos YA en catálogo
        $catalogItems = $pdo->prepare(
            "SELECT sp.id AS sp_id, sp.supplier_price, p.id, p.name, p.sku, p.cost
             FROM supplier_products sp
             JOIN products p ON p.id = sp.product_id
             WHERE sp.supplier_id = ?
             ORDER BY p.name ASC"
        );
        $catalogItems->execute([$id]);
        $catalog = $catalogItems->fetchAll(PDO::FETCH_ASSOC);

        // Todos los productos (para el buscador de agregar — solo si superadmin)
        $allProducts = $pdo->query(
            "SELECT id, name, sku, cost FROM products ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $isSuperAdmin = ($_SESSION['user']['role'] === 'superadmin');

        $this->renderAdmin('admin/supplier_catalog', [
            'supplier'     => $supplier,
            'catalog'      => $catalog,
            'allProducts'  => $allProducts,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    // ─── Agregar Producto al Catálogo (AJAX, solo superadmin) ───────────────
    public function addProduct()
    {
        $this->requireSuperAdmin(true);
        global $pdo;
        header('Content-Type: application/json');

        $supplierId = $_POST['supplier_id'] ?? null;
        $productId  = $_POST['product_id'] ?? null;
        $price      = floatval($_POST['supplier_price'] ?? 0);

        if (!$supplierId || !$productId) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO supplier_products (supplier_id, product_id, supplier_price)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE supplier_price = VALUES(supplier_price)"
            );
            $stmt->execute([$supplierId, $productId, $price]);
            echo json_encode(['success' => true, 'message' => 'Producto agregado al catálogo.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─── Quitar Producto del Catálogo (AJAX, solo superadmin) ────────────────
    public function removeProduct()
    {
        $this->requireSuperAdmin(true);
        global $pdo;
        header('Content-Type: application/json');

        $supplierId = $_POST['supplier_id'] ?? null;
        $productId  = $_POST['product_id'] ?? null;

        if (!$supplierId || !$productId) {
            echo json_encode(['success' => false]);
            exit;
        }

        $pdo->prepare(
            "DELETE FROM supplier_products WHERE supplier_id = ? AND product_id = ?"
        )->execute([$supplierId, $productId]);

        echo json_encode(['success' => true]);
        exit;
    }

    // ─── Productos del proveedor (AJAX para order create) ─────────────────────
    public function products()
    {
        global $pdo;
        header('Content-Type: application/json');
        $id = $_GET['id'] ?? null;
        if (!$id) { echo json_encode([]); exit; }

        $stmt = $pdo->prepare(
            "SELECT p.id, p.name, p.sku, p.cost, sp.supplier_price
             FROM supplier_products sp
             JOIN products p ON p.id = sp.product_id
             WHERE sp.supplier_id = ?
             ORDER BY p.name ASC"
        );
        $stmt->execute([$id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
}
