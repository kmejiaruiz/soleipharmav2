<?php
class Notification
{
    private $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function create($userId, $message)
    {
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        return $stmt->execute([$userId, $message]);
    }
    public function getUnread($userId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function markRead($id)
    {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?");
        return $stmt->execute([$id]);
    }
}