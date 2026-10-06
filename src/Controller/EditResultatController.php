<?php

namespace App\Controller;

use App\Database\Database;
use App\Service\Csrf;
use App\Service\RaceTime;
use App\Service\ResultValidationException;
use App\View\ViewRenderer;
use InvalidArgumentException;
use PDO;
use PDOException;

class EditResultatController
{
    public function index(string $annee, string $id): void
    {
        if (!$this->requireEditor()) {
            return;
        }

        $pdo = (new Database())->getConnection();
        $course = $this->findCourse($pdo, $annee, $id);
        if ($course === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }
        if ($course['is_cancelled'] === 'yes') {
            http_response_code(410);
            echo 'Cette course a été annulée. Ses résultats ne peuvent pas être édités.';
            return;
        }

        $notice = $_SESSION['edit_resultat_notice'] ?? null;
        unset($_SESSION['edit_resultat_notice']);
        $this->renderEditor($pdo, $course, $annee, $notice);
    }

    public function store(string $annee, string $id): void
    {
        if (!$this->requireEditor()) {
            return;
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Requête invalide.';
            return;
        }

        $pdo = (new Database())->getConnection();
        $course = $this->findCourse($pdo, $annee, $id);
        if ($course === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }
        if ($course['is_cancelled'] === 'yes') {
            http_response_code(410);
            echo 'Cette course a été annulée. Ses résultats ne peuvent pas être édités.';
            return;
        }

        $sessions = $this->findSessions($pdo, (int) $course['format_id']);
        $sessionsById = [];
        foreach ($sessions as $session) {
            $sessionsById[(int) $session['id']] = (string) $session['session_type'];
        }

        $sessionId = filter_var($_POST['session_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($sessionId === false || !isset($sessionsById[(int) $sessionId])) {
            http_response_code(400);
            echo 'Séance invalide.';
            return;
        }
        $sessionId = (int) $sessionId;
        $sessionType = $sessionsById[$sessionId];
        $isPointsSession = in_array($sessionType, ['course', 'sprint'], true);
        $halfPointsEnabled = !empty($course['half_points']);
        if ($isPointsSession) {
            if (isset($_POST['half_points']) && $_POST['half_points'] !== '1') {
                http_response_code(400);
                echo 'Option de points invalide.';
                return;
            }
            $halfPointsEnabled = ($_POST['half_points'] ?? null) === '1';
            $course['half_points'] = $halfPointsEnabled ? 1 : 0;
        }

        $postedRows = $_POST['results'] ?? null;
        if (!is_array($postedRows)) {
            http_response_code(400);
            echo 'Résultats invalides.';
            return;
        }

        try {
            $eligibleDriversStatement = $pdo->prepare(
                'SELECT pilote_id, ecurie_id, role
                FROM pilotes_engagement
                WHERE saison_id = :saison_id
                ORDER BY pilote_id ASC,
                    CASE WHEN role = \'titulaire\' THEN 0 ELSE 1 END,
                    id ASC'
            );
            $eligibleDriversStatement->execute([':saison_id' => $course['id_saison']]);
            $eligibleDrivers = [];
            foreach ($eligibleDriversStatement->fetchAll(PDO::FETCH_ASSOC) as $engagement) {
                $driverId = (int) $engagement['pilote_id'];
                $teamId = (int) $engagement['ecurie_id'];
                $eligibleDrivers[$driverId]['teams'][$teamId] = true;
                $eligibleDrivers[$driverId]['default_team_id'] ??= $teamId;
            }
            $rows = $this->validateRows(
                $postedRows,
                $sessionsById[$sessionId],
                $eligibleDrivers
            );

            $pdo->beginTransaction();
            if ($isPointsSession) {
                $halfPointsStatement = $pdo->prepare(
                    'UPDATE courses SET half_points = :half_points WHERE id = :course_id'
                );
                $halfPointsStatement->execute([
                    ':half_points' => $halfPointsEnabled ? 1 : 0,
                    ':course_id' => $course['id'],
                ]);
            }
            $this->upsertRows($pdo, (int) $course['id'], $sessionId, $rows);
            $this->removeUnselectedRows($pdo, (int) $course['id'], $sessionId, array_column($rows, 'pilote_id'));
            $pdo->commit();

            $_SESSION['edit_resultat_notice'] = [
                'type' => 'success',
                'message' => 'Les résultats de la séance ont été enregistrés.',
            ];
            header('Location: /saisons/' . rawurlencode($annee) . '/courses/' . rawurlencode($id) . '/resultats/edition');
            exit();
        } catch (ResultValidationException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $fieldLabel = match ($exception->field) {
                'pilot_query' => 'Pilote',
                'team_id' => 'Écurie',
                'time' => 'Temps',
                'q1_time' => 'Q1',
                'q2_time' => 'Q2',
                'q3_time' => 'Q3',
                'sq1_time' => 'SQ1',
                'sq2_time' => 'SQ2',
                'sq3_time' => 'SQ3',
                'tours' => 'Tours',
                'status' => 'Statut',
                default => 'Champ',
            };
            http_response_code(422);
            $this->renderEditor(
                $pdo,
                $course,
                $annee,
                ['type' => 'error', 'message' => 'Ligne ' . $exception->position . ', ' . $fieldLabel . ' : ' . $exception->getMessage()],
                [$sessionId => $postedRows],
                [$sessionId => [
                    $exception->position => [
                        'field' => $exception->field,
                        'message' => $exception->getMessage(),
                    ],
                ]]
            );
        } catch (InvalidArgumentException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            http_response_code(422);
            $this->renderEditor(
                $pdo,
                $course,
                $annee,
                ['type' => 'error', 'message' => $exception->getMessage()],
                [$sessionId => $postedRows]
            );
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            http_response_code(500);
            $this->renderEditor(
                $pdo,
                $course,
                $annee,
                ['type' => 'error', 'message' => 'Impossible d’enregistrer cette séance pour le moment.'],
                [$sessionId => $postedRows]
            );
        }
    }

    private function requireEditor(): bool
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['auth_error'] = 'Connectez-vous pour éditer les résultats.';
            header('Location: /login');
            exit();
        }

