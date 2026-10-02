<?php

namespace App\Controller;

use App\Database\Database;
use App\Service\Csrf;
use App\View\ViewRenderer;

class LoginController
{
    public function index(): void
    {
        $authError = $_SESSION['auth_error'] ?? null;
        unset($_SESSION['auth_error']);

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/login', [
            'title' => 'BingoF1 - Connexion',
            'authError' => $authError,
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

        try {
            $database = new Database();
            $pdo = $database->getConnection();
            $stmt = $pdo->prepare(
                'SELECT id, user_login, user_password, user_role
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
            $this->redirectWithError('Pseudo/email ou mot de passe incorrect.');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_login'] = $user['user_login'];
        $_SESSION['user_role'] = $user['user_role'];

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