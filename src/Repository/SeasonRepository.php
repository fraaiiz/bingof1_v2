<?php

namespace App\Repository;

use PDO;

class SeasonRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAvailableYears(): array
    {
        $statement = $this->pdo->query('SELECT annee FROM saisons ORDER BY annee DESC');

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function findDefaultYear(?\DateTimeImmutable $today = null): ?int
    {
        $statement = $this->pdo->query(
            "SELECT s.annee, MIN(c.date_fp1) AS season_start, MAX(c.date_course) AS season_end
            FROM saisons s
            LEFT JOIN courses c ON c.id_saison = s.id AND c.is_cancelled = 'no'
            GROUP BY s.id, s.annee
            ORDER BY s.annee DESC"
        );
        $seasons = $statement->fetchAll(PDO::FETCH_ASSOC);
        if ($seasons === []) {
            return null;
        }

        $today ??= new \DateTimeImmutable('today');
        $today = $today->setTime(0, 0);
        $mostRecentYear = (int) $seasons[0]['annee'];
        $nextSeasonYear = null;
        $nextSeasonStart = null;
        $latestEndedYear = null;
        $latestSeasonEnd = null;

        foreach ($seasons as $season) {
            if ($season['season_start'] === null || $season['season_end'] === null) {
                continue;
            }

            $seasonStart = (new \DateTimeImmutable($season['season_start']))->setTime(0, 0);
            $seasonEnd = (new \DateTimeImmutable($season['season_end']))->setTime(0, 0);
            $seasonYear = (int) $season['annee'];

            if ($today >= $seasonStart && $today <= $seasonEnd) {
                return $seasonYear;
            }

            if ($seasonStart > $today
                && ($nextSeasonStart === null || $seasonStart < $nextSeasonStart)) {
                $nextSeasonYear = $seasonYear;
                $nextSeasonStart = $seasonStart;
            }

            if ($seasonEnd < $today
                && ($latestSeasonEnd === null || $seasonEnd > $latestSeasonEnd)) {
                $latestEndedYear = $seasonYear;
                $latestSeasonEnd = $seasonEnd;
            }
        }

        return $nextSeasonYear ?? $latestEndedYear ?? $mostRecentYear;
    }

    public function findAllWithUsage(): array
    {
        $statement = $this->pdo->query(
            'SELECT s.id, s.annee,
                (SELECT COUNT(*) FROM courses c WHERE c.id_saison = s.id) AS courses_count,
                (SELECT COUNT(*) FROM pilotes_engagement pe WHERE pe.saison_id = s.id) AS drivers_count,
                (SELECT COUNT(*) FROM ecuries_saisons es WHERE es.saison_id = s.id) AS teams_count,
                (SELECT COUNT(*) FROM bingos b WHERE b.saison_id = s.id) AS bingos_count
            FROM saisons s
            ORDER BY s.annee DESC'
        );

        return array_map([$this, 'mapSeason'], $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT s.id, s.annee,
                (SELECT COUNT(*) FROM courses c WHERE c.id_saison = s.id) AS courses_count,
                (SELECT COUNT(*) FROM pilotes_engagement pe WHERE pe.saison_id = s.id) AS drivers_count,
                (SELECT COUNT(*) FROM ecuries_saisons es WHERE es.saison_id = s.id) AS teams_count,
                (SELECT COUNT(*) FROM bingos b WHERE b.saison_id = s.id) AS bingos_count
            FROM saisons s
            WHERE s.id = :id'
        );
        $statement->execute([':id' => $id]);
        $season = $statement->fetch(PDO::FETCH_ASSOC);

        return $season === false ? null : $this->mapSeason($season);
    }

    public function yearTaken(int $year, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM saisons WHERE annee = :annee';
        $parameters = [':annee' => $year];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $parameters[':id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function create(int $year): void
    {
        $statement = $this->pdo->prepare('INSERT INTO saisons (annee) VALUES (:annee)');
        $statement->execute([':annee' => $year]);
    }

    public function updateYear(int $id, int $year): void
    {
        $statement = $this->pdo->prepare('UPDATE saisons SET annee = :annee WHERE id = :id');
        $statement->execute([':annee' => $year, ':id' => $id]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM saisons WHERE id = :id');
        $statement->execute([':id' => $id]);
    }

    private function mapSeason(array $season): array
    {
        return [
            'id' => (int) $season['id'],
            'annee' => (int) $season['annee'],
            'courses_count' => (int) $season['courses_count'],
            'drivers_count' => (int) $season['drivers_count'],
            'teams_count' => (int) $season['teams_count'],
            'bingos_count' => (int) $season['bingos_count'],
        ];
    }
}
