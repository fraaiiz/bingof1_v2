<?php

namespace App\Controller;

use App\Database\Database;
use App\View\ViewRenderer;
use PDO;

class ClassementController
{
    private const RACE_POINTS = [
        1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10,
        6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1,
    ];

    private const SPRINT_POINTS = [
        1 => 8, 2 => 7, 3 => 6, 4 => 5, 5 => 4, 6 => 3, 7 => 2, 8 => 1,
    ];

    public function index(string $annee): void
    {
        $pdo = (new Database())->getConnection();
        $seasonStatement = $pdo->prepare('SELECT id FROM saisons WHERE annee = :annee');
        $seasonStatement->execute([':annee' => $annee]);
        $seasonId = $seasonStatement->fetchColumn();

        if ($seasonId === false) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $teamsStatement = $pdo->prepare(
            'SELECT e.id, e.nom_ecurie
            FROM ecuries_saisons es
            INNER JOIN ecuries e ON e.id = es.ecurie_id
            WHERE es.saison_id = :saison_id'
        );
        $teamsStatement->execute([':saison_id' => $seasonId]);
        $teams = [];
        foreach ($teamsStatement->fetchAll(PDO::FETCH_ASSOC) as $team) {
            $teamId = (int) $team['id'];
            $teams[$teamId] = [
                'id' => $teamId,
                'name' => (string) $team['nom_ecurie'],
                'points' => 0,
                'positions' => array_fill(1, 22, 0),
            ];
        }

        $driversStatement = $pdo->prepare(
            "SELECT p.id, p.prenom_pilote, p.nom_pilote, pe.ecurie_id, pe.role
            FROM pilotes_engagement pe
            INNER JOIN pilotes p ON p.id = pe.pilote_id
            WHERE pe.saison_id = :saison_id
            ORDER BY p.id ASC, CASE WHEN pe.role = 'titulaire' THEN 0 ELSE 1 END, pe.id ASC"
        );
        $driversStatement->execute([':saison_id' => $seasonId]);
        $drivers = [];
        foreach ($driversStatement->fetchAll(PDO::FETCH_ASSOC) as $driver) {
            $driverId = (int) $driver['id'];
            if (isset($drivers[$driverId])) {
                continue;
            }

            $drivers[$driverId] = [
                'id' => $driverId,
                'name' => trim($driver['prenom_pilote'] . ' ' . $driver['nom_pilote']),
                'team_id' => $driver['ecurie_id'] === null ? null : (int) $driver['ecurie_id'],
                'team_name' => $driver['ecurie_id'] === null
                    ? ''
                    : ($teams[(int) $driver['ecurie_id']]['name'] ?? ''),
                'role' => (string) $driver['role'],
                'points' => 0,
                'positions' => array_fill(1, 22, 0),
                'participated' => $driver['role'] === 'titulaire',
            ];
        }

        $resultsStatement = $pdo->prepare(
            "SELECT s.session_type, r.pilote_id, r.ecurie_id, r.position, r.dsq, r.np, c.half_points
            FROM courses c
            INNER JOIN sessions s ON s.format_id = c.format_id
            INNER JOIN resultats r ON r.course_id = c.id AND r.session_id = s.id
            WHERE c.id_saison = :saison_id
                AND c.is_cancelled = 'no'
                AND s.session_type IN ('course', 'sprint')
            ORDER BY c.num_manche ASC, s.session_order ASC, r.position ASC"
        );
        $resultsStatement->execute([':saison_id' => $seasonId]);

        foreach ($resultsStatement->fetchAll(PDO::FETCH_ASSOC) as $result) {
            $driverId = (int) $result['pilote_id'];
            if (!isset($drivers[$driverId])) {
                continue;
            }

            $drivers[$driverId]['participated'] = true;
            $position = (int) $result['position'];
            $isExcluded = !empty($result['dsq']) || !empty($result['np']);
            $teamId = $result['ecurie_id'] === null
                ? $drivers[$driverId]['team_id']
                : (int) $result['ecurie_id'];

            if ($result['session_type'] === 'course' && !$isExcluded && $position >= 1 && $position <= 22) {
                $drivers[$driverId]['positions'][$position]++;
                if ($teamId !== null && isset($teams[$teamId])) {
                    $teams[$teamId]['positions'][$position]++;
                }
            }

            if ($isExcluded) {
                continue;
            }

            $pointsByPosition = $result['session_type'] === 'sprint'
                ? self::SPRINT_POINTS
                : self::RACE_POINTS;
            $points = $pointsByPosition[$position] ?? 0;
            if (!empty($result['half_points'])) {
                $points /= 2;
            }
            $drivers[$driverId]['points'] += $points;

            if ($teamId !== null && isset($teams[$teamId])) {
                $teams[$teamId]['points'] += $points;
            }
        }

        $drivers = array_values(array_filter(
            $drivers,
            static fn (array $driver): bool => $driver['participated']
        ));
        $drivers = $this->rankStandings($drivers);
        $teams = $this->rankStandings(array_values($teams));

        (new ViewRenderer())->render('View/pages/classement', [
            'title' => 'BingoF1 - Classement ' . $annee,
            'annee' => $annee,
            'pilotes' => $drivers,
            'equipes' => $teams,
        ]);
    }

    private function rankStandings(array $standings): array
    {
        usort($standings, function (array $first, array $second): int {
            $comparison = $second['points'] <=> $first['points'];
            if ($comparison !== 0) {
                return $comparison;
            }

            for ($position = 1; $position <= 22; $position++) {
                $comparison = $second['positions'][$position] <=> $first['positions'][$position];
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return $first['id'] <=> $second['id'];
        });

        $previous = null;
        $rank = 0;
        foreach ($standings as $index => &$standing) {
            if ($previous === null || !$this->hasSameClassification($previous, $standing)) {
                $rank = $index + 1;
            }
            $standing['rank'] = $rank;
            $previous = $standing;
        }
        unset($standing);

        return $standings;
    }

    private function hasSameClassification(array $first, array $second): bool
    {
        if (($first['points'] <=> $second['points']) !== 0) {
            return false;
        }

        for ($position = 1; $position <= 22; $position++) {
            if ($first['positions'][$position] !== $second['positions'][$position]) {
                return false;
            }
        }

        return true;
    }

    public function redirectToCurrentSeason(): void
    {
        $currentYear = date('Y');
        header('Location: /saisons/' . rawurlencode($currentYear) . '/classement');
        exit();
    }
}
