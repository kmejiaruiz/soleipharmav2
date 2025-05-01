<?php
class DiscardRequest
{
    private $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Crear nueva solicitud
    public function create($productId, $userId, $qty, $reason)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO discard_requests
           (product_id, requested_by, quantity, reason)
           VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$productId, $userId, $qty, $reason]);
    }

    // Pendientes para superadmin
    public function getPending()
    {
        $stmt = $this->pdo->query(
            "SELECT dr.*, p.name AS product_name,
             CONCAT(u.first_name,' ',u.second_name,' ',u.last_name,' ',u.second_surname) AS requester_name
           FROM discard_requests dr
           JOIN products p ON dr.product_id = p.id
           JOIN users u ON dr.requested_by = u.id
           WHERE dr.status = 'pending'
           ORDER BY dr.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Historial completo
    public function getAll()
    {
        $stmt = $this->pdo->query(
            "SELECT dr.*, p.name AS product_name,
             CONCAT(u.first_name,' ',u.second_name,' ',u.last_name,' ',u.second_surname) AS requester_name,
             CONCAT(u2.first_name,' ',u2.second_name,' ',u2.last_name,' ',u2.second_surname) AS decision_name
           FROM discard_requests dr
           JOIN products p ON dr.product_id = p.id
           JOIN users u ON dr.requested_by = u.id
           LEFT JOIN users u2 ON dr.decision_by = u2.id
           ORDER BY dr.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Aprobar/Rechazar
    public function decide($id, $decisionBy, $status, $decisionReason)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE discard_requests SET
            status = ?, decision_by = ?, decision_reason = ?, decision_at = NOW()
           WHERE id = ?"
        );
        return $stmt->execute([$status, $decisionBy, $decisionReason, $id]);
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM discard_requests WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}