<?php

namespace App\Controller;

use App\Database\Database;
use App\Repository\SeasonRepository;
use App\View\ViewRenderer;

class CalendrierController {

    public function index(string $annee) {
        $database = new Database();
        $pdo = $database->getConnection();
        $stmt = $pdo->prepare(
            'SELECT courses.*,
                circuits.pays AS circuit_pays,
                circuits.ville AS circuit_ville,
                circuits.image AS circuit_image
            FROM courses
            INNER JOIN saisons ON saisons.id = courses.id_saison
            LEFT JOIN circuits ON circuits.id = courses.circuit_id
            WHERE saisons.annee = :annee
            ORDER BY courses.num_manche ASC'
        );
        $stmt->execute([':annee' => $annee]);
        $courses = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $today = new \DateTimeImmutable('today');
        $eligibleCourseIndexes = [];
        foreach ($courses as $index => $course) {
            if ($course['is_cancelled'] !== 'yes') {
                $eligibleCourseIndexes[] = $index;
            }
        }
        $selectedCourseIndex = $eligibleCourseIndexes === []
            ? 0
            : $eligibleCourseIndexes[count($eligibleCourseIndexes) - 1];
        $nextCourseIndex = null;

        foreach ($courses as $index => $course) {
            if ($course['is_cancelled'] === 'yes') {
                continue;
            }

            $startDate = (new \DateTimeImmutable($course['date_fp1']))->setTime(0, 0);
            $endDate = (new \DateTimeImmutable($course['date_course']))->setTime(23, 59, 59);

            if ($today >= $startDate && $today <= $endDate) {
                $selectedCourseIndex = $index;
                $nextCourseIndex = null;
                break;
            }

            if ($nextCourseIndex === null && $startDate > $today) {
                $nextCourseIndex = $index;
            }
        }

        if ($nextCourseIndex !== null) {
            $selectedCourseIndex = $nextCourseIndex;
        }

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/calendrier', [
            'title' => 'BingoF1 - Calendrier ' . $annee,
            'annee' => $annee,
            'courses' => $courses,
            'selectedCourseIndex' => $selectedCourseIndex,
            'canEditResults' => !empty($_SESSION['user_id'])
                && in_array($_SESSION['user_role'] ?? null, ['admin', 'editor'], true),
        ]);
    }

    public function redirectToDefaultSeason(): void {
        $seasonYear = (new SeasonRepository((new Database())->getConnection()))->findDefaultYear();
        if ($seasonYear === null) {
            http_response_code(404);
            echo 'Aucune saison n’est disponible.';
            return;
        }

        header('Location: /saisons/' . rawurlencode((string) $seasonYear) . '/calendrier');
        exit();
    }
}