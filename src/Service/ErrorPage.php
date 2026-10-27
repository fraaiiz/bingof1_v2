<?php

namespace App\Service;

class ErrorPage
{
    public static function notFound(): never
    {
        self::send(404, 'error-404.php');
    }

    public static function unavailable(): never
    {
        self::send(503, 'error-503.php', 3600);
    }

    public static function session(): never
    {
        self::send(503, 'error-session.php', 60);
    }

    private static function send(int $status, string $view, ?int $retryAfter = null): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store');
        if ($retryAfter !== null) {
            header('Retry-After: ' . $retryAfter);
        }

        require dirname(__DIR__) . '/View/pages/errors/' . $view;
        exit();
    }
}
