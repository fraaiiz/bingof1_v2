<?php

namespace App\Controller;

use App\View\ViewRenderer;

class MonBingoController {

    public function index() {
        
        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/mon-bingo', [
            'title' => 'BingoF1 - Mon Bingo'
        ]);
    }
}