<?php

namespace App\Controller;

use App\View\ViewRenderer;

class InfosController {

    public function index(string $annee) {
        
        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/infos', [
            'title' => 'BingoF1 - Infos ' . $annee,
            'annee' => $annee
        ]);
    }

    public function redirectToCurrentSeason() {
        $currentYear = date('Y');
        header("Location: /saisons/2026/infos");
        exit();
    }
}