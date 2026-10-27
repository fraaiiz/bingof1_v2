<?php

namespace App\Repository;

use PDO;

class BingoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findSeasonId(int $year): ?int
    {
        $statement = $this->pdo->prepare('SELECT id FROM saisons WHERE annee = :annee');
        $statement->execute([':annee' => $year]);
        $seasonId = $statement->fetchColumn();

        return $seasonId === false ? null : (int) $seasonId;
    }

    public function findUserGrid(int $userId, int $seasonId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT bc.position, bc.label, bc.validated
            FROM bingos b
            INNER JOIN bingo_cases bc ON bc.bingo_id = b.id
            WHERE b.user_id = :user_id AND b.saison_id = :season_id
            ORDER BY bc.position ASC'
        );
        $statement->execute([':user_id' => $userId, ':season_id' => $seasonId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $rows === [] ? null : $this->indexCells($rows);
    }

    public function findPublicGrids(int $seasonId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT b.id, b.user_id, u.user_login, bc.position, bc.label, bc.validated
            FROM bingos b
            INNER JOIN users u ON u.id = b.user_id
            LEFT JOIN bingo_cases bc ON bc.bingo_id = b.id
            WHERE b.saison_id = :season_id
            ORDER BY u.user_login ASC, bc.position ASC'
        );
        $statement->execute([':season_id' => $seasonId]);

        $grids = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $bingoId = (int) $row['id'];
            if (!isset($grids[$bingoId])) {
                $grids[$bingoId] = [
                    'id' => $bingoId,
                    'user_id' => (int) $row['user_id'],
                    'user_login' => (string) $row['user_login'],
                    'cells' => $this->emptyCells(),
                ];
            }

            if ($row['position'] !== null) {
                $position = (int) $row['position'];
                $grids[$bingoId]['cells'][$position] = [
                    'label' => (string) $row['label'],
                    'validated' => (bool) $row['validated'],
                ];
            }
        }

        return array_values($grids);
    }

    public function saveGrid(int $userId, int $seasonId, array $cells): void
    {
        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'SELECT id FROM bingos WHERE user_id = :user_id AND saison_id = :season_id FOR UPDATE'
            );
            $statement->execute([':user_id' => $userId, ':season_id' => $seasonId]);
            $bingoId = $statement->fetchColumn();

            if ($bingoId === false) {
                $statement = $this->pdo->prepare(
                    'INSERT INTO bingos (user_id, saison_id) VALUES (:user_id, :season_id)'
                );
                $statement->execute([':user_id' => $userId, ':season_id' => $seasonId]);
                $bingoId = (int) $this->pdo->lastInsertId();
            } else {
                $bingoId = (int) $bingoId;
            }

            $upsert = $this->pdo->prepare(
                'INSERT INTO bingo_cases (bingo_id, position, label, validated)
                VALUES (:bingo_id, :position, :label, :validated)
                ON DUPLICATE KEY UPDATE label = VALUES(label), validated = VALUES(validated)'
            );
            foreach ($cells as $position => $cell) {
                $upsert->execute([
                    ':bingo_id' => $bingoId,
                    ':position' => $position,
                    ':label' => $cell['label'],
                    ':validated' => $cell['validated'] ? 1 : 0,
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function emptyCells(): array
    {
        $cells = [];
        for ($position = 1; $position <= 25; $position++) {
            $cells[$position] = ['label' => '', 'validated' => false];
        }

        return $cells;
    }

    private function indexCells(array $rows): array
    {
        $cells = $this->emptyCells();
        foreach ($rows as $row) {
            $position = (int) $row['position'];
            if ($position >= 1 && $position <= 25) {
                $cells[$position] = [
                    'label' => (string) $row['label'],
                    'validated' => (bool) $row['validated'],
                ];
            }
        }

        return $cells;
    }
}
