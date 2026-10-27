<?php

namespace App\Repository;

use PDO;

class TeamRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, nom_ecurie, nom_court, nationalite_ecurie, annee_creation
            FROM ecuries
            ORDER BY nom_court ASC, id ASC'
        );
        $teams = array_map($this->mapTeam(...), $statement->fetchAll(PDO::FETCH_ASSOC));
        if ($teams === []) {
            return [];
        }

        $entries = $this->pdo->query(
            'SELECT es.ecurie_id, s.annee, m.nom_motoriste, es.logo
            FROM ecuries_saisons es
            INNER JOIN saisons s ON s.id = es.saison_id
            INNER JOIN motoristes m ON m.id = es.motoriste_id
            ORDER BY s.annee DESC, m.nom_motoriste ASC, es.id ASC'
        );
        $byTeam = [];
        foreach ($entries->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $byTeam[(int) $row['ecurie_id']][] = [
                'annee' => (int) $row['annee'],
                'nom_motoriste' => (string) $row['nom_motoriste'],
                'logo' => $row['logo'] === null || $row['logo'] === '' ? null : (string) $row['logo'],
            ];
        }

        foreach ($teams as $index => $team) {
            $teams[$index]['inscriptions'] = $byTeam[$team['id']] ?? [];
        }

        return $teams;
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nom_ecurie, nom_court, nationalite_ecurie, annee_creation
            FROM ecuries
            WHERE id = :id'
        );
        $statement->execute([':id' => $id]);
        $team = $statement->fetch(PDO::FETCH_ASSOC);

        return $team === false ? null : $this->mapTeam($team);
    }

    public function findEntries(int $teamId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT es.id, es.ecurie_id, es.saison_id, es.motoriste_id, es.logo, es.voiture, es.nombre_titre,
                s.annee, m.nom_motoriste, m.pays_motoriste
            FROM ecuries_saisons es
            INNER JOIN saisons s ON s.id = es.saison_id
            INNER JOIN motoristes m ON m.id = es.motoriste_id
            WHERE es.ecurie_id = :ecurie_id
            ORDER BY s.annee DESC, es.id ASC'
        );
        $statement->execute([':ecurie_id' => $teamId]);

        return array_map($this->mapEntry(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findEntry(int $teamId, int $entryId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT es.id, es.ecurie_id, es.saison_id, es.motoriste_id, es.logo, es.voiture, es.nombre_titre,
                s.annee, m.nom_motoriste, m.pays_motoriste
            FROM ecuries_saisons es
            INNER JOIN saisons s ON s.id = es.saison_id
            INNER JOIN motoristes m ON m.id = es.motoriste_id
            WHERE es.id = :id AND es.ecurie_id = :ecurie_id'
        );
        $statement->execute([':id' => $entryId, ':ecurie_id' => $teamId]);
        $entry = $statement->fetch(PDO::FETCH_ASSOC);

        return $entry === false ? null : $this->mapEntry($entry);
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

    public function findMotoristes(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, nom_motoriste, pays_motoriste
            FROM motoristes
            ORDER BY nom_motoriste ASC, id ASC'
        );

        return array_map(static function (array $engine): array {
            return [
                'id' => (int) $engine['id'],
                'nom_motoriste' => (string) $engine['nom_motoriste'],
                'pays_motoriste' => (string) $engine['pays_motoriste'],
            ];
        }, $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findMotoriste(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nom_motoriste, pays_motoriste FROM motoristes WHERE id = :id'
        );
        $statement->execute([':id' => $id]);
        $engine = $statement->fetch(PDO::FETCH_ASSOC);

        return $engine === false ? null : [
            'id' => (int) $engine['id'],
            'nom_motoriste' => (string) $engine['nom_motoriste'],
            'pays_motoriste' => (string) $engine['pays_motoriste'],
        ];
    }

    public function entryExists(int $teamId, int $seasonId, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM ecuries_saisons WHERE ecurie_id = :ecurie_id AND saison_id = :saison_id';
        $parameters = [
            ':ecurie_id' => $teamId,
            ':saison_id' => $seasonId,
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
                (SELECT COUNT(*) FROM ecuries_saisons es WHERE es.ecurie_id = :id) AS seasons_count,
                (SELECT COUNT(*) FROM pilotes_engagement pe WHERE pe.ecurie_id = :id) AS drivers_count,
                (SELECT COUNT(*) FROM resultats r WHERE r.ecurie_id = :id) AS results_count'
        );
        $statement->execute([':id' => $id]);
        $usage = $statement->fetch(PDO::FETCH_ASSOC);

        return [
            'seasons_count' => (int) ($usage['seasons_count'] ?? 0),
            'drivers_count' => (int) ($usage['drivers_count'] ?? 0),
            'results_count' => (int) ($usage['results_count'] ?? 0),
        ];
    }

    public function entryUsage(int $teamId, int $seasonId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM pilotes_engagement pe
                    WHERE pe.ecurie_id = :ecurie_id AND pe.saison_id = :saison_id) AS drivers_count,
                (SELECT COUNT(*) FROM resultats r
                    INNER JOIN courses c ON c.id = r.course_id
                    WHERE r.ecurie_id = :ecurie_id AND c.id_saison = :saison_id) AS results_count'
        );
        $statement->execute([
            ':ecurie_id' => $teamId,
            ':saison_id' => $seasonId,
        ]);
        $usage = $statement->fetch(PDO::FETCH_ASSOC);

        return [
            'drivers_count' => (int) ($usage['drivers_count'] ?? 0),
            'results_count' => (int) ($usage['results_count'] ?? 0),
        ];
    }

    public function create(array $team): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO ecuries (nom_ecurie, nom_court, nationalite_ecurie, annee_creation)
            VALUES (:nom, :nom_court, :nationalite, :annee_creation)'
        );
        $statement->execute([
            ':nom' => $team['nom_ecurie'],
            ':nom_court' => $team['nom_court'],
            ':nationalite' => $team['nationalite_ecurie'],
            ':annee_creation' => $team['annee_creation'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $team): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE ecuries
            SET nom_ecurie = :nom,
                nom_court = :nom_court,
                nationalite_ecurie = :nationalite,
                annee_creation = :annee_creation
            WHERE id = :id'
        );
        $statement->execute([
            ':nom' => $team['nom_ecurie'],
            ':nom_court' => $team['nom_court'],
            ':nationalite' => $team['nationalite_ecurie'],
            ':annee_creation' => $team['annee_creation'],
            ':id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM ecuries WHERE id = :id');
        $statement->execute([':id' => $id]);
    }

    public function createEntry(int $teamId, array $entry): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO ecuries_saisons (ecurie_id, saison_id, motoriste_id, logo, voiture, nombre_titre)
            VALUES (:ecurie_id, :saison_id, :motoriste_id, :logo, :voiture, :nombre_titre)'
        );
        $statement->execute([
            ':ecurie_id' => $teamId,
            ':saison_id' => $entry['saison_id'],
            ':motoriste_id' => $entry['motoriste_id'],
            ':logo' => $entry['logo'],
            ':voiture' => $entry['voiture'],
            ':nombre_titre' => $entry['nombre_titre'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateEntry(int $teamId, int $entryId, array $entry): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE ecuries_saisons
            SET saison_id = :saison_id,
                motoriste_id = :motoriste_id,
                logo = :logo,
                voiture = :voiture,
                nombre_titre = :nombre_titre
            WHERE id = :id AND ecurie_id = :ecurie_id'
        );
        $statement->execute([
            ':saison_id' => $entry['saison_id'],
            ':motoriste_id' => $entry['motoriste_id'],
            ':logo' => $entry['logo'],
            ':voiture' => $entry['voiture'],
            ':nombre_titre' => $entry['nombre_titre'],
            ':id' => $entryId,
            ':ecurie_id' => $teamId,
        ]);
    }

    public function deleteEntry(int $teamId, int $entryId): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM ecuries_saisons WHERE id = :id AND ecurie_id = :ecurie_id'
        );
        $statement->execute([':id' => $entryId, ':ecurie_id' => $teamId]);
    }

    private function mapTeam(array $team): array
    {
        return [
            'id' => (int) $team['id'],
            'nom_ecurie' => (string) $team['nom_ecurie'],
            'nom_court' => (string) $team['nom_court'],
            'nationalite_ecurie' => (string) $team['nationalite_ecurie'],
            'annee_creation' => (int) $team['annee_creation'],
            'inscriptions' => [],
        ];
    }

    private function mapEntry(array $entry): array
    {
        return [
            'id' => (int) $entry['id'],
            'ecurie_id' => (int) $entry['ecurie_id'],
            'saison_id' => (int) $entry['saison_id'],
            'motoriste_id' => (int) $entry['motoriste_id'],
            'logo' => $entry['logo'] === null || $entry['logo'] === '' ? null : (string) $entry['logo'],
            'voiture' => $entry['voiture'] === null || $entry['voiture'] === '' ? null : (string) $entry['voiture'],
            'nombre_titre' => $entry['nombre_titre'] === null || $entry['nombre_titre'] === ''
                ? null
                : (int) $entry['nombre_titre'],
            'annee' => (int) $entry['annee'],
            'nom_motoriste' => (string) $entry['nom_motoriste'],
            'pays_motoriste' => (string) $entry['pays_motoriste'],
        ];
    }
}
