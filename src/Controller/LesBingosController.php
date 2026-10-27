<?php

namespace App\Controller;

use App\Database\Database;
use App\Repository\BingoRepository;
use App\View\ViewRenderer;
use PDOException;

class LesBingosController
{
    public function index(): void
    {
        try {
            $repository = new BingoRepository((new Database())->getConnection());
            $year = (int) date('Y');
            $requestedYear = $_GET['annee'] ?? null;
            if (is_string($requestedYear) && preg_match('/^\d{4}$/', $requestedYear) === 1) {
                $year = (int) $requestedYear;
            }
            $seasonId = $repository->findSeasonId($year);
            if ($seasonId === null) {
                http_response_code(404);
                echo 'La saison en cours n’est pas configurée.';
                return;
            }

            $requestedUserId = filter_var($_GET['user'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $grids = $repository->findPublicGrids($seasonId);
        } catch (PDOException $exception) {
            error_log('Unable to load public bingo grids: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger les grilles de bingo pour le moment.';
            return;
        }

        (new ViewRenderer())->render('View/pages/les-bingos', [
            'title' => 'BingoF1 - Les Bingos',
            'grids' => $grids,
            'year' => $year,
            'requestedUserId' => $requestedUserId === false ? null : $requestedUserId,
        ]);
    }
}