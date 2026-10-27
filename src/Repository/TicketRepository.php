<?php

namespace App\Repository;

use PDO;

class TicketRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT t.id, t.user_id, t.user_email, t.category, t.subject, t.status, t.priority, t.created_at,
                u.user_login
            FROM support_tickets t
            LEFT JOIN users u ON u.id = t.user_id
            ORDER BY t.created_at DESC, t.id DESC'
        );

        return array_map($this->mapTicket(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.id, t.user_id, t.user_email, t.category, t.subject, t.message, t.response, t.page_url,
                t.course_id, t.status, t.priority, t.created_at, t.updated_at, t.browser_info,
                u.user_login, c.nom_circuit, c.num_manche, s.annee
            FROM support_tickets t
            LEFT JOIN users u ON u.id = t.user_id
            LEFT JOIN courses c ON c.id = t.course_id
            LEFT JOIN saisons s ON s.id = c.id_saison
            WHERE t.id = :id'
        );
        $statement->execute([':id' => $id]);
        $ticket = $statement->fetch(PDO::FETCH_ASSOC);

        return $ticket === false ? null : $this->mapTicket($ticket);
    }

    public function findByUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.id, t.category, t.subject, t.response, t.status, t.created_at,
                c.nom_circuit, s.annee
            FROM support_tickets t
            LEFT JOIN courses c ON c.id = t.course_id
            LEFT JOIN saisons s ON s.id = c.id_saison
            WHERE t.user_id = :user_id
            ORDER BY t.created_at DESC, t.id DESC'
        );
        $statement->execute([':user_id' => $userId]);

        return array_map($this->mapTicket(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findCourses(): array
    {
        $statement = $this->pdo->query(
            'SELECT c.id, c.num_manche, c.nom_circuit, s.annee
            FROM courses c
            INNER JOIN saisons s ON s.id = c.id_saison
            ORDER BY s.annee DESC, c.num_manche ASC, c.id ASC'
        );

        return array_map(static fn (array $course): array => [
            'id' => (int) $course['id'],
            'manche' => (int) $course['num_manche'],
            'circuit' => (string) $course['nom_circuit'],
            'annee' => (int) $course['annee'],
        ], $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findCourse(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id, s.annee
            FROM courses c
            INNER JOIN saisons s ON s.id = c.id_saison
            WHERE c.id = :id'
        );
        $statement->execute([':id' => $id]);
        $course = $statement->fetch(PDO::FETCH_ASSOC);

        if ($course === false) {
            return null;
        }

        return [
            'id' => (int) $course['id'],
            'annee' => (int) $course['annee'],
        ];
    }

    public function findAccountEmail(int $userId): ?string
    {
        $statement = $this->pdo->prepare('SELECT user_email FROM users WHERE id = :id');
        $statement->execute([':id' => $userId]);
        $email = $statement->fetchColumn();

        return is_string($email) && $email !== '' ? $email : null;
    }

    public function create(array $ticket): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO support_tickets
                (user_id, user_email, category, subject, message, page_url, course_id, status, priority, created_at, updated_at, browser_info)
            VALUES
                (:user_id, :user_email, :category, :subject, :message, :page_url, :course_id, \'nouveau\', \'normale\', NOW(), NOW(), :browser_info)'
        );
        $statement->execute([
            ':user_id' => $ticket['user_id'],
            ':user_email' => $ticket['user_email'],
            ':category' => $ticket['category'],
            ':subject' => $ticket['subject'],
            ':message' => $ticket['message'],
            ':page_url' => $ticket['page_url'],
            ':course_id' => $ticket['course_id'],
            ':browser_info' => $ticket['browser_info'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function takeCharge(int $id, string $priority): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE support_tickets
            SET status = \'en_cours\', priority = :priority, updated_at = NOW()
            WHERE id = :id AND status = \'nouveau\''
        );
        $statement->execute([':priority' => $priority, ':id' => $id]);
    }

    public function updatePriority(int $id, string $priority): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE support_tickets
            SET priority = :priority, updated_at = NOW()
            WHERE id = :id AND status = \'en_cours\''
        );
        $statement->execute([':priority' => $priority, ':id' => $id]);
    }

    public function saveResponse(int $id, string $response): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE support_tickets
            SET response = :response, status = \'traite\', updated_at = NOW()
            WHERE id = :id'
        );
        $statement->execute([':response' => $response, ':id' => $id]);
    }

    private function mapTicket(array $ticket): array
    {
        $mapped = ['id' => (int) $ticket['id']];
        $strings = [
            'user_email', 'category', 'subject', 'message', 'response', 'page_url', 'status', 'priority',
            'created_at', 'updated_at', 'browser_info', 'user_login', 'nom_circuit',
        ];
        foreach ($strings as $key) {
            if (!array_key_exists($key, $ticket)) {
                continue;
            }
            $mapped[$key] = $ticket[$key] === null ? null : (string) $ticket[$key];
        }
        foreach (['user_id', 'course_id', 'num_manche', 'annee'] as $key) {
            if (!array_key_exists($key, $ticket)) {
                continue;
            }
            $mapped[$key] = $ticket[$key] === null ? null : (int) $ticket[$key];
        }

        return $mapped;
    }
}
