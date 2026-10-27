<?php

namespace App\Controller;

use App\Database\Database;
use App\Repository\PredictionRepository;
use App\Repository\SiteSettingsRepository;
use App\Service\Csrf;
use App\Service\NewsFeed;
use App\View\ViewRenderer;

class HomeController {

    public function index() {
        $newsFeed = (new NewsFeed())->get();
        $courseData = $this->getNextCourseData();
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        $drivers = [];
        $userPrediction = null;
        $participantPredictions = [];
        $predictionOpen = false;
        $predictionNotice = $_SESSION['prediction_notice'] ?? null;
        unset($_SESSION['prediction_notice']);

        if ($courseData['course'] && $courseData['status'] === 'en_cours') {
            try {
                $database = new Database();
                $predictionRepository = new PredictionRepository($database->getConnection());
                $courseId = (int) $courseData['course']['id'];
                $drivers = $predictionRepository->findEligibleDrivers($courseId);
                $participantPredictions = $predictionRepository->findCoursePredictions($courseId);
                $predictionOpen = new \DateTimeImmutable() < new \DateTimeImmutable($courseData['course']['date_course']);

                if ($userId !== null) {
                    $userPrediction = $predictionRepository->findUserPrediction($userId, $courseId);
                }
            } catch (\PDOException $exception) {
                $drivers = [];
                $participantPredictions = [];
                $predictionOpen = false;
            }
        }

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/home', [
            'title' => 'BingoF1 - Accueil',
            'actus' => $newsFeed['items'],
            'actusStale' => $newsFeed['stale'],
            'actusAvailable' => $newsFeed['available'],
            'course' => $courseData['course'],
            'status' => $courseData['status'],
            'isLoggedIn' => $userId !== null,
            'userRole' => $_SESSION['user_role'] ?? '',
            'drivers' => $drivers,
            'userPrediction' => $userPrediction,
            'participantPredictions' => $participantPredictions,
            'predictionOpen' => $predictionOpen,
            'predictionNotice' => $predictionNotice,
            'chatOpen' => $this->chatOpen(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    private function chatOpen(): bool
    {
        try {
            return (new SiteSettingsRepository((new Database())->getConnection()))->isChatOpen();
        } catch (\PDOException $exception) {
            error_log('Unable to read chat setting: ' . $exception->getMessage());
            return true;
        }
    }

    //Compte a rebours  
    private function getNextCourseData(): array {
        $course = null;
        $status = 'a_venir';

        try {
            //Connexion BDD
            $database = new Database();
            $pdo = $database->getConnection();
            $now = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare("SELECT id, nom_circuit, date_fp1, date_course FROM courses WHERE is_cancelled = 'no' AND date_fp1 <= :started_at AND DATE_ADD(date_course, INTERVAL 3 HOUR) >= :ends_at ORDER BY date_fp1 DESC LIMIT 1");
            $stmt->execute([':started_at' => $now, ':ends_at' => $now]);
            $course = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($course) {
                $status = 'en_cours';
            } else {
                $stmt = $pdo->prepare("SELECT id, nom_circuit, date_fp1, date_course FROM courses WHERE is_cancelled = 'no' AND date_fp1 > :now ORDER BY date_fp1 ASC LIMIT 1");
                $stmt->execute([':now' => $now]);
                $course = $stmt->fetch(\PDO::FETCH_ASSOC);
            }

        } catch (\Exception $e) {
            $course = null;
            $status = 'a_venir';
        }

        return [
            'course' => $course,
            'status' => $status,
        ];
    }
}