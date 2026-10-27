<?php

namespace App\Repository;

use PDO;

class DriverRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, numero_pilote, prenom_pilote, nom_pilote, nationalite_pilote, photo_pilote
            FROM pilotes
            ORDER BY nom_pilote ASC, prenom_pilote ASC, id ASC'
        );
        $drivers = array_map($this->mapDriver(...), $statement->fetchAll(PDO::FETCH_ASSOC));
        if ($drivers === []) {
            return [];
        }

        $engagements = $this->pdo->query(
            'SELECT pe.pilote_id, s.annee, e.nom_court, pe.role
            FROM pilotes_engagement pe
            INNER JOIN saisons s ON s.id = pe.saison_id
            INNER JOIN ecuries e ON e.id = pe.ecurie_id
            ORDER BY s.annee DESC, e.nom_court ASC, pe.id ASC'
        );
        $byDriver = [];
        foreach ($engagements->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $byDriver[(int) $row['pilote_id']][] = [
                'annee' => (int) $row['annee'],
                'nom_court' => (string) $row['nom_court'],
                'role' => (string) $row['role'],
            ];
        }

        foreach ($drivers as $index => $driver) {
            $drivers[$index]['engagements'] = $byDriver[$driver['id']] ?? [];
        }

        return $drivers;
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, numero_pilote, prenom_pilote, nom_pilote, nationalite_pilote, photo_pilote
            FROM pilotes
            WHERE id = :id'
        );
        $statement->execute([':id' => $id]);
        $driver = $statement->fetch(PDO::FETCH_ASSOC);

        return $driver === false ? null : $this->mapDriver($driver);
    }

    public function findEngagements(int $driverId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT pe.id, pe.pilote_id, pe.saison_id, pe.ecurie_id, pe.role,
                pe.date_debut, pe.date_fin, pe.nombre_titre_pilote,
                s.annee, e.nom_ecurie, e.nom_court
            FROM pilotes_engagement pe
            INNER JOIN saisons s ON s.id = pe.saison_id
            INNER JOIN ecuries e ON e.id = pe.ecurie_id
            WHERE pe.pilote_id = :pilote_id
            ORDER BY s.annee DESC, e.nom_ecurie ASC,
                CASE WHEN pe.role = 'titulaire' THEN 0 ELSE 1 END,
                pe.id ASC"
        );
        $statement->execute([':pilote_id' => $driverId]);

        return array_map($this->mapEngagement(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findEngagement(int $driverId, int $engagementId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT pe.id, pe.pilote_id, pe.saison_id, pe.ecurie_id, pe.role,
                pe.date_debut, pe.date_fin, pe.nombre_titre_pilote,
                s.annee, e.nom_ecurie, e.nom_court
            FROM pilotes_engagement pe
            INNER JOIN saisons s ON s.id = pe.saison_id
            INNER JOIN ecuries e ON e.id = pe.ecurie_id
            WHERE pe.id = :id AND pe.pilote_id = :pilote_id'
        );
        $statement->execute([':id' => $engagementId, ':pilote_id' => $driverId]);
        $engagement = $statement->fetch(PDO::FETCH_ASSOC);

        return $engagement === false ? null : $this->mapEngagement($engagement);
    }

    public function findSeasons(): array
    {
        $statement = $this->pdo->query('SELECT id, annee FROM saisons ORDER BY annee DESC');

        return array_map(static function (array $season): array {
            return [
                'id' => (int) $season['id'],
                'annee' => (int) $season['annee'],
            ];
        }, $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findSeason(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, annee FROM saisons WHERE id = :id');
        $statement->execute([':id' => $id]);
        $season = $statement->fetch(PDO::FETCH_ASSOC);

        return $season === false ? null : [
            'id' => (int) $season['id'],
            'annee' => (int) $season['annee'],
        ];
    }

    public function findTeamsBySeason(): array
    {
        $statement = $this->pdo->query(
            'SELECT es.saison_id, e.id, e.nom_ecurie, e.nom_court
            FROM ecuries_saisons es
            INNER JOIN ecuries e ON e.id = es.ecurie_id
            GROUP BY es.saison_id, e.id, e.nom_ecurie, e.nom_court
            ORDER BY e.nom_ecurie ASC'
        );

        return array_map(static function (array $team): array {
            return [
                'saison_id' => (int) $team['saison_id'],
                'id' => (int) $team['id'],
                'nom_ecurie' => (string) $team['nom_ecurie'],
                'nom_court' => (string) $team['nom_court'],
            ];
        }, $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findTeamForSeason(int $seasonId, int $teamId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT e.id, e.nom_ecurie, e.nom_court
            FROM ecuries e
            INNER JOIN ecuries_saisons es ON es.ecurie_id = e.id AND es.saison_id = :saison_id
            WHERE e.id = :ecurie_id
            LIMIT 1'
        );
        $statement->execute([':saison_id' => $seasonId, ':ecurie_id' => $teamId]);
        $team = $statement->fetch(PDO::FETCH_ASSOC);

        return $team === false ? null : [
            'id' => (int) $team['id'],
            'nom_ecurie' => (string) $team['nom_ecurie'],
            'nom_court' => (string) $team['nom_court'],
        ];
    }

    public function photoTaken(string $photo, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM pilotes WHERE photo_pilote = :photo';
        $parameters = [':photo' => $photo];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $parameters[':id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function engagementExists(int $driverId, int $seasonId, int $teamId, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM pilotes_engagement
            WHERE pilote_id = :pilote_id AND saison_id = :saison_id AND ecurie_id = :ecurie_id';
        $parameters = [
            ':pilote_id' => $driverId,
            ':saison_id' => $seasonId,
            ':ecurie_id' => $teamId,
        ];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $parameters[':id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function usage(int $id): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM pilotes_engagement pe WHERE pe.pilote_id = :id) AS engagements_count,
                (SELECT COUNT(*) FROM resultats r WHERE r.pilote_id = :id) AS results_count,
                (SELECT COUNT(*) FROM predictions p WHERE p.P1 = :id OR p.P2 = :id OR p.P3 = :id) AS predictions_count'
        );
        $statement->execute([':id' => $id]);
        $usage = $statement->fetch(PDO::FETCH_ASSOC);

        return [
            'engagements_count' => (int) ($usage['engagements_count'] ?? 0),
            'results_count' => (int) ($usage['results_count'] ?? 0),
            'predictions_count' => (int) ($usage['predictions_count'] ?? 0),
        ];
    }

    public function engagementResultCount(int $driverId, int $seasonId, int $teamId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
            FROM resultats r
            INNER JOIN courses c ON c.id = r.course_id
            WHERE r.pilote_id = :pilote_id
                AND c.id_saison = :saison_id
                AND (r.ecurie_id = :ecurie_id OR r.ecurie_id IS NULL)'
        );
        $statement->execute([
            ':pilote_id' => $driverId,
            ':saison_id' => $seasonId,
            ':ecurie_id' => $teamId,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function create(array $driver): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO pilotes (numero_pilote, prenom_pilote, nom_pilote, nationalite_pilote, photo_pilote)
            VALUES (:numero, :prenom, :nom, :nationalite, :photo)'
        );
        $statement->execute([
            ':numero' => $driver['numero_pilote'],
            ':prenom' => $driver['prenom_pilote'],
            ':nom' => $driver['nom_pilote'],
            ':nationalite' => $driver['nationalite_pilote'],
            ':photo' => $driver['photo_pilote'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $driver): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE pilotes
            SET numero_pilote = :numero,
                prenom_pilote = :prenom,
                nom_pilote = :nom,
                nationalite_pilote = :nationalite,
                photo_pilote = :photo
            WHERE id = :id'
        );
        $statement->execute([
            ':numero' => $driver['numero_pilote'],
            ':prenom' => $driver['prenom_pilote'],
            ':nom' => $driver['nom_pilote'],
            ':nationalite' => $driver['nationalite_pilote'],
            ':photo' => $driver['photo_pilote'],
            ':id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM pilotes WHERE id = :id');
        $statement->execute([':id' => $id]);
    }

    public function createEngagement(int $driverId, array $engagement): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO pilotes_engagement
                (pilote_id, saison_id, ecurie_id, role, date_debut, date_fin, nombre_titre_pilote)
            VALUES
                (:pilote_id, :saison_id, :ecurie_id, :role, :date_debut, :date_fin, :nombre_titre_pilote)'
        );
        $statement->execute([
            ':pilote_id' => $driverId,
            ':saison_id' => $engagement['saison_id'],
            ':ecurie_id' => $engagement['ecurie_id'],
            ':role' => $engagement['role'],
            ':date_debut' => $engagement['date_debut'],
            ':date_fin' => $engagement['date_fin'],
            ':nombre_titre_pilote' => $engagement['nombre_titre_pilote'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateEngagement(int $driverId, int $engagementId, array $engagement): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE pilotes_engagement
            SET saison_id = :saison_id,
                ecurie_id = :ecurie_id,
                role = :role,
                date_debut = :date_debut,
                date_fin = :date_fin,
                nombre_titre_pilote = :nombre_titre_pilote
            WHERE id = :id AND pilote_id = :pilote_id'
        );
        $statement->execute([
            ':saison_id' => $engagement['saison_id'],
            ':ecurie_id' => $engagement['ecurie_id'],
            ':role' => $engagement['role'],
            ':date_debut' => $engagement['date_debut'],
            ':date_fin' => $engagement['date_fin'],
            ':nombre_titre_pilote' => $engagement['nombre_titre_pilote'],
            ':id' => $engagementId,
            ':pilote_id' => $driverId,
        ]);
    }

    public function deleteEngagement(int $driverId, int $engagementId): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM pilotes_engagement WHERE id = :id AND pilote_id = :pilote_id'
        );
        $statement->execute([':id' => $engagementId, ':pilote_id' => $driverId]);
    }

    private function mapDriver(array $driver): array
    {
        return [
            'id' => (int) $driver['id'],
            'numero_pilote' => (int) $driver['numero_pilote'],
            'prenom_pilote' => (string) $driver['prenom_pilote'],
            'nom_pilote' => (string) $driver['nom_pilote'],
            'nationalite_pilote' => (string) $driver['nationalite_pilote'],
            'photo_pilote' => (string) $driver['photo_pilote'],
            'engagements' => [],
        ];
    }

    private function mapEngagement(array $engagement): array
    {
        return [
            'id' => (int) $engagement['id'],
            'pilote_id' => (int) $engagement['pilote_id'],
            'saison_id' => (int) $engagement['saison_id'],
            'ecurie_id' => (int) $engagement['ecurie_id'],
            'role' => (string) $engagement['role'],
            'date_debut' => $this->nullableInt($engagement['date_debut']),
            'date_fin' => $this->nullableInt($engagement['date_fin']),
            'nombre_titre_pilote' => $this->nullableInt($engagement['nombre_titre_pilote']),
            'annee' => (int) $engagement['annee'],
            'nom_ecurie' => (string) $engagement['nom_ecurie'],
            'nom_court' => (string) $engagement['nom_court'],
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
