<?php

namespace App\Controller;

use App\Database\Database;
use App\View\ViewRenderer;

class ResultatController {

    public function index(string $annee, string $id): void {
        if (!ctype_digit($id)) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $database = new Database();
        $pdo = $database->getConnection();
        $stmt = $pdo->prepare(
            'SELECT courses.*
            FROM courses
            INNER JOIN saisons ON saisons.id = courses.id_saison
            WHERE saisons.annee = :annee AND courses.id = :id'
        );
        $stmt->execute([':annee' => $annee, ':id' => $id]);
        $course = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($course === false) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if ($course['is_cancelled'] === 'yes') {
            http_response_code(410);
            (new ViewRenderer())->render('View/pages/resultat', [
                'title' => 'BingoF1 - Course annulée ' . $course['nom_circuit'] . ' ' . $annee,
                'annee' => $annee,
                'course' => $course,
                'sessions' => [],
                'resultsBySession' => [],
            ]);
            return;
        }

        $sessionsStatement = $pdo->prepare(
            'SELECT id, session_type
            FROM sessions
            WHERE format_id = :format_id
            ORDER BY session_order ASC'
        );
        $sessionsStatement->execute([':format_id' => $course['format_id']]);
        $sessions = $sessionsStatement->fetchAll(\PDO::FETCH_ASSOC);

        $resultsStatement = $pdo->prepare(
            'SELECT r.session_id, r.position, r.pilote_id, r.time, r.legacy_delta,
                r.q1_time, r.q2_time, r.q3_time, r.sq1_time, r.sq2_time, r.sq3_time,
                r.tours, r.dsq, r.dnf, r.np, p.prenom_pilote, p.nom_pilote
            FROM resultats r
            INNER JOIN sessions s ON s.id = r.session_id
            INNER JOIN pilotes p ON p.id = r.pilote_id
            WHERE r.course_id = :course_id
            ORDER BY s.session_order ASC, r.position ASC'
        );
        $resultsStatement->execute([':course_id' => $id]);
        $resultsBySession = [];
        foreach ($resultsStatement->fetchAll(\PDO::FETCH_ASSOC) as $result) {
            $resultsBySession[$result['session_id']][] = $result;
        }

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/resultat', [
            'title' => 'BingoF1 - Résultats ' . $course['nom_circuit'] . ' ' . $annee,
            'annee' => $annee,
            'course' => $course,
            'sessions' => $sessions,
            'resultsBySession' => $resultsBySession,
        ]);
    }
}