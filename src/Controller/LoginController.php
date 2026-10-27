<?php

namespace App\Controller;

use App\Database\Database;
use App\Service\Csrf;
use App\Service\RateLimiter;
use App\View\ViewRenderer;

class LoginController
{
    public function index(): void
    {
        RateLimiter::denyWhenLimited('login-account', 5, 900);
        RateLimiter::denyWhenLimited('login-ip', 8, 900);
        $authError = $_SESSION['auth_error'] ?? null;
        $authNotice = $_SESSION['auth_notice'] ?? null;
        unset($_SESSION['auth_error'], $_SESSION['auth_notice']);

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/login', [
            'title' => 'BingoF1 - Connexion',
            'authError' => $authError,
            'authNotice' => $authNotice,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function authenticate(): void
    {
        $identifier = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)
            || !is_string($identifier)
            || !is_string($password)
            || trim($identifier) === ''
            || $password === '') {
            $this->redirectWithError('Identifiants invalides.');
        }

        $identifier = trim($identifier);
        $attemptKey = mb_strtolower($identifier);
        $ip = RateLimiter::clientIp();

        try {
            $database = new Database();
            $pdo = $database->getConnection();
            $limiter = new RateLimiter($pdo);
            if ($limiter->tooMany('login-ip', $ip, 8, 900)) {
                RateLimiter::deny('login-ip', 900);
            }
            if ($limiter->tooMany('login-account', $attemptKey, 5, 900)) {
                RateLimiter::deny('login-account', 900);
            }
            $stmt = $pdo->prepare(
                'SELECT id, user_login, user_password, user_role, auth_version
                FROM users
                WHERE user_login = :login OR user_email = :email
                LIMIT 1'
            );
            $stmt->execute([
                ':login' => trim($identifier),
                ':email' => trim($identifier),
            ]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $exception) {
            $this->redirectWithError('Connexion indisponible. Réessayez plus tard.');
        }

        if (!$user || !password_verify($password, $user['user_password'])) {
            try {
                $limiter->hit('login-ip', $ip);
                $limiter->hit('login-account', $attemptKey);
                if ($limiter->tooMany('login-account', $attemptKey, 5, 900)) {
                    RateLimiter::deny('login-account', 900);
                }
                if ($limiter->tooMany('login-ip', $ip, 8, 900)) {
                    RateLimiter::deny('login-ip', 900);
                }
            } catch (\PDOException $exception) {
                $this->redirectWithError('Connexion indisponible. Réessayez plus tard.');
            }
            $this->redirectWithError('Pseudo/email ou mot de passe incorrect.');
        }

        try {
            $limiter->clear('login-account', $attemptKey);
        } catch (\PDOException $exception) {
            error_log('Unable to clear login attempts: ' . $exception->getMessage());
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_login'] = $user['user_login'];
        $_SESSION['user_role'] = $user['user_role'];
        $_SESSION['auth_version'] = (int) $user['auth_version'];

        header('Location: /');
        exit();
    }

    public function logout(): void
    {
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Requête invalide.';
            return;
        }

        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: /login');
        exit();
    }

    private function redirectWithError(string $message): void
    {
        $_SESSION['auth_error'] = $message;
        header('Location: /login');
        exit();
    }
}