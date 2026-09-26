<?php

namespace App\Controller;

use App\View\ViewRenderer;

class HomeController {

    public function index() {

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/home', [
            'title' => 'BingoF1 - Accueil'
        ]);
    }
}