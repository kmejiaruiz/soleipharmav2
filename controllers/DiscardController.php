<?php
require_once 'AdminController.php';
require_once 'models/DiscardRequest.php';
require_once 'models/Notification.php';
require_once 'models/Product.php';
require_once 'config/config.php';

class DiscardController extends AdminController
{
    private $drModel, $notifModel, $productModel;
    public function __construct()
    {
        parent::__construct();
        global $pdo;
        $this->drModel = new DiscardRequest($pdo);
        $this->notifModel = new Notification($pdo);
        $this->productModel = new Product($pdo);
        if (session_status() === PHP_SESSION_NONE)
            session_start();
    }

    // 1) Formulario de creación (apartado admin)
    public function create()
    {
        // Lista productos para seleccionar
        $products = $this->productModel->getAll();
        $this->renderAdmin('admin/discard_create', ['products' => $products]);
    }

    // 2) Enviar solicitud (admin)
    public function request()
    {
        $productId = intval($_POST['product_id'] ?? 0);
        $qty = intval($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $userId = $_SESSION['user']['id'];
        if (!$productId || $qty <= 0 || !$reason) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }
        $this->drModel->create($productId, $userId, $qty, $reason);
        // Notificar a los superadmins
        $stmt = $GLOBALS['pdo']->query("SELECT id FROM users WHERE role='superadmin'");
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $sid) {
            $this->notifModel->create(
                $sid,
                "Nueva solicitud descarte #{$GLOBALS['pdo']->lastInsertId()} pendiente."
            );
        }
        echo json_encode(['success' => true, 'message' => 'Solicitud enviada.']);
        exit;
    }

    // 3) Superadmin: ver pendientes
    public function listPending()
    {
        $pending = $this->drModel->getPending();
        $this->renderAdmin('admin/discard_list', ['requests' => $pending]);
    }

    // 4) Superadmin: decidir
    public function decide()
    {
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $reason = trim($_POST['decision_reason'] ?? '');
        $userId = $_SESSION['user']['id'];
        if (!$id || !in_array($status, ['approved', 'rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
            exit;
        }
        $this->drModel->decide($id, $userId, $status, $reason);
        // Si aprueba, descontar stock
        if ($status === 'approved') {
            $req = $this->drModel->find($id);
            $this->productModel->updateStock($req['product_id'], -$req['quantity']);
        }
        // Notificar al solicitante
        $req = $this->drModel->find($id);
        $msg = $status === 'approved'
            ? "Tu solicitud #{$id} fue aprobada."
            : "Tu solicitud #{$id} fue rechazada.";
        $this->notifModel->create($req['requested_by'], $msg);
        echo json_encode(['success' => true, 'message' => 'Solicitud ' . $status . '.']);
        exit;
    }

    // 5) Historial (reporte)
    public function history()
    {
        $all = $this->drModel->getAll();
        $this->renderAdmin('admin/discard_history', ['requests' => $all]);
    }
}