<?php
require_once 'AdminController.php';
require_once 'models/Note.php';
require_once 'config/config.php';

class NotesController extends AdminController
{
    private $noteModel;

    public function __construct()
    {
        parent::__construct();
        global $pdo;
        $this->noteModel = new Note($pdo);
    }

    // Listar todas las notas
    public function index()
    {
        $notes = $this->noteModel->getAllNotes();
        $this->renderAdmin('admin/notes_list', ['notes' => $notes]);
    }

    // Mostrar formulario para crear una nota (número de nota autogenerado)
    public function add()
    {
        global $pdo;
        // Obtener el máximo note_number (convertido a número) y sumar 1
        $stmt = $pdo->query("SELECT MAX(CAST(note_number AS UNSIGNED)) as max_note FROM notes");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNoteNumber = $result && $result['max_note'] ? $result['max_note'] + 1 : 1;
        $this->renderAdmin('admin/notes_add', ['nextNoteNumber' => $nextNoteNumber]);
    }

    // Procesar la creación de la nota
    public function save()
    {
        global $pdo;

        $type = $_POST['type'];
        $note_number = $_POST['note_number'];
        $client_name = $_POST['client_name'];
        $client_document = $_POST['client_document'];
        $client_address = $_POST['client_address'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];

        // Validar credenciales de administrador
        $confirmUsername = trim($_POST['confirm_username'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (empty($confirmUsername) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => "Se requieren credenciales de administrador."]);
            exit;
        }

        $stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmtAdmin->execute([$confirmUsername]);
        $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        if (!$adminData || !password_verify($confirmPassword, $adminData['password']) || ($adminData['role'] != 'admin' && $adminData['role'] != 'superadmin')) {
            echo json_encode(['success' => false, 'message' => "Credenciales incorrectas o sin permisos de administrador."]);
            exit;
        }

        $admin_id = $adminData['id'];
        $admin_name = $adminData['username'];

        // Crear nota
        require_once 'models/Note.php';
        $noteModel = new Note($pdo);
        $result = $noteModel->createNote($type, $note_number, $client_name, $client_document, $client_address, $description, $amount, $admin_id, $admin_name);

        if ($result) {
            echo json_encode(['success' => true, 'message' => "Nota de " . ucfirst($type) . " creada correctamente."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Error al crear la nota."]);
        }
        exit;
    }


    // Cancelar (anular) una nota. Solo usuarios con rol 'superadmin' pueden anular.
    public function cancel($id)
    {
        global $pdo;
        // Obtener la nota a anular
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
        $stmt->execute([$id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$note) {
            echo json_encode(['success' => false, 'message' => "Nota no encontrada."]);
            exit;
        }
        if ($note['status'] === 'cancelled') {
            echo json_encode(['success' => false, 'message' => "La nota ya se encuentra anulada."]);
            exit;
        }

        // Validar credenciales: se requiere que el usuario tenga rol 'superadmin'
        $confirmUsername = trim($_POST['confirm_username'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (empty($confirmUsername) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => "Se requieren credenciales para anular la nota."]);
            exit;
        }

        $stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmtAdmin->execute([$confirmUsername]);
        $userData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        if (!$userData || !password_verify($confirmPassword, $userData['password']) || $userData['role'] != 'superadmin') {
            echo json_encode(['success' => false, 'message' => "Credenciales incorrectas o sin permisos de usuario superior."]);
            exit;
        }

        // Actualizar la nota: marcar como cancelada, registrar quién anuló y la fecha
        $stmtUpdate = $pdo->prepare("UPDATE notes SET status = 'cancelled', cancelled_by = ?, cancelled_at = NOW() WHERE id = ?");
        if ($stmtUpdate->execute([$userData['username'], $id])) {
            echo json_encode(['success' => true, 'message' => "Nota anulada correctamente."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Error al anular la nota."]);
        }
        exit;
    }
}
?>