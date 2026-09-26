<?php

namespace App\Controller;

use App\View\ViewRenderer;

class CalendrierController {

    public function index(string $annee) {
        
        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/calendrier', [
            'title' => 'BingoF1 - Calendrier ' . $annee,
            'annee' => $annee
        ]);
    }

    public function redirectToCurrentSeason() {
        $currentYear = date('Y');
        header("Location: /saisons/2026/calendrier");
        exit();
    }
}