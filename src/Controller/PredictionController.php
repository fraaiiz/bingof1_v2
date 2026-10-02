<?php

namespace App\Controller;

use App\Database\Database;
use App\Repository\PredictionRepository;
use App\Service\Csrf;

class PredictionController
{
    public function store(): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['auth_error'] = 'Connectez-vous pour enregistrer vos pronostics.';
            header('Location: /login');
            exit();
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'La session a expiré. Rechargez la page et réessayez.');
        }

        $courseId = $this->positiveInteger($_POST['course_id'] ?? null);
        $p1 = $this->positiveInteger($_POST['p1'] ?? null);
        $p2 = $this->positiveInteger($_POST['p2'] ?? null);
        $p3 = $this->positiveInteger($_POST['p3'] ?? null);

        if ($courseId === null || $p1 === null || $p2 === null || $p3 === null) {
            $this->redirectWithNotice('error', 'Tous les choix sont requis.');
        }

        if (count(array_unique([$p1, $p2, $p3])) !== 3) {
            $this->redirectWithNotice('error', 'Choisissez trois pilotes différents.');
        }

        try {
            $database = new Database();
            $repository = new PredictionRepository($database->getConnection());
            $course = $repository->findOpenCourse($courseId);

            if (!$course
                || new \DateTimeImmutable() < new \DateTimeImmutable($course['date_fp1'])
                || new \DateTimeImmutable() >= new \DateTimeImmutable($course['date_course'])) {
                $this->redirectWithNotice('error', 'La période de pronostic pour cette course est fermée.');
            }

            if (!$repository->areEligibleDrivers($courseId, $p1, $p2, $p3)) {
                $this->redirectWithNotice('error', 'Un ou plusieurs pilotes ne sont pas engagés pour cette course.');
            }

            $repository->save((int) $_SESSION['user_id'], $courseId, $p1, $p2, $p3);
        } catch (\PDOException $exception) {
            $this->redirectWithNotice('error', 'Impossible d’enregistrer les pronostics pour le moment.');
        }

        $this->redirectWithNotice('success', 'Vos pronostics ont été enregistrés.');
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $integer === false ? null : $integer;
    }

    private function redirectWithNotice(string $type, string $message): void
    {
        $_SESSION['prediction_notice'] = ['type' => $type, 'message' => $message];
        header('Location: /');
        exit();
    }
}