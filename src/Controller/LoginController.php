<?php

namespace App\Controller;

use App\View\ViewRenderer;

class LoginController
{
    public function index()
    {
        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/login', [
            'title' => 'BingoF1 - Connexion'
        ]);
    }
}