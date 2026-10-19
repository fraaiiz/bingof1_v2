<?php

namespace App\Repository;

use PDO;

class ChatRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findVisible(int $limit = 200): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, user_login, message, date_post, is_admin_message
            FROM (
                SELECT c.id, c.user_id, u.user_login, c.message, c.date_post, c.is_admin_message
                FROM chat c
                INNER JOIN users u ON u.id = c.user_id
                WHERE c.is_deleted = 0
                ORDER BY c.id DESC
                LIMIT :limit
            ) recent
            ORDER BY id ASC'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $this->formatMessages($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findVisibleAfterId(int $messageId, int $limit = 200): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.user_id, u.user_login, c.message, c.date_post, c.is_admin_message
            FROM chat c
            INNER JOIN users u ON u.id = c.user_id
            WHERE c.is_deleted = 0 AND c.id > :message_id
            ORDER BY c.id ASC
            LIMIT :limit'
        );
        $statement->bindValue(':message_id', $messageId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $this->formatMessages($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function insert(int $userId, string $message, bool $isAdminMessage): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO chat (user_id, message, date_post, is_admin_message, is_deleted, deleted_at)
            VALUES (:user_id, :message, NOW(), :is_admin_message, 0, NULL)'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':message' => $message,
            ':is_admin_message' => $isAdminMessage ? 1 : 0,
        ]);
    }

    public function softDelete(int $messageId, int $deletedBy): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE chat
            SET is_deleted = 1, deleted_at = NOW(), deleted_by = :deleted_by
            WHERE id = :id AND is_deleted = 0'
        );
        $statement->execute([
            ':deleted_by' => $deletedBy,
            ':id' => $messageId,
        ]);

        return $statement->rowCount() > 0;
    }

    private function formatMessages(array $rows): array
    {
        $messages = [];
        foreach ($rows as $row) {
            $isAdminMessage = (bool) $row['is_admin_message'];
            $login = (string) $row['user_login'];
            $postedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $row['date_post']);

            $messages[] = [
                'id' => (int) $row['id'],
                'user_id' => (int) $row['user_id'],
                'user_login' => $isAdminMessage ? 'Admin' : $login,
                'date_post' => $postedAt instanceof \DateTimeImmutable ? $postedAt->format('d/m/Y H:i') : (string) $row['date_post'],
                'message' => (string) $row['message'],
                'is_admin' => $isAdminMessage || strcasecmp($login, 'admin') === 0,
            ];
        }

        return $messages;
    }
}
