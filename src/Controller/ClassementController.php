<?php

namespace App\Controller;

use App\View\ViewRenderer;

class ClassementController {

    public function index(string $annee) {
        
        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/classement', [
            'title' => 'BingoF1 - Classement ' . $annee,
            'annee' => $annee
        ]);
    }

    public function redirectToCurrentSeason() {
        $currentYear = date('Y');
        header("Location: /saisons/2026/classement");
        exit();
    }
}