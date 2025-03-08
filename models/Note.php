<?php
require_once 'BaseModel.php';

class Note extends BaseModel
{
    public function createNote($type, $note_number, $client_name, $client_document, $client_address, $description, $amount, $admin_id, $admin_name)
    {
        $stmt = $this->pdo->prepare("INSERT INTO notes (type, note_number, client_name, client_document, client_address, description, amount, admin_id, admin_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$type, $note_number, $client_name, $client_document, $client_address, $description, $amount, $admin_id, $admin_name]);
    }

    public function getAllNotes()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM notes ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>