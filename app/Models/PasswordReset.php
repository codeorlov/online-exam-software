<?php
/**
 * Модель для роботи з токенами скидання пароля
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class PasswordReset
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Створити токен скидання пароля
     */
    public function create(string $email, string $token, int $expiresInHours = 24): int
    {
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInHours * 3600));

        $this->deleteByEmail($email);

        $stmt = $this->db->prepare("
            INSERT INTO password_resets (email, token, created_at, expires_at, used)
            VALUES (:email, :token, NOW(), :expires_at, 0)
        ");

        $stmt->execute([
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiresAt
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Знайти токен за значенням
     */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM password_resets 
            WHERE token = :token 
            AND used = 0 
            AND expires_at > NOW()
            ORDER BY created_at DESC 
            LIMIT 1
        ");

        $stmt->execute(['token' => $token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Помітити токен як використаний
     */
    public function markAsUsed(string $token): bool
    {
        $stmt = $this->db->prepare("
            UPDATE password_resets 
            SET used = 1 
            WHERE token = :token
        ");

        return $stmt->execute(['token' => $token]);
    }

    /**
     * Видалити токени за email
     */
    public function deleteByEmail(string $email): bool
    {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = :email");
        return $stmt->execute(['email' => $email]);
    }

    /**
     * Очистити вийшли токени
     */
    public function cleanExpired(): int
    {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
