<?php

namespace App\View;

class ViewRenderer {
    public function render($view, $data = []) {
        extract($data);
        ob_start();
        require_once __DIR__ . "/../$view.php";
        $content = ob_get_clean();
        require_once __DIR__ . "/layouts/main.php";
    }
}
