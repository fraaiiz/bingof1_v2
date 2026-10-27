<?php

namespace App\Service;

use App\Database\Database;
use App\Repository\UserRepository;
use PDOException;

class SessionGuard
{
    private const ROLES = ['user', 'editor', 'admin'];

    public static function enforce(): void
    {
        $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($userId === false) {
            return;
        }

        try {
            $user = (new UserRepository((new Database())->getConnection()))->findSessionState($userId);
        } catch (PDOException $exception) {
            error_log('Unable to verify the user session: ' . $exception->getMessage());
            ErrorPage::session();
        }

        $sessionVersion = filter_var($_SESSION['auth_version'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $role = is_array($user) ? $user['user_role'] : null;
        if (
            !is_array($user)
            || $sessionVersion === false
            || $sessionVersion !== $user['auth_version']
            || !is_string($role)
            || !in_array($role, self::ROLES, true)
        ) {
            self::invalidate();
            return;
        }

        $_SESSION['user_role'] = $role;
    }

    private static function invalidate(): void
    {
        $preserved = [];
        if (isset($_SESSION['auth_notice']) && (is_string($_SESSION['auth_notice']) || is_array($_SESSION['auth_notice']))) {
            $preserved['auth_notice'] = $_SESSION['auth_notice'];
        }
        if (isset($_SESSION['auth_error']) && is_string($_SESSION['auth_error'])) {
            $preserved['auth_error'] = $_SESSION['auth_error'];
        }
        if (isset($_SESSION['password_reset_notice']) && is_array($_SESSION['password_reset_notice'])) {
            $preserved['password_reset_notice'] = $_SESSION['password_reset_notice'];
        }

        $_SESSION = $preserved;
        session_regenerate_id(true);
    }
}
