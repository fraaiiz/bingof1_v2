<?php

namespace App\Controller;

use App\Database\Database;
use App\Repository\TicketRepository;
use App\Service\Csrf;
use App\Service\Mailer;
use App\Service\PagePath;
use App\Service\RateLimiter;
use App\View\ViewRenderer;
use PDOException;

class ContactController
{
    private const CATEGORIES = ['bug', 'suggestion', 'course', 'compte', 'question', 'autre'];

    public function index(): void
    {
        RateLimiter::denyWhenLimited('contact', 5, 3600);
        $notice = $_SESSION['contact_notice'] ?? null;
        $draft = $_SESSION['contact_form'] ?? null;
        unset($_SESSION['contact_notice'], $_SESSION['contact_form']);

        try {
            $repository = $this->repository();
            $userId = $this->currentUserId();
            $email = is_array($draft) ? (string) ($draft['user_email'] ?? '') : '';
            if ($email === '' && $userId !== null) {
                $email = $repository->findAccountEmail($userId) ?? '';
            }

            (new ViewRenderer())->render('View/pages/contact', [
                'title' => 'BingoF1 - Contact',
                'notice' => $notice,
                'csrfToken' => Csrf::token(),
                'courses' => $repository->findCourses(),
                'tickets' => $userId === null ? [] : $repository->findByUser($userId),
                'draft' => [
                    'category' => is_array($draft) ? (string) ($draft['category'] ?? '') : '',
                    'course_id' => is_array($draft) ? (string) ($draft['course_id'] ?? '') : '',
                    'user_email' => $email,
                    'subject' => is_array($draft) ? (string) ($draft['subject'] ?? '') : '',
                    'message' => is_array($draft) ? (string) ($draft['message'] ?? '') : '',
                    'page_url' => is_array($draft) ? (string) ($draft['page_url'] ?? '') : $this->refererPath(),
                ],
            ]);
        } catch (PDOException $exception) {
            error_log('Unable to load contact page: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible d’afficher le formulaire de contact pour le moment.';
        }
    }

    public function store(): void
    {
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.');
        }

        $category = $_POST['category'] ?? null;
        $subject = $_POST['subject'] ?? null;
        $message = $_POST['message'] ?? null;
        $email = $_POST['user_email'] ?? null;
        $courseId = $_POST['course_id'] ?? null;
        if (!is_string($category) || !is_string($subject) || !is_string($message) || !is_string($email) || !is_string($courseId)) {
            $this->redirectWithNotice('error', 'Formulaire invalide.');
        }

        $subject = trim($subject);
        $message = trim($message);
        $email = trim($email);
        $draft = [
            'category' => $category,
            'course_id' => $courseId,
            'user_email' => $email,
            'subject' => $subject,
            'message' => $message,
            'page_url' => $this->pagePath($_POST['page_url'] ?? null),
        ];

        if (!in_array($category, self::CATEGORIES, true)) {
            $this->redirectWithNotice('error', 'La catégorie est invalide.', $draft);
        }
        if ($subject === '' || mb_strlen($subject) > 255) {
            $this->redirectWithNotice('error', 'Le sujet doit contenir entre 1 et 255 caractères.', $draft);
        }
        if ($message === '' || mb_strlen($message) > 5000) {
            $this->redirectWithNotice('error', 'Le message doit contenir entre 1 et 5000 caractères.', $draft);
        }
        if ($email !== '' && (mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            $this->redirectWithNotice('error', 'L’adresse email est invalide.', $draft);
        }

        try {
            $repository = $this->repository();
            $limiter = new RateLimiter((new Database())->getConnection());
            if ($limiter->tooMany('contact', RateLimiter::clientIp(), 5, 3600)) {
                RateLimiter::deny('contact', 3600);
            }
            $course = null;
            if ($category === 'course') {
                $course = $this->validateId($courseId) === null ? null : $repository->findCourse((int) $courseId);
                if ($course === null) {
                    $this->redirectWithNotice('error', 'La course choisie est introuvable.', $draft);
                }
            }

            $userId = $this->currentUserId();
            $browserInfo = $this->browserInfo();
            $pageUrl = $course === null
                ? $draft['page_url']
                : '/saisons/' . $course['annee'] . '/courses/' . $course['id'] . '/resultats';
            $ticketId = $repository->create([
                'user_id' => $userId,
                'user_email' => $email === '' ? null : $email,
                'category' => $category,
                'subject' => $subject,
                'message' => $message,
                'page_url' => $pageUrl,
                'course_id' => $course['id'] ?? null,
                'browser_info' => $browserInfo,
            ]);
            $limiter->hit('contact', RateLimiter::clientIp());
            $this->notifyHost($ticketId, [
                'category' => $category,
                'subject' => $subject,
                'message' => $message,
                'page_url' => $pageUrl,
                'user_id' => $userId,
                'user_email' => $email === '' ? null : $email,
                'browser_info' => $browserInfo,
            ]);
        } catch (PDOException $exception) {
            error_log('Unable to create support ticket: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible d’envoyer le message pour le moment.', $draft);
        }

        $this->redirectWithNotice('success', 'Merci, le ticket n°' . $ticketId . ' a bien été envoyé.');
    }

    private function notifyHost(int $ticketId, array $ticket): void
    {
        $subject = 'Nouveau ticket n°' . $ticketId . ' - ' . str_replace(["\r", "\n"], '', (string) $ticket['category']);
        $body = "Ticket n°" . $ticketId . "\n"
            . 'Catégorie : ' . $ticket['category'] . "\n"
            . 'Sujet : ' . $ticket['subject'] . "\n"
            . "Message :\n" . $ticket['message'] . "\n\n"
            . 'Page : ' . $ticket['page_url'] . "\n"
            . 'Utilisateur : ' . ($ticket['user_id'] ?? 'invité') . "\n"
            . 'Email : ' . ($ticket['user_email'] ?? '-') . "\n"
            . 'Navigateur : ' . ($ticket['browser_info'] ?? '-') . "\n";

        if ((new Mailer())->send('dev@bingof1.fr', $subject, $body) !== true) {
            error_log('Unable to send support ticket notification for ticket ' . $ticketId);
        }
    }

    private function refererPath(): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $refererHost = parse_url(is_string($referer) ? $referer : '', PHP_URL_HOST);
        $refererPath = parse_url(is_string($referer) ? $referer : '', PHP_URL_PATH);
        if (is_string($refererHost) && strcasecmp($refererHost, $host) === 0) {
            return $this->pagePath($refererPath);
        }

        return '/contact';
    }

    private function pagePath(mixed $value): string
    {
        return PagePath::internal($value) ?? '/contact';
    }

    private function browserInfo(): ?string
    {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (!is_string($agent) || $agent === '') {
            return null;
        }

        return mb_substr($agent, 0, 2000);
    }

    private function currentUserId(): ?int
    {
        $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $userId === false ? null : $userId;
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

    private function redirectWithNotice(string $type, string $message, ?array $draft = null): never
    {
        $_SESSION['contact_notice'] = ['type' => $type, 'message' => $message];
        if ($draft !== null) {
            $_SESSION['contact_form'] = $draft;
        }
        header('Location: /contact');
        exit();
    }
}
