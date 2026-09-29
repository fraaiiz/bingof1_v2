<?php

namespace App\Controller;

use App\Database\Database;
use App\View\ViewRenderer;

class InfosController {

    public function index(string $annee) {
        $database = new Database();
        $pdo = $database->getConnection();

        $seasonStatement = $pdo->prepare('SELECT id FROM saisons WHERE annee = :annee');
        $seasonStatement->execute([':annee' => $annee]);
        $seasonId = $seasonStatement->fetchColumn();
        $equipes = [];

        if ($seasonId !== false) {
            $teamStatement = $pdo->prepare(
                'SELECT e.id, e.nom_ecurie, e.nom_court, e.nationalite_ecurie, e.annee_creation,
                        es.logo, es.voiture, es.nombre_titre, m.nom_motoriste
                FROM ecuries_saisons es
                INNER JOIN ecuries e ON e.id = es.ecurie_id
                INNER JOIN motoristes m ON m.id = es.motoriste_id
                WHERE es.saison_id = :saison_id
                ORDER BY e.id ASC'
            );
            $teamStatement->execute([':saison_id' => $seasonId]);
            $equipes = $teamStatement->fetchAll(\PDO::FETCH_ASSOC);

            $teamsById = [];
            foreach ($equipes as $index => $equipe) {
                $equipes[$index]['pilotes'] = [];
                $equipes[$index]['reserves'] = [];
                $teamsById[$equipe['id']] = $index;
            }

            $driverStatement = $pdo->prepare(
                "SELECT pe.ecurie_id, pe.role, p.numero_pilote, p.prenom_pilote, p.nom_pilote,
                    p.nationalite_pilote, p.photo_pilote, pe.date_debut,
                    COALESCE(pe.nombre_titre_pilote, 0) AS nombre_titre_pilote
                FROM pilotes_engagement pe
                INNER JOIN pilotes p ON p.id = pe.pilote_id
                WHERE pe.saison_id = :saison_id
                ORDER BY pe.ecurie_id ASC,
                    CASE WHEN pe.role = 'titulaire' THEN 0 ELSE 1 END,
                    p.numero_pilote ASC"
            );
            $driverStatement->execute([':saison_id' => $seasonId]);

            foreach ($driverStatement->fetchAll(\PDO::FETCH_ASSOC) as $pilote) {
                if (!isset($teamsById[$pilote['ecurie_id']])) {
                    continue;
                }

                $teamIndex = $teamsById[$pilote['ecurie_id']];
                $group = $pilote['role'] === 'reserve' ? 'reserves' : 'pilotes';
                $equipes[$teamIndex][$group][] = $pilote;
            }
        }

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/infos', [
            'title' => 'BingoF1 - Infos ' . $annee,
            'annee' => $annee,
            'equipes' => $equipes
        ]);
    }

    public function redirectToCurrentSeason() {
        $currentYear = date('Y');
        header("Location: /saisons/$currentYear/infos");
        exit();
    }
}