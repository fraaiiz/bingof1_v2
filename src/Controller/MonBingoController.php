<?php

namespace App\Controller;

use App\Database\Database;
use App\Repository\BingoRepository;
use App\Service\Csrf;
use App\View\ViewRenderer;
use PDOException;

class MonBingoController
{
    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['auth_error'] = 'Connectez-vous pour créer votre grille de bingo.';
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        $notice = $_SESSION['bingo_notice'] ?? null;
        unset($_SESSION['bingo_notice']);

        try {
            $repository = new BingoRepository((new Database())->getConnection());
            $seasonId = $repository->findSeasonId((int) date('Y'));
            if ($seasonId === null) {
                http_response_code(404);
                echo 'La saison en cours n’est pas configurée.';
                return;
            }

            $cells = $repository->findUserGrid((int) $_SESSION['user_id'], $seasonId)
                ?? $repository->emptyCells();
        } catch (PDOException $exception) {
            error_log('Unable to load bingo grid: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger votre grille de bingo pour le moment.';
            return;
        }

        (new ViewRenderer())->render('View/pages/mon-bingo', [
            'title' => 'BingoF1 - Mon Bingo',
            'cells' => $cells,
            'csrfToken' => Csrf::token(),
            'notice' => $notice,
        ]);
    }

    private function store(): void
    {
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Requête invalide.';
            return;
        }

        $postedCells = $_POST['cells'] ?? null;
        if (!is_array($postedCells) || count($postedCells) !== 25) {
            http_response_code(422);
            echo 'La grille doit contenir 25 cases.';
            return;
        }

        $cells = [];
        for ($position = 1; $position <= 25; $position++) {
            $postedCell = $postedCells[$position] ?? $postedCells[(string) $position] ?? null;
            if (!is_array($postedCell)
                || !is_string($postedCell['label'] ?? null)
                || !is_string($postedCell['validated'] ?? null)
                || !in_array($postedCell['validated'], ['0', '1'], true)) {
                http_response_code(422);
                echo 'Les données de la grille sont invalides.';
                return;
            }

            $label = trim($postedCell['label']);
            if (mb_strlen($label) > 255) {
                http_response_code(422);
                echo 'Chaque case est limitée à 255 caractères.';
                return;
            }

            $cells[$position] = [
                'label' => $label,
                'validated' => $postedCell['validated'] === '1',
            ];
        }

        try {
            $repository = new BingoRepository((new Database())->getConnection());
            $seasonId = $repository->findSeasonId((int) date('Y'));
            if ($seasonId === null) {
                http_response_code(404);
                echo 'La saison en cours n’est pas configurée.';
                return;
            }

            $repository->saveGrid((int) $_SESSION['user_id'], $seasonId, $cells);
        } catch (PDOException $exception) {
            error_log('Unable to save bingo grid: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible d’enregistrer votre grille pour le moment.';
            return;
        }

        $_SESSION['bingo_notice'] = [
            'type' => 'success',
            'message' => 'Votre grille a été enregistrée.',
        ];
        header('Location: /mon-bingo');
        exit();
    }
}