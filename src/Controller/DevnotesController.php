<?php

namespace App\Controller;

use App\View\ViewRenderer;

class DevnotesController
{
    public function index(): void
    {
        (new ViewRenderer())->render('View/pages/devnotes', [
            'title' => 'BingoF1 - Devnotes',
        ]);
    }
}
