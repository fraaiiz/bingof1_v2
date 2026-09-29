<?php

namespace App\Controller;

use App\Database\Database;
use App\View\ViewRenderer;

class HomeController {

    public function index() {
        $actus = $this->getActus();
        $courseData = $this->getNextCourseData();

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/home', [
            'title' => 'BingoF1 - Accueil',
            'actus' => $actus,
            'course' => $courseData['course'],
            'status' => $courseData['status'],
        ]);
    }

    //Connexion webhook actus via motorsport.com
    private function getActus(): array {
        $actus = [];

        try {
            $rssUrl = 'https://fr.motorsport.com/rss/f1/news/';
            $rss = @simplexml_load_file($rssUrl);

            if ($rss) {
                foreach ($rss->channel->item as $item) {
                    $actus[] = [
                        'title' => (string) $item->title,
                        'link' => (string) $item->link,
                        'date' => date('d/m H:i', strtotime((string) $item->pubDate))
                    ];
                }
            }

            return array_slice($actus, 0, 10);
        } catch (\Exception $e) {
            return [];
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

            $stmt = $pdo->prepare('SELECT id, nom_circuit, date_fp1, date_course FROM courses WHERE date_fp1 > :now ORDER BY date_fp1 ASC LIMIT 1');
            $stmt->execute([':now' => $now]);
            $course = $stmt->fetch(\PDO::FETCH_ASSOC);

            //Gestion du décompte
            if ($course) {
                $endReference = $course['date_course'];
                $threeHoursAfterEnd = date('Y-m-d H:i:s', strtotime($endReference . ' +3 hours'));

                if ($now >= $course['date_fp1'] && $now <= $threeHoursAfterEnd) {
                    $status = 'en_cours';
                }
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