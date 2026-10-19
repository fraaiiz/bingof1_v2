<?php

namespace App\View;

use App\Database\Database;
use App\Repository\SeasonRepository;

class ViewRenderer {
    public function render($view, $data = []) {
        $page = basename($view);
        extract($data);
        $availableSeasons = (new SeasonRepository((new Database())->getConnection()))->findAvailableYears();
        ob_start();
        require_once __DIR__ . "/../$view.php";
        $content = ob_get_clean();
        require_once __DIR__ . "/layouts/main.php";
    }
}
