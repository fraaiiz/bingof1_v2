<?php

namespace App\Controller;

use App\Database\Database;
use App\Service\Csrf;
use App\View\ViewRenderer;

class RegisterController
{
    public function index(): void
    {
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

        if (strlen($password) < 8 || $password !== $passwordConfirmation) {
            $this->redirectWithError('Le mot de passe doit faire au moins 8 caractères et les deux saisies doivent correspondre.');
        }

        try {
            $database = new Database();
            $pdo = $database->getConnection();
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
                $this->redirectWithError('Ce pseudo ou cette adresse email est déjà utilisé.');
            }
            $this->redirectWithError('Inscription indisponible. Réessayez plus tard.');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_login'] = $login;
        $_SESSION['user_role'] = 'user';

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