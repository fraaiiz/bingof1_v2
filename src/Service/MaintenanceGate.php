<?php

namespace App\Service;

use App\Database\Database;
use App\Repository\SiteSettingsRepository;
use PDOException;

class MaintenanceGate
{
    public static function enforce(): void
    {
        if (($_SESSION['user_role'] ?? '') === 'admin') {
            return;
        }

        try {
            $maintenance = (new SiteSettingsRepository((new Database())->getConnection()))->isMaintenance();
        } catch (PDOException $exception) {
            error_log('Unable to read maintenance setting: ' . $exception->getMessage());
            ErrorPage::unavailable();
        }

        if (!$maintenance || self::isAuthenticationRequest()) {
            return;
        }

        self::close();
    }

    private static function isAuthenticationRequest(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = is_string($path) ? $path : '/';

        return ($method === 'GET' && ($path === '/login' || $path === '/mot-de-passe-oublie'))
            || ($method === 'POST' && ($path === '/login' || $path === '/logout' || $path === '/mot-de-passe-oublie'))
            || (in_array($method, ['GET', 'POST'], true) && preg_match('#^/mot-de-passe-oublie/[a-f0-9]{64}$#', $path) === 1);
    }

    private static function close(): never
    {
        http_response_code(503);
        header('Retry-After: 3600');
        $isLoggedIn = !empty($_SESSION['user_id']);
        $csrfToken = Csrf::token();
        require __DIR__ . '/../View/pages/maintenance.php';
        exit();
    }
}
