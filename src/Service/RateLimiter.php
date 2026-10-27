<?php

namespace App\Service;

use App\Database\Database;
use PDO;
use PDOException;

class RateLimiter
{
    public function __construct(private PDO $pdo)
    {
    }

    public static function deny(string $scope, int $retryAfter): never
    {
        self::remember($scope, $retryAfter);
        self::respond($retryAfter);
    }

    public static function denyWhenLimited(string $scope, int $max, int $windowSeconds): void
    {
        self::denyIfRemembered($scope);
        try {
            $limiter = new self((new Database())->getConnection());
            if ($limiter->tooMany($scope, self::clientIp(), $max, $windowSeconds)) {
                self::deny($scope, $windowSeconds);
            }
        } catch (PDOException $exception) {
            error_log('Unable to read rate limit: ' . $exception->getMessage());
        }
    }

    private static function denyIfRemembered(string $scope): void
    {
        $until = $_SESSION['rate_limited_until'][$scope] ?? null;
        if (!is_int($until)) {
            return;
        }
        if ($until <= time()) {
            unset($_SESSION['rate_limited_until'][$scope]);
            return;
        }

        self::respond($until - time());
    }

    private static function remember(string $scope, int $retryAfter): void
    {
        if (preg_match('/^[a-z-]{1,32}$/', $scope) !== 1) {
            throw new \InvalidArgumentException('Invalid rate limit scope.');
        }
        if (!isset($_SESSION['rate_limited_until']) || !is_array($_SESSION['rate_limited_until'])) {
            $_SESSION['rate_limited_until'] = [];
        }

        $_SESSION['rate_limited_until'][$scope] = time() + max(1, $retryAfter);
    }

    private static function respond(int $retryAfter): never
    {
        $retryAfter = max(1, $retryAfter);
        $waitMinutes = (int) ceil($retryAfter / 60);
        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store');
        require dirname(__DIR__) . '/View/pages/errors/error-429.php';
        exit();
    }

    public static function clientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return 'invalid';
        }

        return $ip;
    }

    public function tooMany(string $scope, string $subject, int $max, int $windowSeconds): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM request_attempts
            WHERE scope = :scope
            AND subject_hash = :subject_hash
            AND created_at > DATE_SUB(NOW(), INTERVAL ' . $this->window($windowSeconds) . ' SECOND)'
        );
        $statement->execute([
            ':scope' => $this->scope($scope),
            ':subject_hash' => $this->hash($scope, $subject),
        ]);

        return (int) $statement->fetchColumn() >= $max;
    }

    public function hit(string $scope, string $subject): void
    {
        $scope = $this->scope($scope);
        $hash = $this->hash($scope, $subject);
        $statement = $this->pdo->prepare(
            'INSERT INTO request_attempts (scope, subject_hash, created_at)
            VALUES (:scope, :subject_hash, NOW())'
        );
        $statement->execute([
            ':scope' => $scope,
            ':subject_hash' => $hash,
        ]);
        $this->pdo->prepare(
            'DELETE FROM request_attempts
            WHERE created_at <= DATE_SUB(NOW(), INTERVAL 1 DAY)'
        )->execute();
    }

    public function clear(string $scope, string $subject): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM request_attempts
            WHERE scope = :scope AND subject_hash = :subject_hash'
        );
        $statement->execute([
            ':scope' => $this->scope($scope),
            ':subject_hash' => $this->hash($scope, $subject),
        ]);
    }

    private function scope(string $scope): string
    {
        if (preg_match('/^[a-z-]{1,32}$/', $scope) !== 1) {
            throw new \InvalidArgumentException('Invalid rate limit scope.');
        }

        return $scope;
    }

    private function window(int $windowSeconds): int
    {
        if ($windowSeconds < 1 || $windowSeconds > 86400) {
            throw new \InvalidArgumentException('Invalid rate limit window.');
        }

        return $windowSeconds;
    }

    private function hash(string $scope, string $subject): string
    {
        return hash('sha256', $this->scope($scope) . "\0" . $subject);
    }
}
