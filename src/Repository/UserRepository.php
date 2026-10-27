<?php

namespace App\Repository;

use PDO;

class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT u.id, u.user_login, u.user_email, u.user_role,
                (SELECT COUNT(*) FROM bingos b WHERE b.user_id = u.id) AS bingos_count,
                (SELECT COUNT(*) FROM predictions p WHERE p.user_id = u.id) AS predictions_count,
                (SELECT COUNT(*) FROM chat c WHERE c.user_id = u.id) AS messages_count,
                (SELECT COUNT(*) FROM support_tickets t WHERE t.user_id = u.id) AS tickets_count
            FROM users u
            ORDER BY u.user_login ASC, u.id ASC'
        );

        return array_map($this->mapUser(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_login, user_email, user_role
            FROM users
            WHERE id = :id'
        );
        $statement->execute([':id' => $id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user === false ? null : $this->mapUser($user);
    }

    public function usage(int $id): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM bingos WHERE user_id = :bingo_user_id) AS bingos_count,
                (SELECT COUNT(*) FROM predictions WHERE user_id = :prediction_user_id) AS predictions_count,
                (SELECT COUNT(*) FROM chat WHERE user_id = :chat_user_id) AS messages_count,
                (SELECT COUNT(*) FROM support_tickets WHERE user_id = :ticket_user_id) AS tickets_count'
        );
        $statement->execute([
            ':bingo_user_id' => $id,
            ':prediction_user_id' => $id,
            ':chat_user_id' => $id,
            ':ticket_user_id' => $id,
        ]);
        $usage = $statement->fetch(PDO::FETCH_ASSOC);

        return [
            'bingos_count' => (int) ($usage['bingos_count'] ?? 0),
            'predictions_count' => (int) ($usage['predictions_count'] ?? 0),
            'messages_count' => (int) ($usage['messages_count'] ?? 0),
            'tickets_count' => (int) ($usage['tickets_count'] ?? 0),
        ];
    }

    public function activity(int $id): array
    {
        $bingos = $this->pdo->prepare(
            'SELECT s.annee
            FROM bingos b
            INNER JOIN saisons s ON s.id = b.saison_id
            WHERE b.user_id = :user_id
            ORDER BY s.annee DESC'
        );
        $bingos->execute([':user_id' => $id]);

        $predictions = $this->pdo->prepare(
            'SELECT s.annee, c.num_manche, c.nom_circuit, pr.date_post,
                p1.prenom_pilote AS p1_prenom, p1.nom_pilote AS p1_nom,
                p2.prenom_pilote AS p2_prenom, p2.nom_pilote AS p2_nom,
                p3.prenom_pilote AS p3_prenom, p3.nom_pilote AS p3_nom
            FROM predictions pr
            INNER JOIN courses c ON c.id = pr.course_id
            INNER JOIN saisons s ON s.id = c.id_saison
            INNER JOIN pilotes p1 ON p1.id = pr.P1
            INNER JOIN pilotes p2 ON p2.id = pr.P2
            INNER JOIN pilotes p3 ON p3.id = pr.P3
            WHERE pr.user_id = :user_id
            ORDER BY pr.date_post DESC, pr.id DESC'
        );
        $predictions->execute([':user_id' => $id]);

        $messages = $this->pdo->prepare(
            'SELECT message, date_post, is_deleted
            FROM chat
            WHERE user_id = :user_id
            ORDER BY date_post DESC, id DESC'
        );
        $messages->execute([':user_id' => $id]);

        $tickets = $this->pdo->prepare(
            'SELECT id, category, subject, status, created_at
            FROM support_tickets
            WHERE user_id = :user_id
            ORDER BY created_at DESC, id DESC'
        );
        $tickets->execute([':user_id' => $id]);

        return [
            'bingos' => array_map(static fn (array $row): int => (int) $row['annee'], $bingos->fetchAll(PDO::FETCH_ASSOC)),
            'predictions' => array_map(static function (array $row): array {
                $name = static fn (string $firstName, string $lastName): string => trim($firstName . ' ' . $lastName);

                return [
                    'annee' => (int) $row['annee'],
                    'manche' => (int) $row['num_manche'],
                    'circuit' => (string) $row['nom_circuit'],
                    'date_post' => (string) $row['date_post'],
                    'p1' => $name((string) $row['p1_prenom'], (string) $row['p1_nom']),
                    'p2' => $name((string) $row['p2_prenom'], (string) $row['p2_nom']),
                    'p3' => $name((string) $row['p3_prenom'], (string) $row['p3_nom']),
                ];
            }, $predictions->fetchAll(PDO::FETCH_ASSOC)),
            'messages' => array_map(static fn (array $row): array => [
                'message' => (string) $row['message'],
                'date_post' => (string) $row['date_post'],
                'deleted' => (int) $row['is_deleted'] === 1,
            ], $messages->fetchAll(PDO::FETCH_ASSOC)),
            'tickets' => array_map(static fn (array $row): array => [
                'id' => (int) $row['id'],
                'category' => (string) $row['category'],
                'subject' => (string) $row['subject'],
                'status' => (string) $row['status'],
                'created_at' => (string) $row['created_at'],
            ], $tickets->fetchAll(PDO::FETCH_ASSOC)),
        ];
    }

    public function adminCount(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users WHERE user_role = \'admin\'')->fetchColumn();
    }

    public function findSessionState(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT user_role, auth_version FROM users WHERE id = :id'
        );
        $statement->execute([':id' => $id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        if ($user === false) {
            return null;
        }

        return [
            'user_role' => (string) $user['user_role'],
            'auth_version' => (int) $user['auth_version'],
        ];
    }

    public function updateRole(int $id, string $role): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users
            SET auth_version = auth_version + IF(user_role <> :next_role, 1, 0),
                user_role = :role
            WHERE id = :id'
        );
        $statement->execute([
            ':next_role' => $role,
            ':role' => $role,
            ':id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute([':id' => $id]);
    }

    private function mapUser(array $user): array
    {
        $mapped = [
            'id' => (int) $user['id'],
            'user_login' => (string) $user['user_login'],
            'user_email' => (string) $user['user_email'],
            'user_role' => (string) $user['user_role'],
        ];
        foreach (['bingos_count', 'predictions_count', 'messages_count', 'tickets_count'] as $count) {
            if (array_key_exists($count, $user)) {
                $mapped[$count] = (int) $user[$count];
            }
        }

        return $mapped;
    }
}
