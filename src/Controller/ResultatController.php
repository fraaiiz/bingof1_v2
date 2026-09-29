<?php

namespace App\Controller;

use App\View\ViewRenderer;

class ResultatController {

    public function index() {
        
        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/saisons/{$annee}/{$course}', [
            'title' => 'BingoF1 - Résultat de {$course} {$année}'
        ]);
    }
}