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
        
        // Verificar si ya existe una solicitud de descarte pendiente para este producto
        if ($this->drModel->hasPendingRequest($productId)) {
            echo json_encode(['success' => false, 'message' => 'Ya existe una solicitud de descarte PENDIENTE para este producto. Espere a que sea resuelta.']);
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
        $userId = $_SESSION['user']['id'];
        $pending = $this->drModel->getPending($userId);
        
        // Obtener lista de superadmins para el modal de asignación
        global $pdo;
        $stmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'superadmin'");
        $superadmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/discard_list', ['requests' => $pending, 'superadmins' => $superadmins]);
    }

    // 4) Superadmin: decidir
    public function decide()
    {
        $id = intval($_POST['id'] ?? 0);
        $rawStatus = $_POST['status'] ?? '';
        $reason = trim($_POST['decision_reason'] ?? '');
        $isFollowUp = intval($_POST['is_follow_up'] ?? 0);
        $userId = $_SESSION['user']['id'];
        
        if (!$id || !in_array($rawStatus, ['approved', 'rejected', 'in_revision', 'in_follow_up'])) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
            exit;
        }

        // Obtener solicitud para validaciones de asignación
        $req = $this->drModel->find($id);
        if (!$req) {
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada.']);
            exit;
        }

        // Verificar permisos de decisión
        if ($req['assigned_to'] !== null && $req['assigned_to'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para decidir sobre esta solicitud asignada a otro responsable.']);
            exit;
        }

        // Determinar status real a guardar en DB.
        // Si mandamos "in_follow_up" puro, en realidad lo dejamos "pending" y prendemos el flag de is_follow_up
        $dbStatus = $rawStatus;
        if ($rawStatus === 'in_follow_up') {
            $dbStatus = 'pending';
            $isFollowUp = 1;
        }

        $this->drModel->decide($id, $userId, $dbStatus, $reason, $isFollowUp);

        // Si aprueba, descontar stock
        if ($dbStatus === 'approved') {
            $this->productModel->updateStock($req['product_id'], -$req['quantity']);
        }

        // Notificar al solicitante principal
        $msg = "Tu solicitud #{$id} ha sido actualizada.";
        if ($dbStatus === 'approved') $msg = "Tu solicitud #{$id} fue aprobada.";
        else if ($dbStatus === 'rejected') $msg = "Tu solicitud #{$id} fue rechazada.";
        else if ($dbStatus === 'in_revision' && $isFollowUp) $msg = "Tu solicitud #{$id} requiere corrección y ha sido enmarcada en seguimiento interno.";
        else if ($dbStatus === 'in_revision') $msg = "Tu solicitud #{$id} requiere revisión/corrección.";
        else if ($isFollowUp) $msg = "Tu solicitud #{$id} está en seguimiento interno.";
            
        $this->notifModel->create($req['requested_by'], $msg);

        // Notificar a observadores si existen
        global $pdo;
        $stmtObs = $pdo->prepare("SELECT user_id FROM discard_request_observers WHERE request_id = ?");
        $stmtObs->execute([$id]);
        $observers = $stmtObs->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($observers)) {
            $obsMsg = "El responsable ha actuado sobre la solicitud #{$id} que observas: " . ucfirst(str_replace('_', ' ', $dbStatus));
            foreach ($observers as $obsId) {
                $this->notifModel->create($obsId, $obsMsg);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Solicitud actualizada correctamente.']);
        exit;
    }

    // Acción para que un Superadmin asigne una solicitud
    public function assignRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        $assignedTo = intval($_POST['assigned_to'] ?? 0);
        $observers = isset($_POST['observers']) && is_array($_POST['observers']) ? $_POST['observers'] : [];

        if (!$id || !$assignedTo) {
            echo json_encode(['success' => false, 'message' => 'El responsable es obligatorio.']);
            exit;
        }

        $req = $this->drModel->find($id);
        if (!$req || $req['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'La solicitud no es válida o ya fue procesada.']);
            exit;
        }

        // Solo se pueden asignar solicitudes que aún no estén asignadas
        if ($req['assigned_to'] !== null) {
            echo json_encode(['success' => false, 'message' => 'Esta solicitud ya tiene un superadmin responsable.']);
            exit;
        }

        if ($this->drModel->assignTo($id, $assignedTo, $observers)) {
            // Notificar al nuevo responsable
            if ($assignedTo != $_SESSION['user']['id']) {
                $this->notifModel->create($assignedTo, "Se te ha asignado como responsable para decidir sobre el descarte #{$id}.");
            }
            // Notificar a los observadores
            foreach ($observers as $obsId) {
                if ($obsId != $_SESSION['user']['id'] && $obsId != $assignedTo) {
                    $this->notifModel->create($obsId, "Se te ha asignado como observador de la solicitud de descarte #{$id}.");
                }
            }

            echo json_encode(['success' => true, 'message' => 'Solicitud asignada exitosamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al asignar solicitud en base de datos.']);
        }
        exit;
    }

    // Mostrar formulario de edición al administrador
    public function edit()
    {
        if ($_SESSION['user']['role'] !== 'admin') {
            header('Location: /soleipharmav2/discard/myHistory');
            exit;
        }

        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: /soleipharmav2/discard/myHistory');
            exit;
        }

        // Obtener la solicitud con datos del producto
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT dr.*, p.name AS product_name, p.stock AS current_stock
         FROM discard_requests dr
         JOIN products p ON dr.product_id = p.id
         WHERE dr.id = ?"
    );
    $stmt->execute([$id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$req || $req['status'] !== 'in_revision' || $req['requested_by'] != $_SESSION['user']['id']) {
            header('Location: /soleipharmav2/discard/myHistory');
            exit;
        }

        $this->renderAdmin('admin/discard_edit', ['request' => $req]);
    }

    // Acción para que el Admin edite una solicitud "in_revision"
    public function editRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        $qty = intval($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if (!$id || $qty <= 0 || !$reason) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // Obtener la solicitud actual para verificar estado
        $req = $this->drModel->find($id);
        if (!$req || $req['status'] !== 'in_revision' || $req['requested_by'] != $_SESSION['user']['id']) {
            echo json_encode(['success' => false, 'message' => 'La solicitud no autoriza edición o no te pertenece.']);
            exit;
        }

        // Actualizar la solicitud a pending
        $this->drModel->updateRequest($id, $qty, $reason);

        // Notificar a superadmins que fue corregida
        $stmt = $GLOBALS['pdo']->query("SELECT id FROM users WHERE role='superadmin'");
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $sid) {
            $this->notifModel->create(
                $sid,
                "El descarte #{$id} ha sido corregido y reenviado por el administrador al que pertenece."
            );
        }

        echo json_encode(['success' => true, 'message' => 'Solicitud corregida y reenviada a revisión.']);
        exit;
    }

    // 5) Historial (reporte)
    public function history()
    {
        $userId = $_SESSION['user']['id'];
        $all = $this->drModel->getAll($userId);
        $this->renderAdmin('admin/discard_history', ['requests' => $all]);
    }

    // 6) Mis Descartes (para administradores normales)
    public function myHistory()
    {
        $userId = $_SESSION['user']['id'];
        $myRequests = $this->drModel->getByUser($userId);
        $this->renderAdmin('admin/discard_my_history', ['requests' => $myRequests]);
    }
}