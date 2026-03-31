<?php
// models/User.php
require_once 'BaseModel.php';

class User extends BaseModel {

    public function findByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($username, $password, $role = 'user') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $hash, $role]);
    }

    // ── Login Lockout ───────────────────────────────────────────

    public function incrementFailedAttempts($username) {
        $stmt = $this->pdo->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE username = ?");
        $stmt->execute([$username]);
    }

    public function resetFailedAttempts($username) {
        $stmt = $this->pdo->prepare("UPDATE users SET failed_login_attempts = 0 WHERE username = ?");
        $stmt->execute([$username]);
    }

    public function lockUser($username) {
        $stmt = $this->pdo->prepare("UPDATE users SET is_locked = 1, locked_at = NOW() WHERE username = ?");
        $stmt->execute([$username]);
    }

    public function getLockedUsers() {
        $stmt = $this->pdo->query("SELECT id, username, first_name, last_name, second_surname, role, locked_at FROM users WHERE is_locked = 1 ORDER BY locked_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function unlockUser($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET is_locked = 0, failed_login_attempts = 0, locked_at = NULL WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function verifyPasswordById($userId, $password) {
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        if ($hash && password_verify($password, $hash)) {
            return true;
        }
        return false;
    }

    // ── Password Reset ──────────────────────────────────────────

    /**
     * Crea un token de recuperación para el usuario dado.
     * Devuelve el token generado, o false si el usuario no existe.
     */
    public function createResetToken($username) {
        // Verificar que el usuario existe
        $user = $this->findByUsername($username);
        if (!$user) return false;

        // Invalidar tokens anteriores del mismo usuario
        $del = $this->pdo->prepare("DELETE FROM password_resets WHERE username = ?");
        $del->execute([$username]);

        // Generar token seguro
        $token = bin2hex(random_bytes(32)); // 64 chars hex

        $ins = $this->pdo->prepare(
            "INSERT INTO password_resets (username, token) VALUES (?, ?)"
        );
        $ins->execute([$username, $token]);

        return $token;
    }

    /**
     * Valida un token. Devuelve el username si es válido y no usado, false si no.
     * Tokens expiran a los 30 minutos.
     */
    public function validateResetToken($token) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM password_resets
             WHERE token = ? AND used = 0
               AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['username'] : false;
    }

    /**
     * Cambia la contraseña del usuario y marca el token como usado.
     */
    public function resetPassword($token, $newPassword) {
        $username = $this->validateResetToken($token);
        if (!$username) return false;

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $this->pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $upd->execute([$hash, $username]);

        // Marcar token como usado
        $mark = $this->pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $mark->execute([$token]);

        return true;
    }
}
?>
