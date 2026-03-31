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

    // Verificar si ya existe una solicitud pendiente para este producto
    public function hasPendingRequest($productId)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM discard_requests WHERE product_id = ? AND status = 'pending'");
        $stmt->execute([$productId]);
        return $stmt->fetchColumn() > 0;
    }

    // Pendientes para superadmin
    public function getPending($userId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT dr.*, p.name AS product_name, p.stock AS current_stock,
             CONCAT(u.first_name,' ',u.second_name,' ',u.last_name,' ',u.second_surname) AS requester_name,
             (dr.assigned_to = :userId) as is_assignee,
             (dro.user_id IS NOT NULL) as is_observer
           FROM discard_requests dr
           JOIN products p ON dr.product_id = p.id
           JOIN users u ON dr.requested_by = u.id
           LEFT JOIN discard_request_observers dro ON dr.id = dro.request_id AND dro.user_id = :userId
           WHERE dr.status = 'pending'
             AND (dr.assigned_to IS NULL OR dr.assigned_to = :userId OR dro.user_id IS NOT NULL)
           ORDER BY dr.created_at DESC"
        );
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Historial completo
    public function getAll($userId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT dr.*, p.name AS product_name,
             CONCAT(u.first_name,' ',u.second_name,' ',u.last_name,' ',u.second_surname) AS requester_name,
             CONCAT(u2.first_name,' ',u2.second_name,' ',u2.last_name,' ',u2.second_surname) AS decision_name
           FROM discard_requests dr
           JOIN products p ON dr.product_id = p.id
           JOIN users u ON dr.requested_by = u.id
           LEFT JOIN users u2 ON dr.decision_by = u2.id
           LEFT JOIN discard_request_observers dro ON dr.id = dro.request_id AND dro.user_id = :userId
           WHERE dr.assigned_to IS NULL 
              OR dr.assigned_to = :userId 
              OR dro.user_id IS NOT NULL 
              OR dr.decision_by = :userId
           ORDER BY dr.created_at DESC"
        );
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Historial por usuario específico (Mis Descartes)
    public function getByUser($userId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT dr.*, p.name AS product_name,
             CONCAT(u.first_name,' ',u.second_name,' ',u.last_name,' ',u.second_surname) AS requester_name,
             CONCAT(u2.first_name,' ',u2.second_name,' ',u2.last_name,' ',u2.second_surname) AS decision_name
           FROM discard_requests dr
           JOIN products p ON dr.product_id = p.id
           JOIN users u ON dr.requested_by = u.id
           LEFT JOIN users u2 ON dr.decision_by = u2.id
           WHERE dr.requested_by = ?
           ORDER BY dr.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Aprobar/Rechazar/Revisión/Seguimiento
    public function decide($id, $decisionBy, $status, $decisionReason, $isFollowUp = 0)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE discard_requests SET
            status = ?, is_follow_up = ?, decision_by = ?, decision_reason = ?, decision_at = NOW()
           WHERE id = ?"
        );
        return $stmt->execute([$status, $isFollowUp, $decisionBy, $decisionReason, $id]);
    }

    // Administrador: Corregir solicitud en revisión
    public function updateRequest($id, $qty, $reason)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE discard_requests SET
            quantity = ?, reason = ?, status = 'pending', decision_reason = NULL, decision_at = NULL
           WHERE id = ? AND status = 'in_revision'"
        );
        return $stmt->execute([$qty, $reason, $id]);
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM discard_requests WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Asignar responsable y observadores
    public function assignTo($requestId, $assignedTo, $observers = [])
    {
        try {
            $this->pdo->beginTransaction();

            // Set main assignee
            $stmt = $this->pdo->prepare("UPDATE discard_requests SET assigned_to = ? WHERE id = ?");
            $stmt->execute([$assignedTo, $requestId]);

            // Clear old observers just in case
            $stmt = $this->pdo->prepare("DELETE FROM discard_request_observers WHERE request_id = ?");
            $stmt->execute([$requestId]);

            // Add new observers
            if (!empty($observers)) {
                $stmtObs = $this->pdo->prepare("INSERT IGNORE INTO discard_request_observers (request_id, user_id) VALUES (?, ?)");
                foreach ($observers as $obsId) {
                    if ($obsId != $assignedTo) { // avoid adding assignee as observer too
                        $stmtObs->execute([$requestId, $obsId]);
                    }
                }
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}