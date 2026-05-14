<?php
require_once 'AdminController.php';

/**
 * NotesController — Módulo de Notas de Crédito/Débito DESHABILITADO.
 * Este módulo fue eliminado del sistema. Todas las acciones redirigen al Dashboard.
 */
class NotesController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        header('Location: ' . APP_BASE . '/admin/index');
        exit;
    }

    public function add()
    {
        header('Location: ' . APP_BASE . '/admin/index');
        exit;
    }

    public function save()
    {
        echo json_encode(['success' => false, 'message' => 'Módulo de notas deshabilitado.']);
        exit;
    }

    public function cancel($id = null)
    {
        echo json_encode(['success' => false, 'message' => 'Módulo de notas deshabilitado.']);
        exit;
    }
}
?>