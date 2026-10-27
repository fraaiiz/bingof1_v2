<?php

namespace App\Controller;

use App\Service\ErrorPage;
use App\Database\Database;
use App\Repository\UserRepository;
use App\Service\Csrf;
use App\View\ViewRenderer;
use PDOException;

class AdminUserController
{
    private const ROLES = ['user', 'editor', 'admin'];
    public function index(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $this->redirectIfPendingBelongsElsewhere(null);

        try {
            $repository = $this->repository();
            $this->render($repository->findAll(), $this->resolvePending($repository), null, null, null);
        } catch (PDOException $exception) {
            error_log('Unable to load users: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger les utilisateurs pour le moment.';
        }
    }

    public function show(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $userId = $this->validateId($id);
        if ($userId === null) {
            ErrorPage::notFound();
        }

        $this->redirectIfPendingBelongsElsewhere($userId);

        try {
            $repository = $this->repository();
            $user = $repository->find($userId);
            if ($user === null) {
                ErrorPage::notFound();
            }

            $this->render(
                [],
                $this->resolvePending($repository),
                $user,
                $repository->usage($userId),
                $repository->activity($userId)
            );
        } catch (PDOException $exception) {
            error_log('Unable to load user: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger ce compte pour le moment.';
        }
    }

    public function prepareUpdate(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $userId = $this->validateId($id);
        $location = '/admin/users/' . (int) $userId;
        if ($userId === null) {
            ErrorPage::notFound();
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_user_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        $posted = ['user_role' => $this->roleFromPost($location)];

        try {
            $repository = $this->repository();
            $current = $repository->find($userId);
            if ($current === null) {
                $this->redirectWithNotice('error', 'Ce compte est introuvable.', '/admin/users');
            }
            if ($this->demotesLastAdmin($repository, $current, $posted)) {
                $this->redirectWithNotice('error', 'Le dernier administrateur ne peut pas changer de rôle.', $location);
            }

            $_SESSION['admin_user_pending'] = [
                'action' => 'update',
                'user_id' => $userId,
                'user' => $posted,
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare user update: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cette modification pour le moment.', $location);
        }

        $this->redirectForPending();
    }

    public function prepareDelete(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $userId = $this->validateId($id);
        $location = '/admin/users/' . (int) $userId;
        if ($userId === null) {
            ErrorPage::notFound();
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_user_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        try {
            if ($this->repository()->find($userId) === null) {
                $this->redirectWithNotice('error', 'Ce compte est introuvable.', '/admin/users');
            }

            $_SESSION['admin_user_pending'] = [
                'action' => 'delete',
                'user_id' => $userId,
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare user deletion: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cette suppression pour le moment.', $location);
        }

        $this->redirectForPending();
    }

    public function confirm(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.');
        }

        $decision = $_POST['decision'] ?? null;
        $pending = $_SESSION['admin_user_pending'] ?? null;
        if ($decision === 'cancel') {
            $location = is_array($pending) ? $this->locationFor($pending) : '/admin/users';
            unset($_SESSION['admin_user_pending']);
            $this->redirectWithNotice('success', 'Action annulée.', $location);
        }
        if ($decision !== 'confirm') {
            $this->redirectWithNotice('error', 'Confirmation invalide.');
        }

        try {
            $repository = $this->repository();
            $confirmation = $this->resolvePending($repository);
            if ($confirmation === null) {
                $this->redirectWithNotice('error', 'Aucune action à confirmer.');
            }
            if ($confirmation['blocked']) {
                $this->redirectWithNotice('error', $confirmation['blocked_reason']);
            }

            $login = $confirmation['user']['user_login'];
            if ($confirmation['action'] === 'update') {
                $repository->updateRole($confirmation['user_id'], $confirmation['posted']['user_role']);
                $location = '/admin/users/' . $confirmation['user_id'];
                if ((int) ($_SESSION['user_id'] ?? 0) === $confirmation['user_id']) {
                    $_SESSION['user_role'] = $confirmation['posted']['user_role'];
                    if ($confirmation['posted']['user_role'] !== 'admin') {
                        $location = '/';
                    }
                }
                unset($_SESSION['admin_user_pending']);
                $this->redirectWithNotice('success', 'Le rôle de ' . $login . ' a été mis à jour.', $location);
            }

            if ($confirmation['action'] === 'delete') {
                $repository->delete($confirmation['user_id']);
                unset($_SESSION['admin_user_pending']);
                $this->redirectWithNotice('success', $login . ' a été supprimé.', '/admin/users');
            }

            $this->redirectWithNotice('error', 'Action invalide.');
        } catch (PDOException $exception) {
            error_log('Unable to confirm user action: ' . $exception->getMessage());
            unset($_SESSION['admin_user_pending']);
            $this->redirectWithNotice('error', 'Impossible d’enregistrer cette action pour le moment.');
        }
    }

    private function render(
        array $users,
        ?array $confirmation,
        ?array $user,
        ?array $usage,
        ?array $activity
    ): void {
        $notice = $_SESSION['admin_user_notice'] ?? null;
        unset($_SESSION['admin_user_notice']);

        (new ViewRenderer())->render('View/pages/admin/admin-users', [
            'title' => 'BingoF1 - ' . ($user === null ? 'Utilisateurs' : $user['user_login']),
            'users' => $users,
            'user' => $user,
            'usage' => $usage,
            'activity' => $activity,
            'confirmation' => $confirmation,
            'notice' => $notice,
            'csrfToken' => Csrf::token(),
            'currentUserId' => (int) ($_SESSION['user_id'] ?? 0),
        ]);
    }

    private function resolvePending(UserRepository $repository): ?array
    {
        $pending = $_SESSION['admin_user_pending'] ?? null;
        if (!is_array($pending)) {
            return null;
        }

        $action = $pending['action'] ?? null;
        $userId = $pending['user_id'] ?? null;
        if (!is_string($action) || !in_array($action, ['update', 'delete'], true) || !is_int($userId)) {
            unset($_SESSION['admin_user_pending']);
            return null;
        }

        $user = $repository->find($userId);
        if ($user === null) {
            unset($_SESSION['admin_user_pending']);
            return null;
        }

        $usage = $repository->usage($userId);
        if ($action === 'delete') {
            $self = (int) ($_SESSION['user_id'] ?? 0) === $userId;
            $attached = array_sum($usage) > 0;

            return [
                'action' => 'delete',
                'user_id' => $userId,
                'user' => $user,
                'posted' => null,
                'usage' => $usage,
                'blocked' => $self || $attached,
                'blocked_reason' => $self
                    ? 'Le compte connecté ne peut pas être supprimé.'
                    : ($attached ? 'Des bingos, pronostics, messages ou tickets sont encore rattachés à ce compte.' : ''),
            ];
        }

        $posted = $pending['user'] ?? null;
        if (!$this->validUserPayload($posted)) {
            unset($_SESSION['admin_user_pending']);
            return null;
        }

        $blocked = false;
        $reason = '';
        if ($this->demotesLastAdmin($repository, $user, $posted)) {
            $blocked = true;
            $reason = 'Le dernier administrateur ne peut pas changer de rôle.';
        }

        return [
            'action' => 'update',
            'user_id' => $userId,
            'user' => $user,
            'posted' => $posted,
            'usage' => $usage,
            'blocked' => $blocked,
            'blocked_reason' => $reason,
        ];
    }

    private function roleFromPost(string $location): string
    {
        $role = $_POST['user_role'] ?? null;
        if (!is_string($role) || !in_array($role, self::ROLES, true)) {
            $this->redirectWithNotice('error', 'Le rôle est invalide.', $location);
        }

        return $role;
    }

    private function validUserPayload(mixed $user): bool
    {
        $role = is_array($user) ? ($user['user_role'] ?? null) : null;

        return is_string($role) && in_array($role, self::ROLES, true);
    }

    private function demotesLastAdmin(UserRepository $repository, array $current, array $posted): bool
    {
        return $current['user_role'] === 'admin'
            && $posted['user_role'] !== 'admin'
            && $repository->adminCount() <= 1;
    }

    private function redirectIfPendingBelongsElsewhere(?int $userId): void
    {
        $pending = $_SESSION['admin_user_pending'] ?? null;
        if (!is_array($pending)) {
            return;
        }

        $target = $this->locationFor($pending);
        $here = $userId === null ? '/admin/users' : '/admin/users/' . $userId;
        if ($here !== $target) {
            header('Location: ' . $target);
            exit();
        }
    }

    private function locationFor(array $pending): string
    {
        $userId = $pending['user_id'] ?? null;
        if (is_int($userId)) {
            return '/admin/users/' . $userId;
        }

        return '/admin/users';
    }

    private function validateId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function repository(): UserRepository
    {
        return new UserRepository((new Database())->getConnection());
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

    private function redirectWithNotice(string $type, string $message, ?string $location = null): never
    {
        $_SESSION['admin_user_notice'] = ['type' => $type, 'message' => $message];
        if ($location !== null) {
            header('Location: ' . $location);
            exit();
        }

        $pending = $_SESSION['admin_user_pending'] ?? null;
        header('Location: ' . (is_array($pending) ? $this->locationFor($pending) : '/admin/users'));
        exit();
    }

    private function redirectForPending(): never
    {
        $pending = $_SESSION['admin_user_pending'] ?? null;
        header('Location: ' . (is_array($pending) ? $this->locationFor($pending) : '/admin/users'));
        exit();
    }
}
