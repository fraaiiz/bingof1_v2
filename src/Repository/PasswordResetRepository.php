<?php

namespace App\Repository;

use PDO;

class PasswordResetRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findUserIdByEmail(string $email): ?int
    {
        $statement = $this->pdo->prepare('SELECT id FROM users WHERE user_email = :email LIMIT 1');
        $statement->execute([':email' => $email]);
        $id = $statement->fetchColumn();

        return is_numeric($id) ? (int) $id : null;
    }

    public function hasRecentRequest(int $userId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM password_resets
            WHERE user_id = :user_id AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
            LIMIT 1'
        );
        $statement->execute([':user_id' => $userId]);

        return $statement->fetchColumn() !== false;
    }

    public function create(int $userId, string $tokenHash): void
    {
        $this->pdo->prepare('DELETE FROM password_resets WHERE expires_at <= NOW() OR user_id = :user_id')
            ->execute([':user_id' => $userId]);
        $statement = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
            VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
        ]);
    }

    public function deleteForUser(int $userId): void
    {
        $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id')
            ->execute([':user_id' => $userId]);
    }

    public function findValidUserId(string $tokenHash): ?int
    {
        $statement = $this->pdo->prepare(
            'SELECT user_id FROM password_resets
            WHERE token_hash = :token_hash AND expires_at > NOW()
            LIMIT 1'
        );
        $statement->execute([':token_hash' => $tokenHash]);
        $id = $statement->fetchColumn();

        return is_numeric($id) ? (int) $id : null;
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'UPDATE users
                SET user_password = :password, auth_version = auth_version + 1
                WHERE id = :id'
            );
            $statement->execute([
                ':password' => $passwordHash,
                ':id' => $userId,
            ]);
            $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id')
                ->execute([':user_id' => $userId]);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
