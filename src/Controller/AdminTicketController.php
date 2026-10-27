<?php

namespace App\Controller;

use App\Service\ErrorPage;
use App\Database\Database;
use App\Repository\TicketRepository;
use App\Service\Csrf;
use App\Service\Mailer;
use App\View\ViewRenderer;
use PDOException;

class AdminTicketController
{
    private const PRIORITIES = ['basse', 'normale', 'haute'];

    public function index(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        try {
            $this->render($this->repository()->findAll(), null);
        } catch (PDOException $exception) {
            error_log('Unable to load support tickets: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger les tickets pour le moment.';
        }
    }

    public function show(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $ticketId = $this->validateId($id);
        if ($ticketId === null) {
            ErrorPage::notFound();
        }

        try {
            $ticket = $this->repository()->find($ticketId);
            if ($ticket === null) {
                ErrorPage::notFound();
            }
            $this->render([], $ticket);
        } catch (PDOException $exception) {
            error_log('Unable to load support ticket: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger ce ticket pour le moment.';
        }
    }

    public function takeCharge(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $ticketId = $this->validateId($id);
        $location = '/admin/tickets/' . (int) $ticketId;
        if ($ticketId === null) {
            ErrorPage::notFound();
        }
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }

        $priority = $_POST['priority'] ?? null;
        if (!is_string($priority) || !in_array($priority, self::PRIORITIES, true)) {
            $this->redirectWithNotice('error', 'La priorité est invalide.', $location);
        }

        try {
            $ticket = $this->repository()->find($ticketId);
            if ($ticket === null) {
                $this->redirectWithNotice('error', 'Ce ticket est introuvable.', '/admin/tickets');
            }
            if ($ticket['status'] === 'traite') {
                $this->redirectWithNotice('error', 'Un ticket traité ne change plus de priorité.', $location);
            }
            if ($ticket['status'] === 'nouveau') {
                $this->repository()->takeCharge($ticketId, $priority);
                $this->redirectWithNotice('success', 'Le ticket a été pris en charge.', $location);
            }
            $this->repository()->updatePriority($ticketId, $priority);
        } catch (PDOException $exception) {
            error_log('Unable to update support ticket priority: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de mettre à jour ce ticket pour le moment.', $location);
        }

        $this->redirectWithNotice('success', 'La priorité a été enregistrée.', $location);
    }

    public function reply(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $ticketId = $this->validateId($id);
        $location = '/admin/tickets/' . (int) $ticketId;
        if ($ticketId === null) {
            ErrorPage::notFound();
        }
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }

        $response = $_POST['response'] ?? null;
        if (!is_string($response) || trim($response) === '') {
            $this->redirectWithNotice('error', 'La réponse ne peut pas être vide.', $location);
        }
        if (mb_strlen(trim($response)) > 5000) {
            $this->redirectWithNotice('error', 'La réponse doit contenir au maximum 5000 caractères.', $location);
        }

        $response = trim($response);
        try {
            $ticket = $this->repository()->find($ticketId);
            if ($ticket === null) {
                $this->redirectWithNotice('error', 'Ce ticket est introuvable.', '/admin/tickets');
            }
            $this->repository()->saveResponse($ticketId, $response);
        } catch (PDOException $exception) {
            error_log('Unable to save support ticket response: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible d’enregistrer la réponse pour le moment.', $location);
        }

        $message = 'La réponse a été enregistrée.';
        $sent = $this->notifyAuthor($ticket, $ticketId, $response);
        if ($sent === true) {
            $message = 'La réponse a été enregistrée et envoyée par email.';
        } elseif ($sent === false) {
            $message = 'La réponse a été enregistrée. L’email n’a pas pu être envoyé.';
        }

        $this->redirectWithNotice('success', $message, $location);
    }

    private function notifyAuthor(array $ticket, int $ticketId, string $response): ?bool
    {
        $email = $ticket['user_email'] ?? null;
        if (!is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        $subject = 'Réponse à votre ticket n°' . $ticketId;
        $body = "Bonjour,\n\nVoici la réponse à votre ticket :\n\n" . $response . "\n\nCordialement,\nSupport BingoF1";

        if ((new Mailer())->send($email, $subject, $body, 'dev@bingof1.fr') === true) {
            return true;
        }

        error_log('Unable to send support ticket reply for ticket ' . $ticketId);
        return false;
    }

    private function render(array $tickets, ?array $ticket): void
    {
        $notice = $_SESSION['admin_ticket_notice'] ?? null;
        unset($_SESSION['admin_ticket_notice']);

        (new ViewRenderer())->render('View/pages/admin/admin-tickets', [
            'title' => 'BingoF1 - ' . ($ticket === null ? 'Tickets' : 'Ticket n°' . $ticket['id']),
            'tickets' => $tickets,
            'ticket' => $ticket,
            'notice' => $notice,
            'csrfToken' => Csrf::token(),
        ]);
    }

    private function validateId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function repository(): TicketRepository
    {
        return new TicketRepository((new Database())->getConnection());
    }

    private function requireAdmin(): bool
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['auth_error'] = 'Connectez-vous pour accéder à l’administration.';
            header('Location: /login');
            exit();
        }

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo '403 Forbidden';
            return false;
        }

        return true;
    }

    private function redirectWithNotice(string $type, string $message, string $location): never
    {
        $_SESSION['admin_ticket_notice'] = ['type' => $type, 'message' => $message];
        header('Location: ' . $location);
        exit();
    }
}
