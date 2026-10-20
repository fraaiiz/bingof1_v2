<?php

namespace App\Repository;

use PDO;
use PDOStatement;

class CourseRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findBySeason(int $seasonId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT courses.id, courses.num_manche, courses.nom_circuit, courses.date_fp1, courses.date_course,
                courses.format_id, courses.is_cancelled, circuits.pays AS circuit_pays, circuits.ville AS circuit_ville
            FROM courses
            LEFT JOIN circuits ON circuits.id = courses.circuit_id
            WHERE courses.id_saison = :season_id
            ORDER BY courses.num_manche ASC, courses.id ASC'
        );
        $statement->execute([':season_id' => $seasonId]);

        return array_map(static function (array $course): array {
            return [
                'id' => (int) $course['id'],
                'num_manche' => (int) $course['num_manche'],
                'nom_circuit' => (string) $course['nom_circuit'],
                'circuit_pays' => $course['circuit_pays'] !== null ? (string) $course['circuit_pays'] : '',
                'circuit_ville' => $course['circuit_ville'] !== null ? (string) $course['circuit_ville'] : null,
                'date_fp1' => (string) $course['date_fp1'],
                'date_course' => (string) $course['date_course'],
                'format_id' => (int) $course['format_id'],
                'is_cancelled' => (string) $course['is_cancelled'],
            ];
        }, $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findInSeason(int $seasonId, int $courseId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT courses.id, courses.circuit_id, courses.num_manche, courses.nom_circuit,
                courses.date_fp1, courses.date_fp2, courses.date_fp3, courses.date_qualif_sprint,
                courses.date_sprint, courses.date_qualif, courses.date_course, courses.format_id,
                courses.is_cancelled, courses.nombre_tours, courses.longueur_circuit, courses.nombre_virages,
                courses.best_lap, courses.last_winner, courses.half_points,
                circuits.pays AS circuit_pays, circuits.ville AS circuit_ville, circuits.image AS circuit_image
            FROM courses
            LEFT JOIN circuits ON circuits.id = courses.circuit_id
            WHERE courses.id = :course_id AND courses.id_saison = :season_id
            LIMIT 1'
        );
        $statement->execute([':course_id' => $courseId, ':season_id' => $seasonId]);
        $course = $statement->fetch(PDO::FETCH_ASSOC);
        if ($course === false) {
            return null;
        }

        $nullableText = static fn (mixed $value): ?string => $value === null || $value === '' ? null : (string) $value;
        $nullableInt = static fn (mixed $value): ?int => $value === null || $value === '' ? null : (int) $value;

        return [
            'id' => (int) $course['id'],
            'circuit_id' => $nullableInt($course['circuit_id']),
            'num_manche' => (int) $course['num_manche'],
            'nom_circuit' => (string) $course['nom_circuit'],
            'format_id' => (int) $course['format_id'],
            'is_cancelled' => (string) $course['is_cancelled'],
            'date_fp1' => (string) $course['date_fp1'],
            'date_fp2' => $nullableText($course['date_fp2']),
            'date_fp3' => $nullableText($course['date_fp3']),
            'date_qualif_sprint' => $nullableText($course['date_qualif_sprint']),
            'date_sprint' => $nullableText($course['date_sprint']),
            'date_qualif' => $nullableText($course['date_qualif']),
            'date_course' => (string) $course['date_course'],
            'nombre_tours' => $nullableInt($course['nombre_tours']),
            'longueur_circuit' => $nullableText($course['longueur_circuit']),
            'nombre_virages' => $nullableInt($course['nombre_virages']),
            'best_lap' => $nullableText($course['best_lap']),
            'last_winner' => $nullableText($course['last_winner']),
            'half_points' => (int) $course['half_points'],
            'circuit_pays' => $course['circuit_pays'] !== null ? (string) $course['circuit_pays'] : '',
            'circuit_ville' => $nullableText($course['circuit_ville']),
            'circuit_image' => $nullableText($course['circuit_image']),
        ];
    }

    public function findSessionTypesByFormat(): array
    {
        $statement = $this->pdo->query(
            'SELECT format_id, session_type
            FROM sessions
            ORDER BY format_id ASC, session_order ASC'
        );
        $formats = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $formats[(int) $row['format_id']][] = (string) $row['session_type'];
        }

        return $formats;
    }

    public function roundTaken(int $seasonId, int $round, ?int $exceptCourseId = null): bool
    {
        $sql = 'SELECT 1 FROM courses WHERE id_saison = :season_id AND num_manche = :round';
        $values = [':season_id' => $seasonId, ':round' => $round];
        if ($exceptCourseId !== null) {
            $sql .= ' AND id <> :course_id';
            $values[':course_id'] = $exceptCourseId;
        }
        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($values);

        return $statement->fetchColumn() !== false;
    }

    public function create(int $seasonId, array $course): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO courses (
                id_saison, circuit_id, num_manche, nom_circuit,
                date_fp1, date_fp2, date_fp3, date_qualif_sprint, date_sprint, date_qualif, date_course,
                format_id, is_cancelled, nombre_tours, longueur_circuit, nombre_virages,
                best_lap, last_winner, half_points
            ) VALUES (
                :id_saison, :circuit_id, :num_manche, :nom_circuit,
                :date_fp1, :date_fp2, :date_fp3, :date_qualif_sprint, :date_sprint, :date_qualif, :date_course,
                :format_id, :is_cancelled, :nombre_tours, :longueur_circuit, :nombre_virages,
                :best_lap, :last_winner, :half_points
            )'
        );
        $values = [
            ':id_saison' => $seasonId,
            ':circuit_id' => $course['circuit_id'],
            ':num_manche' => $course['num_manche'],
            ':nom_circuit' => $course['nom_circuit'],
            ':date_fp1' => $course['date_fp1'],
            ':date_fp2' => $course['date_fp2'],
            ':date_fp3' => $course['date_fp3'],
            ':date_qualif_sprint' => $course['date_qualif_sprint'],
            ':date_sprint' => $course['date_sprint'],
            ':date_qualif' => $course['date_qualif'],
            ':date_course' => $course['date_course'],
            ':format_id' => $course['format_id'],
            ':is_cancelled' => $course['is_cancelled'],
            ':nombre_tours' => $course['nombre_tours'],
            ':longueur_circuit' => $course['longueur_circuit'],
            ':nombre_virages' => $course['nombre_virages'],
            ':best_lap' => $course['best_lap'],
            ':last_winner' => $course['last_winner'],
            ':half_points' => $course['half_points'],
        ];
        $this->bindValues($statement, $values);
        $statement->execute();
    }

    public function usage(int $courseId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM resultats WHERE course_id = :course_id) AS results_count,
                (SELECT COUNT(*) FROM predictions WHERE course_id = :course_id) AS predictions_count,
                (SELECT COUNT(*) FROM support_tickets WHERE course_id = :course_id) AS tickets_count'
        );
        $statement->execute([':course_id' => $courseId]);
        $usage = $statement->fetch(PDO::FETCH_ASSOC);

        return [
            'results_count' => (int) ($usage['results_count'] ?? 0),
            'predictions_count' => (int) ($usage['predictions_count'] ?? 0),
            'tickets_count' => (int) ($usage['tickets_count'] ?? 0),
        ];
    }

    public function delete(int $seasonId, int $courseId): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM courses WHERE id = :course_id AND id_saison = :season_id'
        );
        $statement->execute([':course_id' => $courseId, ':season_id' => $seasonId]);
    }

    public function update(int $seasonId, int $courseId, array $course): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE courses SET
                circuit_id = :circuit_id,
                num_manche = :num_manche,
                nom_circuit = :nom_circuit,
                date_fp1 = :date_fp1,
                date_fp2 = :date_fp2,
                date_fp3 = :date_fp3,
                date_qualif_sprint = :date_qualif_sprint,
                date_sprint = :date_sprint,
                date_qualif = :date_qualif,
                date_course = :date_course,
                format_id = :format_id,
                is_cancelled = :is_cancelled,
                nombre_tours = :nombre_tours,
                longueur_circuit = :longueur_circuit,
                nombre_virages = :nombre_virages,
                best_lap = :best_lap,
                last_winner = :last_winner,
                half_points = :half_points
            WHERE id = :id AND id_saison = :id_saison'
        );
        $this->bindValues($statement, [
            ':id' => $courseId,
            ':id_saison' => $seasonId,
            ':circuit_id' => $course['circuit_id'],
            ':num_manche' => $course['num_manche'],
            ':nom_circuit' => $course['nom_circuit'],
            ':date_fp1' => $course['date_fp1'],
            ':date_fp2' => $course['date_fp2'],
            ':date_fp3' => $course['date_fp3'],
            ':date_qualif_sprint' => $course['date_qualif_sprint'],
            ':date_sprint' => $course['date_sprint'],
            ':date_qualif' => $course['date_qualif'],
            ':date_course' => $course['date_course'],
            ':format_id' => $course['format_id'],
            ':is_cancelled' => $course['is_cancelled'],
            ':nombre_tours' => $course['nombre_tours'],
            ':longueur_circuit' => $course['longueur_circuit'],
            ':nombre_virages' => $course['nombre_virages'],
            ':best_lap' => $course['best_lap'],
            ':last_winner' => $course['last_winner'],
            ':half_points' => $course['half_points'],
        ]);
        $statement->execute();
    }

    private function bindValues(PDOStatement $statement, array $values): void
    {
        foreach ($values as $placeholder => $value) {
            $type = $value === null
                ? PDO::PARAM_NULL
                : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            $statement->bindValue($placeholder, $value, $type);
        }
    }
}
