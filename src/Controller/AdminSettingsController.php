<?php

namespace App\Controller;

use App\Database\Database;
use App\Repository\SiteSettingsRepository;
use App\Service\Csrf;
use App\View\ViewRenderer;
use PDOException;

class AdminSettingsController
{
    private const SETTINGS = [
        'maintenance' => 'maintenance',
        'chat' => 'chat_open',
    ];

    public function index(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        try {
            $this->render($this->repository()->all());
        } catch (PDOException $exception) {
            error_log('Unable to load site settings: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger les paramètres pour le moment.';
        }
    }

    public function update(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.');
        }

        $setting = $_POST['setting'] ?? null;
        $enabled = $_POST['enabled'] ?? null;
        if (!is_string($setting) || !isset(self::SETTINGS[$setting]) || ($enabled !== '0' && $enabled !== '1')) {
            $this->redirectWithNotice('error', 'Ce réglage est invalide.');
        }

        try {
            $this->repository()->save(self::SETTINGS[$setting], $enabled === '1');
        } catch (PDOException $exception) {
            error_log('Unable to save site setting: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible d’enregistrer ce réglage pour le moment.');
        }

        $this->redirectWithNotice('success', $this->successMessage($setting, $enabled === '1'));
    }

    private function successMessage(string $setting, bool $enabled): string
    {
        if ($setting === 'maintenance') {
            return $enabled ? 'La maintenance est activée.' : 'Le site est rouvert.';
        }

        return $enabled ? 'Le chat est ouvert.' : 'Le chat est fermé.';
    }

    private function render(array $settings): void
    {
        $notice = $_SESSION['admin_settings_notice'] ?? null;
        unset($_SESSION['admin_settings_notice']);

        (new ViewRenderer())->render('View/pages/admin/admin-settings', [
            'title' => 'BingoF1 - Paramètres',
            'settings' => $settings,
            'notice' => $notice,
            'csrfToken' => Csrf::token(),
        ]);
    }

    private function repository(): SiteSettingsRepository
    {
        return new SiteSettingsRepository((new Database())->getConnection());
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

    private function redirectWithNotice(string $type, string $message): never
    {
        $_SESSION['admin_settings_notice'] = ['type' => $type, 'message' => $message];
        header('Location: /admin/settings');
        exit();
    }
}
