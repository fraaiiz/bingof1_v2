<?php

namespace App\Controller;

use App\Database\Database;
use App\Service\Csrf;
use App\Service\PasswordRules;
use App\Service\RateLimiter;
use App\View\ViewRenderer;

class RegisterController
{
    public function index(): void
    {
        RateLimiter::denyWhenLimited('register', 5, 3600);
        $authError = $_SESSION['auth_error'] ?? null;
        unset($_SESSION['auth_error']);

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/register', [
            'title' => 'BingoF1 - Inscription',
            'authError' => $authError,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function store(): void
    {
        $login = $_POST['username'] ?? null;
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        $passwordConfirmation = $_POST['password_confirmation'] ?? null;

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)
            || !is_string($login)
            || !is_string($email)
            || !is_string($password)
            || !is_string($passwordConfirmation)) {
            $this->redirectWithError('Formulaire invalide.');
        }

        $login = trim($login);
        $email = trim($email);

        if (strlen($login) < 3 || strlen($login) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithError('Vérifiez le pseudo et l’adresse email.');
        }

        if (!PasswordRules::isComplex($password)) {
            $this->redirectWithError('Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.');
        }

        if ($password !== $passwordConfirmation) {
            $this->redirectWithError('Les deux mots de passe sont différents.');
        }

        try {
            $database = new Database();
            $pdo = $database->getConnection();
            $limiter = new RateLimiter($pdo);
            if ($limiter->tooMany('register', RateLimiter::clientIp(), 5, 3600)) {
                RateLimiter::deny('register', 3600);
            }
            $limiter->hit('register', RateLimiter::clientIp());
            $stmt = $pdo->prepare(
                'INSERT INTO users (user_login, user_email, user_password, user_role)
                VALUES (:login, :email, :password, \'user\')'
            );
            $stmt->execute([
                ':login' => $login,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $userId = (int) $pdo->lastInsertId();
        } catch (\PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $this->redirectWithError('Erreur au moment de l’inscription.');
            }
            $this->redirectWithError('Inscription indisponible. Réessayez plus tard.');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_login'] = $login;
        $_SESSION['user_role'] = 'user';
        $_SESSION['auth_version'] = 1;

        header('Location: /');
        exit();
    }

    private function redirectWithError(string $message): void
    {
        $_SESSION['auth_error'] = $message;
        header('Location: /register');
        exit();
    }
}