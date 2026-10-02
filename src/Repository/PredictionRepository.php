<?php

namespace App\Repository;

use PDO;

class PredictionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findEligibleDrivers(int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT p.id, p.numero_pilote, p.prenom_pilote, p.nom_pilote
            FROM courses c
            INNER JOIN pilotes_engagement pe ON pe.saison_id = c.id_saison
            INNER JOIN pilotes p ON p.id = pe.pilote_id
            WHERE c.id = :course_id AND pe.role = 'titulaire'
            ORDER BY p.numero_pilote ASC"
        );
        $stmt->execute([':course_id' => $courseId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findUserPrediction(int $userId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT P1 AS p1, P2 AS p2, P3 AS p3
            FROM predictions
            WHERE user_id = :user_id AND course_id = :course_id'
        );
        $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        $prediction = $stmt->fetch(PDO::FETCH_ASSOC);

        return $prediction ?: null;
    }

    public function findCoursePredictions(int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.user_login,
                CONCAT(p1.prenom_pilote, \' \', p1.nom_pilote) AS p1_name,
                CONCAT(p2.prenom_pilote, \' \', p2.nom_pilote) AS p2_name,
                CONCAT(p3.prenom_pilote, \' \', p3.nom_pilote) AS p3_name,
                predictions.date_post
            FROM predictions
            INNER JOIN users u ON u.id = predictions.user_id
            INNER JOIN pilotes p1 ON p1.id = predictions.P1
            INNER JOIN pilotes p2 ON p2.id = predictions.P2
            INNER JOIN pilotes p3 ON p3.id = predictions.P3
            WHERE predictions.course_id = :course_id
            ORDER BY predictions.date_post DESC, u.user_login ASC'
        );
        $stmt->execute([':course_id' => $courseId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function areEligibleDrivers(int $courseId, int $p1, int $p2, int $p3): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT p.id)
            FROM courses c
            INNER JOIN pilotes_engagement pe ON pe.saison_id = c.id_saison
            INNER JOIN pilotes p ON p.id = pe.pilote_id
            WHERE c.id = :course_id
                AND pe.role = 'titulaire'
                AND p.id IN (:p1, :p2, :p3)"
        );
        $stmt->execute([
            ':course_id' => $courseId,
            ':p1' => $p1,
            ':p2' => $p2,
            ':p3' => $p3,
        ]);

        return (int) $stmt->fetchColumn() === 3;
    }

    public function save(int $userId, int $courseId, int $p1, int $p2, int $p3): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO predictions (user_id, course_id, P1, P2, P3, date_post)
            VALUES (:user_id, :course_id, :p1, :p2, :p3, NOW())
            ON DUPLICATE KEY UPDATE
                P1 = VALUES(P1),
                P2 = VALUES(P2),
                P3 = VALUES(P3),
                date_post = NOW()'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':course_id' => $courseId,
            ':p1' => $p1,
            ':p2' => $p2,
            ':p3' => $p3,
        ]);
    }

    public function findOpenCourse(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT date_fp1, date_course
            FROM courses
            WHERE id = :course_id AND is_cancelled = 'no'"
        );
        $stmt->execute([':course_id' => $courseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        return $course ?: null;
    }
}