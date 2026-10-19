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
}
