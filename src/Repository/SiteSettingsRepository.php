<?php

namespace App\Repository;

use PDO;

class SiteSettingsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function isMaintenance(): bool
    {
        return $this->isEnabled('maintenance', false);
    }

    public function isChatOpen(): bool
    {
        return $this->isEnabled('chat_open', true);
    }

    public function all(): array
    {
        $statement = $this->pdo->query('SELECT setting_key, setting_value FROM site_settings');
        $rows = $statement->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'maintenance' => ($rows['maintenance'] ?? '0') === '1',
            'chat_open' => ($rows['chat_open'] ?? '1') === '1',
        ];
    }

    public function save(string $key, bool $enabled): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO site_settings (setting_key, setting_value)
            VALUES (:setting_key, :setting_value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $statement->execute([
            ':setting_key' => $key,
            ':setting_value' => $enabled ? '1' : '0',
        ]);
    }

    private function isEnabled(string $key, bool $default): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT setting_value FROM site_settings WHERE setting_key = :setting_key'
        );
        $statement->execute([':setting_key' => $key]);
        $value = $statement->fetchColumn();
        if (!is_string($value)) {
            return $default;
        }

        return $value === '1';
    }
}
