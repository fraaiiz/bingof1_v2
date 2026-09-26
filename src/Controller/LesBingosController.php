<?php

namespace App\Controller;

use App\View\ViewRenderer;

class LesBingosController {

    public function index() {
        
        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/les-bingos', [
            'title' => 'BingoF1 - Les Bingos'
        ]);
    }
}