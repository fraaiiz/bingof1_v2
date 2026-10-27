<?php

namespace App\Controller;

use App\Service\ErrorPage;
use App\Database\Database;
use App\Repository\PasswordResetRepository;
use App\Service\Csrf;
use App\Service\Mailer;
use App\Service\PasswordRules;
use App\Service\RateLimiter;
use App\View\ViewRenderer;
use PDOException;

class PasswordResetController
{
    public function requestForm(): void
    {
        RateLimiter::denyWhenLimited('password-reset', 5, 900);
        $notice = $_SESSION['password_reset_notice'] ?? null;
        unset($_SESSION['password_reset_notice']);

        (new ViewRenderer())->render('View/pages/password-request', [
            'title' => 'BingoF1 - Mot de passe oublié',
            'notice' => $notice,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function send(): void
    {
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectRequest('error', 'Requête invalide.');
        }

        $email = $_POST['email'] ?? null;
        if (!is_string($email) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false || mb_strlen(trim($email)) > 255) {
            $this->redirectRequest('error', 'L’adresse email est invalide.');
        }

        $email = trim($email);
        $baseUrl = $this->baseUrl();
        if ($baseUrl === null) {
            error_log('APP_URL is missing or invalid; password reset email was not sent.');
            $this->redirectRequest('error', 'L’email n’a pas pu être envoyé. Réessayez plus tard.');
        }

        try {
            $repository = $this->repository();
            $limiter = new RateLimiter((new Database())->getConnection());
            if ($limiter->tooMany('password-reset', RateLimiter::clientIp(), 5, 900)) {
                RateLimiter::deny('password-reset', 900);
            }
            $limiter->hit('password-reset', RateLimiter::clientIp());
            $userId = $repository->findUserIdByEmail($email);
            if ($userId !== null && !$repository->hasRecentRequest($userId)) {
                $token = bin2hex(random_bytes(32));
                $repository->create($userId, hash('sha256', $token));
                $sent = (new Mailer())->send(
                    $email,
                    'Réinitialisation de votre mot de passe BingoF1',
                    $this->messageBody($baseUrl, $token)
                );
                if (!$sent) {
                    error_log('Password reset email could not be sent.');
                    try {
                        $repository->deleteForUser($userId);
                    } catch (PDOException $exception) {
                        error_log('Unable to delete unused password reset: ' . $exception->getMessage());
                    }
                }
            }
        } catch (PDOException $exception) {
            error_log('Unable to create password reset: ' . $exception->getMessage());
            $this->redirectRequest('error', 'La demande est indisponible pour le moment.');
        }

        $this->redirectRequest('success', 'Si un compte correspond à cette adresse, un email vient d’être envoyé.');
    }

    public function chooseForm(string $token): void
    {
        if (!$this->validToken($token)) {
            ErrorPage::notFound();
        }

        try {
            if ($this->repository()->findValidUserId(hash('sha256', $token)) === null) {
                $this->renderChoose($token, ['type' => 'error', 'message' => 'Ce lien est invalide ou a expiré.']);
                return;
            }
        } catch (PDOException $exception) {
            error_log('Unable to read password reset: ' . $exception->getMessage());
            http_response_code(500);
            echo 'La réinitialisation est indisponible pour le moment.';
            return;
        }

        $notice = $_SESSION['password_reset_notice'] ?? null;
        unset($_SESSION['password_reset_notice']);
        $this->renderChoose($token, $notice);
    }

    public function update(string $token): void
    {
        if (!$this->validToken($token)) {
            ErrorPage::notFound();
        }

        $location = '/mot-de-passe-oublie/' . $token;
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectChoose($location, 'error', 'Requête invalide.');
        }

        $password = $_POST['password'] ?? null;
        $confirmation = $_POST['password_confirmation'] ?? null;
        if (!is_string($password) || !is_string($confirmation) || !PasswordRules::isComplex($password)) {
            $this->redirectChoose($location, 'error', 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.');
        }

        if ($password !== $confirmation) {
            $this->redirectChoose($location, 'error', 'Les deux mots de passe sont différents.');
        }

        try {
            $repository = $this->repository();
            $userId = $repository->findValidUserId(hash('sha256', $token));
            if ($userId === null) {
                $this->redirectChoose($location, 'error', 'Ce lien est invalide ou a expiré.');
            }
            $repository->updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
        } catch (PDOException $exception) {
            error_log('Unable to update password: ' . $exception->getMessage());
            $this->redirectChoose($location, 'error', 'Le mot de passe n’a pas pu être modifié.');
        }

        $_SESSION['auth_notice'] = 'Votre mot de passe a été modifié. Vous pouvez vous connecter.';
        header('Location: /login');
        exit();
    }

    private function messageBody(string $baseUrl, string $token): string
    {
        $link = $baseUrl . '/mot-de-passe-oublie/' . $token;

        return "Bonjour,\n\n"
            . "Pour choisir un nouveau mot de passe, ouvrez ce lien :\n"
            . $link . "\n\n"
            . "Ce lien est valable 1 heure. Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.\n\n"
            . "BingoF1";
    }

    private function baseUrl(): ?string
    {
        $url = getenv('APP_URL');
        if (!is_string($url) || trim($url) === '') {
            return null;
        }

        $parts = parse_url(rtrim(trim($url), '/'));
        if (!is_array($parts)) {
            return null;
        }

        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        if (
            !in_array($scheme, ['http', 'https'], true)
            || preg_match('/^[A-Za-z0-9.-]+$/', $host) !== 1
            || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])
            || ($path !== '' && $path !== '/')
        ) {
            return null;
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return $scheme . '://' . $host . $port;
    }

    private function validToken(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    private function renderChoose(string $token, ?array $notice): void
    {
        (new ViewRenderer())->render('View/pages/password-choose', [
            'title' => 'BingoF1 - Nouveau mot de passe',
            'token' => $token,
            'notice' => $notice,
            'csrfToken' => Csrf::token(),
        ]);
    }

    private function repository(): PasswordResetRepository
    {
        return new PasswordResetRepository((new Database())->getConnection());
    }

    private function redirectRequest(string $type, string $message): never
    {
        $_SESSION['password_reset_notice'] = ['type' => $type, 'message' => $message];
        header('Location: /mot-de-passe-oublie');
        exit();
    }

    private function redirectChoose(string $location, string $type, string $message): never
    {
        $_SESSION['password_reset_notice'] = ['type' => $type, 'message' => $message];
        header('Location: ' . $location);
        exit();
    }
}
