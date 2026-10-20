<?php

namespace App\Controller;

use App\View\ViewRenderer;

class AdminController
{
    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['auth_error'] = 'Connectez-vous pour accéder à l’administration.';
            header('Location: /login');
            exit();
        }

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/admin', [
            'title' => 'BingoF1 - Administration',
        ]);
    }
}