        if (!in_array($_SESSION['user_role'] ?? null, ['admin', 'editor'], true)) {
            http_response_code(403);
            echo '403 Forbidden';
            return false;
        }

        return true;
    }

    private function findCourse(PDO $pdo, string $annee, string $id): ?array
    {
        if (!ctype_digit($id)) {
            return null;
        }

        $statement = $pdo->prepare(
            'SELECT courses.*
            FROM courses
            INNER JOIN saisons ON saisons.id = courses.id_saison
            WHERE saisons.annee = :annee AND courses.id = :id'
        );
        $statement->execute([':annee' => $annee, ':id' => $id]);
        $course = $statement->fetch(PDO::FETCH_ASSOC);

        return $course ?: null;
    }

    private function findSessions(PDO $pdo, int $formatId): array
    {
        $statement = $pdo->prepare(
            'SELECT id, session_type
            FROM sessions
            WHERE format_id = :format_id
            ORDER BY session_order ASC'
        );
        $statement->execute([':format_id' => $formatId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function renderEditor(
        PDO $pdo,
        array $course,
        string $annee,
        ?array $notice = null,
        array $oldResults = [],
        array $validationErrors = []
    ): void {
        $sessions = $this->findSessions($pdo, (int) $course['format_id']);
        $driversStatement = $pdo->prepare(
            "SELECT p.id, p.numero_pilote, p.prenom_pilote, p.nom_pilote,
                pe.ecurie_id, pe.role, e.nom_ecurie
            FROM pilotes_engagement pe
            INNER JOIN pilotes p ON p.id = pe.pilote_id
            LEFT JOIN ecuries e ON e.id = pe.ecurie_id
            WHERE pe.saison_id = :saison_id
            ORDER BY pe.ecurie_id ASC,
                CASE WHEN pe.role = 'titulaire' THEN 0 ELSE 1 END,
                p.numero_pilote ASC"
        );
        $driversStatement->execute([':saison_id' => $course['id_saison']]);
        $pilotes = $driversStatement->fetchAll(PDO::FETCH_ASSOC);

        $resultsStatement = $pdo->prepare('SELECT * FROM resultats WHERE course_id = :course_id');
        $resultsStatement->execute([':course_id' => $course['id']]);
        $existingResults = [];
        foreach ($resultsStatement->fetchAll(PDO::FETCH_ASSOC) as $result) {
            foreach (['time', 'q1_time', 'q2_time', 'q3_time', 'sq1_time', 'sq2_time', 'sq3_time'] as $field) {
                $result[$field . '_input'] = RaceTime::formatMilliseconds(
                    $result[$field] === null ? null : (int) $result[$field]
                );
            }
            $result['status'] = !empty($result['dnf'])
                ? 'dnf'
                : (!empty($result['dsq']) ? 'dsq' : (!empty($result['np']) ? 'np' : ''));
            $existingResults[(int) $result['session_id']][(int) $result['position']] = $result;
        }

        $viewRenderer = new ViewRenderer();
        $viewRenderer->render('View/pages/edit-resultat', [
            'title' => 'BingoF1 - Édition des résultats ' . $course['nom_circuit'] . ' ' . $annee,
            'annee' => $annee,
            'course' => $course,
            'sessions' => $sessions,
            'pilotes' => $pilotes,
            'csrfToken' => Csrf::token(),
            'notice' => $notice,
            'oldResults' => $oldResults,
            'existingResults' => $existingResults,
            'validationErrors' => $validationErrors,
        ]);
    }

    private function validateRows(array $postedRows, string $sessionType, array $eligibleDrivers): array
    {
        $timeFields = match ($sessionType) {
            'fp1', 'fp2', 'fp3', 'sprint', 'course' => ['time'],
            'qualif_course' => ['q1_time', 'q2_time', 'q3_time'],
            'qualif_sprint' => ['sq1_time', 'sq2_time', 'sq3_time'],
            default => throw new InvalidArgumentException('Type de séance non pris en charge.'),
        };
        $hasRaceFields = in_array($sessionType, ['sprint', 'course'], true);
        $seenDrivers = [];
        $rows = [];

        foreach ($postedRows as $position => $postedRow) {
            $position = filter_var($position, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 22],
            ]);
            if ($position === false || !is_array($postedRow)) {
                throw new InvalidArgumentException('Une position est invalide.');
            }
            foreach ($postedRow as $value) {
                if (!is_string($value) && !is_int($value)) {
                    throw new ResultValidationException((int) $position, 'pilot_query', 'Les données de cette ligne sont invalides.');
                }
            }

            $pilotIdValue = trim((string) ($postedRow['pilot_id'] ?? ''));
            $pilotQuery = trim((string) ($postedRow['pilot_query'] ?? ''));
            $hasOtherData = $pilotQuery !== ''
                || trim((string) ($postedRow['time'] ?? '')) !== ''
                || trim((string) ($postedRow['q1_time'] ?? '')) !== ''
                || trim((string) ($postedRow['q2_time'] ?? '')) !== ''
                || trim((string) ($postedRow['q3_time'] ?? '')) !== ''
                || trim((string) ($postedRow['sq1_time'] ?? '')) !== ''
                || trim((string) ($postedRow['sq2_time'] ?? '')) !== ''
                || trim((string) ($postedRow['sq3_time'] ?? '')) !== ''
                || trim((string) ($postedRow['tours'] ?? '')) !== ''
                || trim((string) ($postedRow['team_id'] ?? '')) !== ''
                || trim((string) ($postedRow['status'] ?? '')) !== '';

            if ($pilotIdValue === '') {
                if ($hasOtherData) {
                    throw new ResultValidationException((int) $position, 'pilot_query', 'Sélectionne un pilote dans la liste avant de saisir son résultat.');
                }
                continue;
            }

            $pilotId = filter_var($pilotIdValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($pilotId === false || !isset($eligibleDrivers[$pilotId])) {
                throw new ResultValidationException((int) $position, 'pilot_query', 'Ce pilote n’est pas engagé pour cette saison.');
            }
            $teamIdValue = trim((string) ($postedRow['team_id'] ?? ''));
            $teamId = $teamIdValue === ''
                ? $eligibleDrivers[$pilotId]['default_team_id']
                : filter_var($teamIdValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($teamId === false || !isset($eligibleDrivers[$pilotId]['teams'][$teamId])) {
                throw new ResultValidationException((int) $position, 'team_id', 'Cette écurie n’est pas associée à ce pilote pour cette saison.');
            }
            if (isset($seenDrivers[$pilotId])) {
                throw new ResultValidationException((int) $position, 'pilot_query', 'Ce pilote apparaît déjà dans cette séance.');
            }
            $seenDrivers[$pilotId] = true;

            $row = [
                'pilote_id' => $pilotId,
                'ecurie_id' => $teamId,
                'position' => $position,
                'time' => null,
                'q1_time' => null,
                'q2_time' => null,
                'q3_time' => null,
                'sq1_time' => null,
                'sq2_time' => null,
                'sq3_time' => null,
                'tours' => null,
                'dnf' => 0,
                'dsq' => 0,
                'np' => 0,
            ];

            foreach ($timeFields as $field) {
                try {
                    $row[$field] = RaceTime::parseMilliseconds($postedRow[$field] ?? '');
                } catch (InvalidArgumentException $exception) {
                    throw new ResultValidationException(
                        (int) $position,
                        $field,
                        $exception->getMessage()
                    );
                }
            }

            if ($hasRaceFields) {
                $lapsValue = trim((string) ($postedRow['tours'] ?? ''));
                if ($lapsValue !== '') {
                    $laps = filter_var($lapsValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99]]);
                    if ($laps === false) {
                        throw new ResultValidationException((int) $position, 'tours', 'Le nombre de tours doit être un entier entre 0 et 99.');
                    }
                    $row['tours'] = $laps;
                }

                $status = (string) ($postedRow['status'] ?? '');
                if (!in_array($status, ['', 'dnf', 'dsq', 'np'], true)) {
                    throw new ResultValidationException((int) $position, 'status', 'Le statut sélectionné est invalide.');
                }
                if ($status !== '') {
                    $row[$status] = 1;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function upsertRows(PDO $pdo, int $courseId, int $sessionId, array $rows): void
    {
        $columns = [
            'ecurie_id', 'position', 'time', 'q1_time', 'q2_time', 'q3_time',
            'sq1_time', 'sq2_time', 'sq3_time', 'tours', 'dnf', 'dsq', 'np',
        ];
        $insertColumns = array_merge(['course_id', 'session_id', 'pilote_id'], $columns);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $insertColumns);
        $updates = array_map(
            static fn (string $column): string => $column . ' = VALUES(' . $column . ')',
            $columns
        );
        $statement = $pdo->prepare(
            'INSERT INTO resultats (' . implode(', ', $insertColumns) . ')
            VALUES (' . implode(', ', $placeholders) . ')
            ON DUPLICATE KEY UPDATE ' . implode(', ', $updates)
        );

        foreach ($rows as $row) {
            $values = [
                ':course_id' => $courseId,
                ':session_id' => $sessionId,
                ':pilote_id' => $row['pilote_id'],
            ];
            foreach ($columns as $column) {
                $values[':' . $column] = $row[$column];
            }
            foreach ($values as $placeholder => $value) {
                $parameterType = $value === null
                    ? PDO::PARAM_NULL
                    : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                $statement->bindValue($placeholder, $value, $parameterType);
            }
            $statement->execute();
        }
    }

    private function removeUnselectedRows(PDO $pdo, int $courseId, int $sessionId, array $selectedDriverIds): void
    {
        if ($selectedDriverIds === []) {
            $statement = $pdo->prepare(
                'DELETE FROM resultats WHERE course_id = :course_id AND session_id = :session_id'
            );
            $statement->execute([':course_id' => $courseId, ':session_id' => $sessionId]);
            return;
        }

        $placeholders = [];
        $parameters = [':course_id' => $courseId, ':session_id' => $sessionId];
        foreach (array_values($selectedDriverIds) as $index => $driverId) {
            $placeholder = ':pilote_' . $index;
            $placeholders[] = $placeholder;
            $parameters[$placeholder] = $driverId;
        }

        $statement = $pdo->prepare(
            'DELETE FROM resultats
            WHERE course_id = :course_id AND session_id = :session_id
                AND pilote_id NOT IN (' . implode(', ', $placeholders) . ')'
        );
        $statement->execute($parameters);
    }
}