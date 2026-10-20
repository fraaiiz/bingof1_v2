<?php

namespace App\Controller;

use App\Database\Database;
use App\Repository\CircuitRepository;
use App\Repository\CourseRepository;
use App\Repository\SeasonRepository;
use App\Service\Csrf;
use App\View\ViewRenderer;
use PDOException;

class AdminSeasonController
{
    private const MIN_YEAR = 1950;
    private const MAX_YEAR = 2155;

    private const FORMATS = [
        0 => 'Week-end classique',
        1 => 'Week-end sprint',
    ];

    private const DATE_FIELDS = [
        'fp1' => ['column' => 'date_fp1', 'label' => 'Essais libres 1', 'required' => true],
        'fp2' => ['column' => 'date_fp2', 'label' => 'Essais libres 2', 'required' => false],
        'fp3' => ['column' => 'date_fp3', 'label' => 'Essais libres 3', 'required' => false],
        'qualif_sprint' => ['column' => 'date_qualif_sprint', 'label' => 'Qualifications sprint', 'required' => false],
        'sprint' => ['column' => 'date_sprint', 'label' => 'Sprint', 'required' => false],
        'qualif_course' => ['column' => 'date_qualif', 'label' => 'Qualifications', 'required' => false],
        'course' => ['column' => 'date_course', 'label' => 'Course', 'required' => true],
    ];

