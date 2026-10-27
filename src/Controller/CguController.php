<?php

namespace App\Controller;

use App\View\ViewRenderer;

class CguController
{
    public function index(): void
    {
        (new ViewRenderer())->render('View/pages/cgu', [
            'title' => 'BingoF1 - Conditions générales d’utilisation',
            'hostName' => 'IONOS France',
            'hostAddress' => '7 Place de la Gare, 57200 Sarreguemines',
            'hostPhone' => '0 805 54 21 80',
        ]);
    }
}
