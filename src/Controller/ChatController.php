<?php

namespace App\Controller;

use App\Database\Database;
use App\Repository\ChatRepository;
use App\Service\Csrf;
use PDOException;

class ChatController
{
    private const MAX_LENGTH = 300;

    private const BAD_WORDS = [
        'con', 'connard', 'connasse', 'idiot', 'idiote', 'abruti', 'abrutie', 'debile', 'débile',
        'imbecile', 'imbécile', 'stupide', 'nul', 'nulle', 'fdp', 'encule', 'enculé', 'enculee', 'enculée',
        'salope', 'pute', 'batard', 'bâtard', 'ta gueule', 'ferme ta gueule', 'tg', 'ntm',
        'stupid', 'dumb', 'moron', 'loser', 'asshole', 'bastard', 'bitch', 'slut', 'fuck', 'fucking', 'shit', 'shitty', 'wtf', 'stfu',
    ];

    public function index(): void
    {
        try {
            $messages = $this->repository()->findVisible();
        } catch (PDOException $exception) {
            $this->respond(false, [], 'Erreur BDD.');
            return;
        }

        $this->respond(true, $messages);
    }

    public function store(): void
    {
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->respond(false, [], 'Requête invalide.');
            return;
        }

        try {
            $repository = $this->repository();
        } catch (PDOException $exception) {
            $this->respond(false, [], 'Erreur BDD.');
            return;
        }

        if (isset($_POST['delete_id'])) {
            $this->delete($repository);
            return;
        }

        $this->publish($repository);
    }

    private function delete(ChatRepository $repository): void
    {
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->respond(false, [], 'Non autorisé.');
            return;
        }

        if (!$this->canModerate()) {
            $this->respond(false, [], 'Permission refusée.');
            return;
        }

        $messageId = filter_var($_POST['delete_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($messageId === false) {
            $this->respond(false, [], 'Message introuvable.');
            return;
        }

        try {
            $deleted = $repository->softDelete($messageId, $userId);
        } catch (PDOException $exception) {
            $this->respond(false, [], 'Erreur BDD.');
            return;
        }

        if (!$deleted) {
            $this->respond(false, [], 'Message introuvable.');
            return;
        }

        $this->respond(true);
    }

    private function publish(ChatRepository $repository): void
    {
        $userId = $this->currentUserId();
        if ($userId === null) {
            $this->respond(false, [], 'Vous devez être connecté pour poster un message.');
            return;
        }

        $message = $_POST['message'] ?? null;
        if (!is_string($message)) {
            $this->respond(false, [], 'Message vide');
            return;
        }

        $message = trim($message);
        if ($message === '') {
            $this->respond(false, [], 'Message vide');
            return;
        }

        $isAdminMessage = str_starts_with($message, '/admin ');
        if ($isAdminMessage) {
            if (!$this->isAdmin()) {
                $this->respond(false, [], 'Commande réservée aux admins.');
                return;
            }

            $message = trim(substr($message, 7));
            if ($message === '') {
                $this->respond(false, [], 'Message vide');
                return;
            }
        }

        if (mb_strlen($message) > self::MAX_LENGTH) {
            $this->respond(false, [], 'Message trop long (300 caractères max).');
            return;
        }

        if (!$isAdminMessage && isset($_SESSION['last_message_time']) && time() - (int) $_SESSION['last_message_time'] < 5) {
            $this->respond(false, [], 'Vous envoyez des messages trop rapidement.');
            return;
        }

        if (!$isAdminMessage && $this->containsBadWord($message)) {
            $this->respond(false, [], 'Message refusé : contenu inapproprié.');
            return;
        }

        if (!$isAdminMessage && preg_match('/(.)\1{5,}/u', $message) === 1) {
            $this->respond(false, [], 'Message suspect.');
            return;
        }

        try {
            $repository->insert($userId, $message, $isAdminMessage);
        } catch (PDOException $exception) {
            $this->respond(false, [], 'Erreur BDD.');
            return;
        }

        $_SESSION['last_message_time'] = time();
        $this->respond(true);
    }

    private function repository(): ChatRepository
    {
        return new ChatRepository((new Database())->getConnection());
    }

    private function currentUserId(): ?int
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return (int) $_SESSION['user_id'];
    }

    private function canModerate(): bool
    {
        return in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'], true);
    }

    private function isAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? '') === 'admin';
    }

    private function containsBadWord(string $message): bool
    {
        foreach (self::BAD_WORDS as $word) {
            $pattern = '/(?<![\p{L}\p{N}_])' . preg_quote($word, '/') . '(?![\p{L}\p{N}_])/iu';
            if (preg_match($pattern, $message) === 1) {
                return true;
            }
        }

        return false;
    }

    private function respond(bool $success, array $messages = [], ?string $error = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'success' => $success,
            'messages' => $messages,
            'error' => $error,
        ], JSON_UNESCAPED_UNICODE);
    }
}