    public function index(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $this->redirectIfPendingBelongsElsewhere(null);

        try {
            $repository = $this->repository();
            $seasons = $repository->findAllWithUsage();
            $confirmation = $this->resolvePending($repository);
        } catch (PDOException $exception) {
            error_log('Unable to load seasons: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger les saisons pour le moment.';
            return;
        }

        $this->renderSeasons($seasons, $confirmation, null, []);
    }

    public function show(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $seasonId = $this->validateId($id);
        if ($seasonId === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $this->redirectIfPendingBelongsElsewhere($seasonId);

        try {
            $repository = $this->repository();
            $season = $repository->find($seasonId);
            if ($season === null) {
                http_response_code(404);
                echo '404 Not Found';
                return;
            }
            $courses = $this->courses()->findBySeason($seasonId);
            $confirmation = $this->resolvePending($repository);
            $sessionTypesByFormat = $this->courses()->findSessionTypesByFormat();
            $circuits = $this->circuits()->search('');
        } catch (PDOException $exception) {
            error_log('Unable to load season calendar: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger cette saison pour le moment.';
            return;
        }

        $formats = [];
        foreach (array_keys($sessionTypesByFormat) as $formatId) {
            $formats[$formatId] = self::FORMATS[$formatId] ?? ('Format ' . $formatId);
        }

        $this->renderSeasons([], $confirmation, $season, $courses, $formats, $sessionTypesByFormat, $circuits, null);
    }

    public function edit(string $id, string $courseId): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $seasonId = $this->validateId($id);
        $editedCourseId = $this->validateId($courseId);
        if ($seasonId === null || $editedCourseId === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $pending = $_SESSION['admin_season_pending'] ?? null;
        if (is_array($pending)) {
            $action = $pending['action'] ?? '';
            $target = in_array($action, ['create_course', 'update_course', 'delete_course'], true)
                ? '/admin/saisons/' . (int) ($pending['season_id'] ?? $seasonId)
                : '/admin/saisons';
            header('Location: ' . $target);
            exit();
        }

        try {
            $repository = $this->repository();
            $season = $repository->find($seasonId);
            $editedCourse = $season === null ? null : $this->courses()->findInSeason($seasonId, $editedCourseId);
            if ($season === null || $editedCourse === null) {
                http_response_code(404);
                echo '404 Not Found';
                return;
            }
            $courses = $this->courses()->findBySeason($seasonId);
            $sessionTypesByFormat = $this->courses()->findSessionTypesByFormat();
            $circuits = $this->circuits()->search('');
        } catch (PDOException $exception) {
            error_log('Unable to load race edition: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger cette course pour le moment.';
            return;
        }

        $formats = [];
        foreach (array_keys($sessionTypesByFormat) as $formatId) {
            $formats[$formatId] = self::FORMATS[$formatId] ?? ('Format ' . $formatId);
        }

        $this->renderSeasons([], null, $season, $courses, $formats, $sessionTypesByFormat, $circuits, $editedCourse);
    }

    public function prepare(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.');
        }

        if (isset($_SESSION['admin_season_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        $action = $_POST['action'] ?? null;
        if (!is_string($action) || !in_array($action, ['create', 'delete'], true)) {
            $this->redirectWithNotice('error', 'Action invalide.');
        }

        try {
            $repository = $this->repository();
            if ($action === 'create') {
                $year = $this->validateYear($_POST['annee'] ?? null);
                if ($year === null) {
                    $this->redirectWithNotice('error', 'L’année doit être comprise entre ' . self::MIN_YEAR . ' et ' . self::MAX_YEAR . '.');
                }
                if ($repository->yearTaken($year)) {
                    $this->redirectWithNotice('error', 'Cette saison existe déjà.');
                }
                $_SESSION['admin_season_pending'] = ['action' => 'create', 'annee' => $year];
                $this->redirectForPending();
            }

            $seasonId = $this->validateId($_POST['season_id'] ?? null);
            $season = $seasonId === null ? null : $repository->find($seasonId);
            if ($season === null) {
                $this->redirectWithNotice('error', 'Cette saison est introuvable.');
            }

            $_SESSION['admin_season_pending'] = [
                'action' => 'delete',
                'season_id' => $season['id'],
                'annee' => $season['annee'],
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare season action: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cette action pour le moment.');
        }

        $this->redirectForPending();
    }

    public function prepareCourse(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $seasonId = $this->validateId($id);
        if ($seasonId === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $this->queueCourse($seasonId, null);
    }

    public function prepareCourseUpdate(string $id, string $courseId): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $seasonId = $this->validateId($id);
        $editedCourseId = $this->validateId($courseId);
        if ($seasonId === null || $editedCourseId === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $this->queueCourse($seasonId, $editedCourseId);
    }

    public function prepareCourseDelete(string $id, string $courseId): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $seasonId = $this->validateId($id);
        $deletedCourseId = $this->validateId($courseId);
        $location = '/admin/saisons/' . (int) $seasonId;
        if ($seasonId === null || $deletedCourseId === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_season_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.', $location);
        }

        try {
            $course = $this->courses()->findInSeason($seasonId, $deletedCourseId);
            if ($course === null) {
                $this->redirectWithNotice('error', 'Cette course est introuvable.', $location);
            }

            $_SESSION['admin_season_pending'] = [
                'action' => 'delete_course',
                'season_id' => $seasonId,
                'course_id' => $deletedCourseId,
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare race deletion: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cette suppression pour le moment.', $location);
        }

        $this->redirectForPending();
    }

    private function queueCourse(int $seasonId, ?int $courseId): never
    {
        $location = $courseId === null
            ? '/admin/saisons/' . $seasonId
            : '/admin/saisons/' . $seasonId . '/courses/' . $courseId;
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_season_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.', $location);
        }

        try {
            $season = $this->repository()->find($seasonId);
            if ($season === null) {
                $this->redirectWithNotice('error', 'Cette saison est introuvable.', '/admin/saisons');
            }
            if ($courseId !== null && $this->courses()->findInSeason($seasonId, $courseId) === null) {
                $this->redirectWithNotice('error', 'Cette course est introuvable.', '/admin/saisons/' . $seasonId);
            }

            $course = $this->courseFromPost($_POST, $seasonId, $this->courses()->findSessionTypesByFormat(), $location);
            if ($this->courses()->roundTaken($seasonId, $course['num_manche'], $courseId)) {
                $this->redirectWithNotice('error', 'Cette manche existe déjà dans la saison.', $location);
            }

            $choice = $this->circuitChoiceFromPost($location);
            if ($choice['mode'] === 'existing') {
                $course['circuit_id'] = $choice['circuit']['id'];
            }
            $_SESSION['admin_season_pending'] = [
                'action' => $courseId === null ? 'create_course' : 'update_course',
                'season_id' => $seasonId,
                'course_id' => $courseId,
                'course' => $course,
                'circuit' => $choice['circuit'],
                'image_temp' => $choice['image_temp'],
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare race: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cette course pour le moment.', $location);
        }

        $this->redirectForPending();
    }

    private function saveCourse(array $confirmation): void
    {
        $createdCircuitId = null;
        $isUpdate = $confirmation['action'] === 'update_course';
        if ($confirmation['circuit']['is_new']) {
            $this->publishCircuitImage($confirmation);
            try {
                $createdCircuitId = $this->circuits()->create(
                    $confirmation['circuit']['pays'],
                    $confirmation['circuit']['ville'],
                    $confirmation['circuit']['image']
                );
                $confirmation['course']['circuit_id'] = $createdCircuitId;
                if ($isUpdate) {
                    $this->courses()->update($confirmation['season_id'], $confirmation['course_id'], $confirmation['course']);
                } else {
                    $this->courses()->create($confirmation['season_id'], $confirmation['course']);
                }
            } catch (PDOException $exception) {
                if ($createdCircuitId !== null) {
                    $this->circuits()->delete($createdCircuitId);
                }
                $this->deletePublishedImage((string) $confirmation['circuit']['image']);
                throw $exception;
            }
            return;
        }

        if ($isUpdate) {
            $this->courses()->update($confirmation['season_id'], $confirmation['course_id'], $confirmation['course']);
            return;
        }

        $this->courses()->create($confirmation['season_id'], $confirmation['course']);
    }

    public function confirm(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.');
        }

        $decision = $_POST['decision'] ?? null;
        if ($decision === 'cancel') {
            $pending = $_SESSION['admin_season_pending'] ?? null;
            $this->discardPendingImage(is_array($pending) ? $pending : null);
            unset($_SESSION['admin_season_pending']);
            $location = '/admin/saisons';
            if (is_array($pending) && in_array($pending['action'] ?? '', ['create_course', 'update_course', 'delete_course'], true)) {
                $location = '/admin/saisons/' . (int) ($pending['season_id'] ?? 0);
            }
            $this->redirectWithNotice('success', 'Action annulée.', $location);
        }
        if ($decision !== 'confirm') {
            $this->redirectWithNotice('error', 'Confirmation invalide.');
        }

        try {
            $repository = $this->repository();
            $confirmation = $this->resolvePending($repository);
            if ($confirmation === null) {
                $this->redirectWithNotice('error', 'Aucune action à confirmer.');
            }
            if ($confirmation['blocked']) {
                $this->redirectWithNotice('error', $confirmation['blocked_reason']);
            }

            if ($confirmation['action'] === 'create') {
                $repository->create($confirmation['annee']);
                unset($_SESSION['admin_season_pending']);
                $this->redirectWithNotice('success', 'La saison ' . $confirmation['annee'] . ' a été créée.', '/admin/saisons');
            }

            if ($confirmation['action'] === 'delete_course') {
                $this->courses()->delete($confirmation['season_id'], $confirmation['course_id']);
                unset($_SESSION['admin_season_pending']);
                $this->redirectWithNotice(
                    'success',
                    'La manche ' . $confirmation['course']['num_manche'] . ' a été supprimée.',
                    '/admin/saisons/' . $confirmation['season_id']
                );
            }

            if ($confirmation['action'] === 'create_course' || $confirmation['action'] === 'update_course') {
                $this->saveCourse($confirmation);
                unset($_SESSION['admin_season_pending']);
                $this->redirectWithNotice(
                    'success',
                    $confirmation['action'] === 'update_course'
                        ? 'La manche ' . $confirmation['course']['num_manche'] . ' a été mise à jour.'
                        : 'La manche ' . $confirmation['course']['num_manche'] . ' a été ajoutée au calendrier.',
                    '/admin/saisons/' . $confirmation['season_id']
                );
            }

            $repository->delete($confirmation['season_id']);
            unset($_SESSION['admin_season_pending']);
            $this->redirectWithNotice('success', 'La saison ' . $confirmation['current_annee'] . ' a été supprimée.', '/admin/saisons');
        } catch (PDOException $exception) {
            error_log('Unable to confirm season action: ' . $exception->getMessage());
            unset($_SESSION['admin_season_pending']);
            $this->redirectWithNotice('error', 'Impossible d’enregistrer cette action pour le moment.');
        }
    }

    private function renderSeasons(
        array $seasons,
        ?array $confirmation,
        ?array $season,
        array $courses,
        array $formats = self::FORMATS,
        array $sessionTypesByFormat = [],
        array $circuits = [],
        ?array $editedCourse = null
    ): void {
        $notice = $_SESSION['admin_season_notice'] ?? null;
        unset($_SESSION['admin_season_notice']);

        (new ViewRenderer())->render('View/pages/admin-saisons', [
            'title' => $season === null
                ? 'BingoF1 - Saisons'
                : 'BingoF1 - Saison ' . $season['annee'],
            'seasons' => $seasons,
            'season' => $season,
            'courses' => $courses,
            'formats' => $formats,
            'sessionTypesByFormat' => $sessionTypesByFormat,
            'circuits' => $circuits,
            'editedCourse' => $editedCourse,
            'dateFields' => self::DATE_FIELDS,
            'confirmation' => $confirmation,
            'notice' => $notice,
            'csrfToken' => Csrf::token(),
            'minYear' => self::MIN_YEAR,
            'maxYear' => self::MAX_YEAR,
        ]);
    }

    private function resolvePending(SeasonRepository $repository): ?array
    {
        $pending = $_SESSION['admin_season_pending'] ?? null;
        if (!is_array($pending)) {
            return null;
        }

        $action = $pending['action'] ?? null;
        if (!is_string($action) || !in_array($action, ['create', 'delete', 'create_course', 'update_course', 'delete_course'], true)) {
            unset($_SESSION['admin_season_pending']);
            return null;
        }

        if ($action === 'create_course' || $action === 'update_course') {
            return $this->resolveCoursePending($repository, $pending);
        }

        if ($action === 'delete_course') {
            return $this->resolveCourseDelete($pending);
        }

        $year = $pending['annee'] ?? null;
        if (!is_int($year)) {
            unset($_SESSION['admin_season_pending']);
            return null;
        }

        if ($action === 'create') {
            $blocked = $repository->yearTaken($year);

            return [
                'action' => 'create',
                'annee' => $year,
                'season_id' => null,
                'current_annee' => null,
                'course' => null,
                'usage' => null,
                'blocked' => $blocked,
                'blocked_reason' => $blocked ? 'Cette année existe déjà.' : '',
            ];
        }

        $seasonId = $pending['season_id'] ?? null;
        if (!is_int($seasonId)) {
            unset($_SESSION['admin_season_pending']);
            return null;
        }

        $season = $repository->find($seasonId);
        if ($season === null) {
            unset($_SESSION['admin_season_pending']);
            return null;
        }

        $usage = [
            'courses_count' => $season['courses_count'],
            'drivers_count' => $season['drivers_count'],
            'teams_count' => $season['teams_count'],
            'bingos_count' => $season['bingos_count'],
        ];
        $blocked = array_sum($usage) > 0;

        return [
            'action' => 'delete',
            'annee' => $season['annee'],
            'season_id' => $season['id'],
            'current_annee' => $season['annee'],
            'course' => null,
            'usage' => $usage,
            'blocked' => $blocked,
            'blocked_reason' => $blocked ? 'Des données sont encore rattachées à cette saison.' : '',
        ];
    }

    private function resolveCoursePending(SeasonRepository $repository, array $pending): ?array
    {
        $seasonId = $pending['season_id'] ?? null;
        $course = $pending['course'] ?? null;
        $circuit = $pending['circuit'] ?? null;
        $imageTemp = $pending['image_temp'] ?? null;
        $isNewCircuit = is_array($circuit) && ($circuit['is_new'] ?? null) === true;
        $isExistingCircuit = is_array($circuit) && ($circuit['is_new'] ?? null) === false;
        if (!is_int($seasonId) || !is_array($course) || (!$isNewCircuit && !$isExistingCircuit)) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_season_pending']);
            return null;
        }
        if ($isNewCircuit && !is_string($imageTemp)) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_season_pending']);
            return null;
        }
        if ($isExistingCircuit) {
            $circuitId = $circuit['id'] ?? null;
            $freshCircuit = is_int($circuitId) ? $this->circuits()->find($circuitId) : null;
            if ($freshCircuit === null) {
                $this->discardPendingImage($pending);
                unset($_SESSION['admin_season_pending']);
                return null;
            }
            $circuit = $freshCircuit;
            $circuit['is_new'] = false;
            $course['circuit_id'] = $freshCircuit['id'];
        }

        $season = $repository->find($seasonId);
        if ($season === null) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_season_pending']);
            return null;
        }

        $courseId = $pending['course_id'] ?? null;
        $isUpdate = ($pending['action'] ?? '') === 'update_course';
        if ($isUpdate && (!is_int($courseId) || $this->courses()->findInSeason($seasonId, $courseId) === null)) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_season_pending']);
            return null;
        }

        $blocked = $this->courses()->roundTaken($seasonId, (int) ($course['num_manche'] ?? 0), $isUpdate ? $courseId : null);

        return [
            'action' => $isUpdate ? 'update_course' : 'create_course',
            'annee' => $season['annee'],
            'season_id' => $season['id'],
            'course_id' => $isUpdate ? $courseId : null,
            'current_annee' => $season['annee'],
            'course' => $course,
            'circuit' => $circuit,
            'image_temp' => is_string($imageTemp) ? $imageTemp : null,
            'usage' => null,
            'blocked' => $blocked,
            'blocked_reason' => $blocked ? 'Cette manche existe déjà dans la saison.' : '',
        ];
    }

    private function resolveCourseDelete(array $pending): ?array
    {
        $seasonId = $pending['season_id'] ?? null;
        $courseId = $pending['course_id'] ?? null;
        if (!is_int($seasonId) || !is_int($courseId)) {
            unset($_SESSION['admin_season_pending']);
            return null;
        }

        $season = $this->repository()->find($seasonId);
        $course = $season === null ? null : $this->courses()->findInSeason($seasonId, $courseId);
        if ($season === null || $course === null) {
            unset($_SESSION['admin_season_pending']);
            return null;
        }

        $usage = $this->courses()->usage($courseId);
        $blocked = array_sum($usage) > 0;

        return [
            'action' => 'delete_course',
            'annee' => $season['annee'],
            'season_id' => $season['id'],
            'course_id' => $course['id'],
            'current_annee' => $season['annee'],
            'course' => [
                'num_manche' => $course['num_manche'],
                'nom_circuit' => $course['nom_circuit'],
            ],
            'usage' => $usage,
            'blocked' => $blocked,
            'blocked_reason' => $blocked ? 'Des données sont encore rattachées à cette course.' : '',
        ];
    }

    private function courseFromPost(array $post, int $seasonId, array $sessionTypesByFormat, ?string $location = null): array
    {
        $location ??= '/admin/saisons/' . $seasonId;
        $round = filter_var($post['num_manche'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 30],
        ]);
        $circuit = $this->boundedText($post['nom_circuit'] ?? null, 100, false);
        $formatId = filter_var($post['format_id'] ?? null, FILTER_VALIDATE_INT);
        $cancelled = $post['is_cancelled'] ?? null;
        $laps = $this->optionalInt($post['nombre_tours'] ?? null, 0, 99);
        $corners = $this->optionalInt($post['nombre_virages'] ?? null, 0, 99);
        $length = $this->boundedText($post['longueur_circuit'] ?? null, 25, false);
        $bestLap = $this->boundedText($post['best_lap'] ?? null, 50, false);
        $lastWinner = $this->boundedText($post['last_winner'] ?? null, 50, false);

        if ($round === false) {
            $this->redirectWithNotice('error', 'La manche est requise.', $location);
        }
        if ($circuit === false) {
            $this->redirectWithNotice('error', 'Le nom de l’événement est trop long.', $location);
        }
        if ($formatId === false || !isset($sessionTypesByFormat[$formatId])) {
            $this->redirectWithNotice('error', 'Le format du week-end est invalide.', $location);
        }
        if (!is_string($cancelled) || !in_array($cancelled, ['no', 'yes'], true)) {
            $this->redirectWithNotice('error', 'Le statut d’annulation est invalide.', $location);
        }
        if ($laps === false || $corners === false || $length === false || $bestLap === false || $lastWinner === false) {
            $this->redirectWithNotice('error', 'Une information du circuit est invalide.', $location);
        }

        $halfPoints = $post['half_points'] ?? '0';
        if (!is_string($halfPoints) || !in_array($halfPoints, ['0', '1'], true)) {
            $this->redirectWithNotice('error', 'L’option de points est invalide.', $location);
        }

        $allowedSessions = $sessionTypesByFormat[$formatId];
        $dates = [];
        foreach (self::DATE_FIELDS as $sessionType => $field) {
            if (!in_array($sessionType, $allowedSessions, true)) {
                $dates[$field['column']] = null;
                continue;
            }

            $dates[$field['column']] = $field['required']
                ? $this->dateTimeValue($post[$field['column']] ?? null, true)
                : $this->optionalDate($post[$field['column']] ?? null, $location);
            if ($dates[$field['column']] === false) {
                $this->redirectWithNotice('error', 'La date « ' . $field['label'] . ' » est requise.', $location);
            }
        }

        if ($dates['date_fp1'] !== null && $dates['date_course'] !== null && $dates['date_course'] < $dates['date_fp1']) {
            $this->redirectWithNotice('error', 'La course doit commencer après les essais libres 1.', $location);
        }

        return [
            'num_manche' => $round,
            'nom_circuit' => $circuit ?? '',
            'format_id' => $formatId,
            'is_cancelled' => $cancelled,
            'date_fp1' => $dates['date_fp1'],
            'date_fp2' => $dates['date_fp2'],
            'date_fp3' => $dates['date_fp3'],
            'date_qualif_sprint' => $dates['date_qualif_sprint'],
            'date_sprint' => $dates['date_sprint'],
            'date_qualif' => $dates['date_qualif'],
            'date_course' => $dates['date_course'],
            'nombre_tours' => $laps,
            'longueur_circuit' => $length,
            'nombre_virages' => $corners,
            'best_lap' => $bestLap,
            'last_winner' => $lastWinner,
            'half_points' => (int) $halfPoints,
        ];
    }

    private function circuitChoiceFromPost(string $location): array
    {
        $circuitId = $this->validateId($_POST['circuit_id'] ?? null);
        if ($circuitId !== null) {
            $circuit = $this->circuits()->find($circuitId);
            if ($circuit === null) {
                $this->redirectWithNotice('error', 'Ce circuit est introuvable.', $location);
            }
            $circuit['is_new'] = false;

            return ['mode' => 'existing', 'circuit' => $circuit, 'image_temp' => null];
        }

        $pays = $this->boundedText($_POST['circuit_pays'] ?? null, 50, true);
        $ville = $this->boundedText($_POST['circuit_ville'] ?? null, 50, false);
        if ($pays === false || $ville === false) {
            $this->redirectWithNotice('error', 'Choisis un circuit existant, ou indique le pays du nouveau circuit.', $location);
        }

        $existing = $this->circuits()->findByPlace($pays, $ville);
        if ($existing !== null) {
            $this->redirectWithNotice('error', 'Ce circuit existe déjà. Sélectionne-le dans la recherche.', $location);
        }

        $image = $this->stageCircuitImage($location);
        if ($this->circuits()->imageTaken($image['path'])) {
            $this->discardPendingImage(['image_temp' => $image['temp']]);
            $this->redirectWithNotice('error', 'Une image porte déjà ce nom.', $location);
        }

        return [
            'mode' => 'new',
            'circuit' => [
                'id' => null,
                'pays' => $pays,
                'ville' => $ville,
                'image' => $image['path'],
                'is_new' => true,
            ],
            'image_temp' => $image['temp'],
        ];
    }

    private function stageCircuitImage(string $location): array
    {
        $file = $_FILES['image_trace'] ?? null;
        if (!is_array($file)) {
            $this->redirectWithNotice('error', 'L’image du tracé est requise.', $location);
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            $this->redirectWithNotice('error', 'L’image du tracé est requise.', $location);
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            $this->redirectWithNotice('error', 'L’image dépasse la taille autorisée.', $location);
        }
        if ($error !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
            $this->redirectWithNotice('error', 'L’envoi de l’image a échoué.', $location);
        }
        if ((int) ($file['size'] ?? 0) > 2_000_000) {
            $this->redirectWithNotice('error', 'L’image ne doit pas dépasser 2 Mo.', $location);
        }

        $filename = $this->circuitFilename(is_string($file['name'] ?? null) ? $file['name'] : '');
        if ($filename === null) {
            $this->redirectWithNotice('error', 'Le fichier doit s’appeler comme portugal.png, sans espace ni dossier.', $location);
        }

        $path = '/assets/images/circuits/' . $filename;
        if (strlen($path) > 255) {
            $this->redirectWithNotice('error', 'Le nom du fichier est trop long.', $location);
        }
        if (is_file($this->circuitsDirectory() . DIRECTORY_SEPARATOR . $filename)) {
            $this->redirectWithNotice('error', 'Une image porte déjà ce nom.', $location);
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $expectedMime = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => '',
        };
        if ($expectedMime === '' || $mime !== $expectedMime) {
            $this->redirectWithNotice('error', 'Le fichier doit être une image PNG, JPEG, WEBP ou GIF.', $location);
        }

        $staging = $this->stagingDirectory();
        if (!is_dir($staging) && !mkdir($staging, 0775, true) && !is_dir($staging)) {
            $this->redirectWithNotice('error', 'Impossible de préparer l’image pour le moment.', $location);
        }

        $temp = $staging . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $temp)) {
            $this->redirectWithNotice('error', 'Impossible d’enregistrer l’image pour le moment.', $location);
        }

        return ['path' => $path, 'temp' => $temp];
    }

