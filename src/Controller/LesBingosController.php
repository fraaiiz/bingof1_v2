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
            $seasonId = $repository->findSeasonId((int) date('Y'));
            if ($seasonId === null) {
                http_response_code(404);
                echo 'La saison en cours n’est pas configurée.';
                return;
            }

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
        ]);
    }
}