    private function publishCircuitImage(array $confirmation): void
    {
        $path = $confirmation['circuit']['image'] ?? null;
        $filename = is_string($path) ? $this->circuitFilename(basename($path)) : null;
        $tempPath = $this->stagedFile($confirmation['image_temp'] ?? null);
        $seasonLocation = '/admin/saisons/' . (int) ($confirmation['season_id'] ?? 0);
        if ($filename === null || $tempPath === null || $path !== '/assets/images/circuits/' . $filename) {
            $this->discardPendingImage($confirmation);
            unset($_SESSION['admin_season_pending']);
            $this->redirectWithNotice('error', 'L’image envoyée n’est plus disponible.', $seasonLocation);
        }

        $directory = $this->circuitsDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $this->redirectWithNotice('error', 'Impossible d’enregistrer l’image pour le moment.', $seasonLocation);
        }

        $destination = $directory . DIRECTORY_SEPARATOR . $filename;
        if (is_file($destination)) {
            $this->discardPendingImage($confirmation);
            unset($_SESSION['admin_season_pending']);
            $this->redirectWithNotice('error', 'Une image porte déjà ce nom.', $seasonLocation);
        }

        if (!rename($tempPath, $destination)) {
            if (!copy($tempPath, $destination) || !unlink($tempPath)) {
                $this->redirectWithNotice('error', 'Impossible d’enregistrer l’image pour le moment.', $seasonLocation);
            }
        }
    }

    private function circuitFilename(string $name): ?string
    {
        $filename = basename(str_replace('\\', '/', $name));
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,70}\.(png|jpe?g|webp|gif)$/', $filename)) {
            return null;
        }

        return $filename;
    }

    private function stagedFile(mixed $temp): ?string
    {
        if (!is_string($temp) || !is_file($temp)) {
            return null;
        }

        $staging = realpath($this->stagingDirectory());
        $file = realpath($temp);
        if ($staging === false || $file === false || !str_starts_with($file, $staging . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $file;
    }

    private function discardPendingImage(?array $pending): void
    {
        if (!is_array($pending)) {
            return;
        }

        $file = $this->stagedFile($pending['image_temp'] ?? null);
        if ($file !== null) {
            unlink($file);
        }
    }

    private function deletePublishedImage(string $path): void
    {
        $filename = $this->circuitFilename(basename($path));
        if ($filename === null || $path !== '/assets/images/circuits/' . $filename) {
            return;
        }

        $file = $this->circuitsDirectory() . DIRECTORY_SEPARATOR . $filename;
        if (is_file($file)) {
            unlink($file);
        }
    }

    private function circuitsDirectory(): string
    {
        return dirname(__DIR__, 2) . '/public/assets/images/circuits';
    }

    private function stagingDirectory(): string
    {
        return sys_get_temp_dir() . '/bingof1-circuit-uploads';
    }

    private function optionalDate(mixed $value, string $location): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $date = $this->dateTimeValue($value, true);
        if ($date === false) {
            $this->redirectWithNotice('error', 'Une date du week-end est invalide.', $location);
        }

        return $date;
    }

    private function dateTimeValue(mixed $value, bool $required): string|false|null
    {
        if (!is_string($value) || trim($value) === '') {
            return $required ? false : null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', trim($value))
            ?: \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', trim($value));

        return $date === false ? false : $date->format('Y-m-d H:i:s');
    }

    private function boundedText(mixed $value, int $max, bool $required): string|false|null
    {
        if (!is_string($value)) {
            return $required ? false : null;
        }

        $value = trim($value);
        if ($value === '') {
            return $required ? false : null;
        }
        if (mb_strlen($value) > $max) {
            return false;
        }

        return $value;
    }

    private function optionalInt(mixed $value, int $min, int $max): int|false|null
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $min, 'max_range' => $max],
        ]);

        return $number === false ? false : $number;
    }

    private function redirectIfPendingBelongsElsewhere(?int $seasonId): void
    {
        $pending = $_SESSION['admin_season_pending'] ?? null;
        if (!is_array($pending)) {
            return;
        }

        $action = $pending['action'] ?? '';
        if ($action === 'create_course' || $action === 'update_course' || $action === 'delete_course') {
            $pendingSeasonId = (int) ($pending['season_id'] ?? 0);
            if ($seasonId !== $pendingSeasonId) {
                header('Location: /admin/saisons/' . $pendingSeasonId);
                exit();
            }
            return;
        }

        if ($seasonId !== null) {
            header('Location: /admin/saisons');
            exit();
        }
    }

    private function validateYear(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $year = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => self::MIN_YEAR, 'max_range' => self::MAX_YEAR],
        ]);

        return $year === false ? null : $year;
    }

    private function validateId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function repository(): SeasonRepository
    {
        return new SeasonRepository((new Database())->getConnection());
    }

    private function courses(): CourseRepository
    {
        return new CourseRepository((new Database())->getConnection());
    }

    private function circuits(): CircuitRepository
    {
        return new CircuitRepository((new Database())->getConnection());
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

    private function redirectWithNotice(string $type, string $message, ?string $location = null): never
    {
        $_SESSION['admin_season_notice'] = ['type' => $type, 'message' => $message];
        if ($location !== null) {
            header('Location: ' . $location);
            exit();
        }

        $this->redirectForPending();
    }

    private function redirectForPending(): never
    {
        $pending = $_SESSION['admin_season_pending'] ?? null;
        if (is_array($pending) && in_array($pending['action'] ?? '', ['create_course', 'update_course', 'delete_course'], true)) {
            header('Location: /admin/saisons/' . (int) ($pending['season_id'] ?? 0));
            exit();
        }

        header('Location: /admin/saisons');
        exit();
    }
}
