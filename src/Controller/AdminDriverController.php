<?php

namespace App\Controller;

use App\Service\ErrorPage;
use App\Database\Database;
use App\Repository\DriverRepository;
use App\Service\Csrf;
use App\View\ViewRenderer;
use PDOException;

class AdminDriverController
{
    private const MIN_NUMBER = 1;
    private const MAX_NUMBER = 99;
    private const MIN_YEAR = 1901;
    private const MAX_YEAR = 2155;
    private const MAX_TITLES = 99;

    private const DRIVER_ACTIONS = ['create', 'update', 'delete'];
    private const ENGAGEMENT_ACTIONS = ['create_engagement', 'update_engagement', 'delete_engagement'];

    public function index(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $this->redirectIfPendingBelongsElsewhere(null, false);

        try {
            $repository = $this->repository();
            $drivers = $repository->findAll();
            $confirmation = $this->resolvePending($repository);
        } catch (PDOException $exception) {
            error_log('Unable to load drivers: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger les pilotes pour le moment.';
            return;
        }

        $this->render($drivers, $confirmation, null, [], null, [], []);
    }

    public function show(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $driverId = $this->validateId($id);
        if ($driverId === null) {
            ErrorPage::notFound();
        }

        $this->redirectIfPendingBelongsElsewhere($driverId, false);

        try {
            $repository = $this->repository();
            $driver = $repository->find($driverId);
            if ($driver === null) {
                ErrorPage::notFound();
            }

            $this->render(
                [],
                $this->resolvePending($repository),
                $driver,
                $repository->findEngagements($driverId),
                null,
                $repository->findSeasons(),
                $repository->findTeamsBySeason()
            );
        } catch (PDOException $exception) {
            error_log('Unable to load driver: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger ce pilote pour le moment.';
        }
    }

    public function editEngagement(string $id, string $engagementId): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $driverId = $this->validateId($id);
        $editedEngagementId = $this->validateId($engagementId);
        if ($driverId === null || $editedEngagementId === null) {
            ErrorPage::notFound();
        }

        $this->redirectIfPendingBelongsElsewhere($driverId, true);
        $this->renderDriver($driverId, $editedEngagementId);
    }

    public function prepareCreate(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $this->queueDriver(null);
    }

    public function prepareUpdate(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $driverId = $this->validateId($id);
        if ($driverId === null) {
            ErrorPage::notFound();
        }

        $this->queueDriver($driverId);
    }

    public function prepareDelete(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $driverId = $this->validateId($id);
        $location = '/admin/pilotes/' . (int) $driverId;
        if ($driverId === null) {
            ErrorPage::notFound();
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_driver_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        try {
            $driver = $this->repository()->find($driverId);
            if ($driver === null) {
                $this->redirectWithNotice('error', 'Ce pilote est introuvable.', '/admin/pilotes');
            }

            $_SESSION['admin_driver_pending'] = [
                'action' => 'delete',
                'driver_id' => $driverId,
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare driver deletion: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cette suppression pour le moment.', $location);
        }

        $this->redirectForPending();
    }

    public function prepareEngagement(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $driverId = $this->validateId($id);
        if ($driverId === null) {
            ErrorPage::notFound();
        }

        $this->queueEngagement($driverId, null);
    }

    public function prepareEngagementUpdate(string $id, string $engagementId): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $driverId = $this->validateId($id);
        $editedEngagementId = $this->validateId($engagementId);
        if ($driverId === null || $editedEngagementId === null) {
            ErrorPage::notFound();
        }

        $this->queueEngagement($driverId, $editedEngagementId);
    }

    public function prepareEngagementDelete(string $id, string $engagementId): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $driverId = $this->validateId($id);
        $deletedEngagementId = $this->validateId($engagementId);
        $location = '/admin/pilotes/' . (int) $driverId;
        if ($driverId === null || $deletedEngagementId === null) {
            ErrorPage::notFound();
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_driver_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        try {
            $engagement = $this->repository()->findEngagement($driverId, $deletedEngagementId);
            if ($engagement === null) {
                $this->redirectWithNotice('error', 'Cet engagement est introuvable.', $location);
            }

            $_SESSION['admin_driver_pending'] = [
                'action' => 'delete_engagement',
                'driver_id' => $driverId,
                'engagement_id' => $deletedEngagementId,
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare engagement deletion: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cette suppression pour le moment.', $location);
        }

        $this->redirectForPending();
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
        $pending = $_SESSION['admin_driver_pending'] ?? null;
        if ($decision === 'cancel') {
            $this->discardPendingImage(is_array($pending) ? $pending : null);
            $location = is_array($pending) ? $this->locationFor($pending) : '/admin/pilotes';
            unset($_SESSION['admin_driver_pending']);
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

            $driverLabel = $this->driverLabel($confirmation['driver']);
            if ($confirmation['action'] === 'create') {
                $this->publishPhoto($confirmation, null);
                try {
                    $repository->create($confirmation['driver']);
                } catch (PDOException $exception) {
                    $this->deletePublishedPhoto($confirmation['driver']['photo_pilote']);
                    throw $exception;
                }
                unset($_SESSION['admin_driver_pending']);
                $this->redirectWithNotice('success', $driverLabel . ' a été créé.', '/admin/pilotes');
            }

            if ($confirmation['action'] === 'update') {
                $existing = $repository->find($confirmation['driver_id']);
                if ($existing === null) {
                    $this->discardPendingImage(is_array($pending) ? $pending : null);
                    unset($_SESSION['admin_driver_pending']);
                    $this->redirectWithNotice('error', 'Ce pilote est introuvable.', '/admin/pilotes');
                }
                $previousPhoto = $existing['photo_pilote'];
                $createdNewFile = $confirmation['image_is_new'] && $previousPhoto !== $confirmation['driver']['photo_pilote'];
                try {
                    $this->publishPhoto($confirmation, $previousPhoto);
                    $repository->update($confirmation['driver_id'], $confirmation['driver']);
                } catch (PDOException $exception) {
                    if ($createdNewFile) {
                        $this->deletePublishedPhoto($confirmation['driver']['photo_pilote']);
                    }
                    throw $exception;
                }
                unset($_SESSION['admin_driver_pending']);
                $this->redirectWithNotice(
                    'success',
                    $driverLabel . ' a été mis à jour.',
                    '/admin/pilotes/' . $confirmation['driver_id']
                );
            }

            if ($confirmation['action'] === 'delete') {
                $repository->delete($confirmation['driver_id']);
                unset($_SESSION['admin_driver_pending']);
                $this->redirectWithNotice('success', $driverLabel . ' a été supprimé.', '/admin/pilotes');
            }

            if ($confirmation['action'] === 'create_engagement') {
                $repository->createEngagement($confirmation['driver_id'], $confirmation['engagement']);
                unset($_SESSION['admin_driver_pending']);
                $this->redirectWithNotice(
                    'success',
                    'L’engagement de ' . $driverLabel . ' avec ' . $confirmation['engagement']['nom_ecurie'] . ' pour ' . $confirmation['engagement']['annee'] . ' a été enregistré.',
                    '/admin/pilotes/' . $confirmation['driver_id']
                );
            }

            if ($confirmation['action'] === 'update_engagement') {
                $repository->updateEngagement(
                    $confirmation['driver_id'],
                    $confirmation['engagement_id'],
                    $confirmation['engagement']
                );
                unset($_SESSION['admin_driver_pending']);
                $this->redirectWithNotice(
                    'success',
                    'L’engagement de ' . $driverLabel . ' a été mis à jour.',
                    '/admin/pilotes/' . $confirmation['driver_id']
                );
            }

            if ($confirmation['action'] === 'delete_engagement') {
                $repository->deleteEngagement($confirmation['driver_id'], $confirmation['engagement_id']);
                unset($_SESSION['admin_driver_pending']);
                $this->redirectWithNotice(
                    'success',
                    'L’engagement de ' . $driverLabel . ' avec ' . $confirmation['engagement']['nom_ecurie'] . ' pour ' . $confirmation['engagement']['annee'] . ' a été supprimé.',
                    '/admin/pilotes/' . $confirmation['driver_id']
                );
            }

            $this->redirectWithNotice('error', 'Action invalide.');
        } catch (PDOException $exception) {
            error_log('Unable to confirm driver action: ' . $exception->getMessage());
            $this->discardPendingImage(is_array($pending) ? $pending : null);
            unset($_SESSION['admin_driver_pending']);
            $this->redirectWithNotice('error', 'Impossible d’enregistrer cette action pour le moment.');
        }
    }

    private function renderDriver(int $driverId, ?int $engagementId): void
    {
        try {
            $repository = $this->repository();
            $driver = $repository->find($driverId);
            if ($driver === null) {
                ErrorPage::notFound();
            }

            $editedEngagement = null;
            if ($engagementId !== null) {
                $editedEngagement = $repository->findEngagement($driverId, $engagementId);
                if ($editedEngagement === null) {
                    ErrorPage::notFound();
                }
            }

            $this->render(
                [],
                null,
                $driver,
                $repository->findEngagements($driverId),
                $editedEngagement,
                $repository->findSeasons(),
                $repository->findTeamsBySeason()
            );
        } catch (PDOException $exception) {
            error_log('Unable to load driver: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger ce pilote pour le moment.';
        }
    }

    private function queueDriver(?int $driverId): never
    {
        $location = $driverId === null ? '/admin/pilotes' : '/admin/pilotes/' . $driverId;
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_driver_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        try {
            $repository = $this->repository();
            $current = null;
            if ($driverId !== null) {
                $current = $repository->find($driverId);
                if ($current === null) {
                    $this->redirectWithNotice('error', 'Ce pilote est introuvable.', '/admin/pilotes');
                }
            }

            $posted = $this->driverFromPost($location, $driverId === null, $current['photo_pilote'] ?? null);
            $driver = $posted['driver'];
            $imageTemp = $posted['image_temp'];
            if ($imageTemp !== null && $repository->photoTaken($driver['photo_pilote'], $driverId)) {
                $this->discardPendingImage(['image_temp' => $imageTemp]);
                $this->redirectWithNotice('error', 'Cette photo est déjà utilisée par un autre pilote.', $location);
            }
            $previousPhoto = $current['photo_pilote'] ?? null;
            if ($imageTemp !== null && $this->photoFileExists($driver['photo_pilote']) && $driver['photo_pilote'] !== $previousPhoto) {
                $this->discardPendingImage(['image_temp' => $imageTemp]);
                $this->redirectWithNotice('error', 'Une photo porte déjà ce nom.', $location);
            }

            $_SESSION['admin_driver_pending'] = [
                'action' => $driverId === null ? 'create' : 'update',
                'driver_id' => $driverId,
                'driver' => $driver,
                'image_temp' => $imageTemp,
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare driver: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer ce pilote pour le moment.', $location);
        }

        $this->redirectForPending();
    }

    private function queueEngagement(int $driverId, ?int $engagementId): never
    {
        $location = $engagementId === null
            ? '/admin/pilotes/' . $driverId
            : '/admin/pilotes/' . $driverId . '/engagements/' . $engagementId;
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_driver_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        try {
            $repository = $this->repository();
            if ($repository->find($driverId) === null) {
                $this->redirectWithNotice('error', 'Ce pilote est introuvable.', '/admin/pilotes');
            }

            $current = null;
            if ($engagementId !== null) {
                $current = $repository->findEngagement($driverId, $engagementId);
                if ($current === null) {
                    $this->redirectWithNotice('error', 'Cet engagement est introuvable.', '/admin/pilotes/' . $driverId);
                }
            }

            $engagement = $this->engagementFromPost($location);
            if ($repository->findSeason($engagement['saison_id']) === null) {
                $this->redirectWithNotice('error', 'Cette saison est introuvable.', $location);
            }
            if ($repository->findTeamForSeason($engagement['saison_id'], $engagement['ecurie_id']) === null) {
                $this->redirectWithNotice('error', 'Cette écurie n’est pas engagée sur cette saison.', $location);
            }
            if ($repository->engagementExists($driverId, $engagement['saison_id'], $engagement['ecurie_id'], $engagementId)) {
                $this->redirectWithNotice('error', 'Ce pilote a déjà un engagement avec cette écurie pour cette saison.', $location);
            }
            if ($current !== null
                && ($current['saison_id'] !== $engagement['saison_id'] || $current['ecurie_id'] !== $engagement['ecurie_id'])
                && $repository->engagementResultCount($driverId, $current['saison_id'], $current['ecurie_id']) > 0
            ) {
                $this->redirectWithNotice('error', 'Des résultats sont rattachés à cet engagement. La saison et l’écurie ne peuvent plus être modifiées.', $location);
            }

            $_SESSION['admin_driver_pending'] = [
                'action' => $engagementId === null ? 'create_engagement' : 'update_engagement',
                'driver_id' => $driverId,
                'engagement_id' => $engagementId,
                'engagement' => $engagement,
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare engagement: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cet engagement pour le moment.', $location);
        }

        $this->redirectForPending();
    }

    private function render(
        array $drivers,
        ?array $confirmation,
        ?array $driver,
        array $engagements,
        ?array $editedEngagement,
        array $seasons,
        array $teams
    ): void {
        $notice = $_SESSION['admin_driver_notice'] ?? null;
        unset($_SESSION['admin_driver_notice']);

        $titleName = $driver === null ? 'Pilotes' : trim($driver['prenom_pilote'] . ' ' . $driver['nom_pilote']);
        (new ViewRenderer())->render('View/pages/admin/admin-pilotes', [
            'title' => 'BingoF1 - ' . $titleName,
            'drivers' => $drivers,
            'driver' => $driver,
            'engagements' => $engagements,
            'editedEngagement' => $editedEngagement,
            'seasons' => $seasons,
            'teams' => $teams,
            'confirmation' => $confirmation,
            'notice' => $notice,
            'csrfToken' => Csrf::token(),
            'minNumber' => self::MIN_NUMBER,
            'maxNumber' => self::MAX_NUMBER,
            'minYear' => self::MIN_YEAR,
            'maxYear' => self::MAX_YEAR,
            'maxTitles' => self::MAX_TITLES,
            'roleLabels' => [
                'titulaire' => 'Titulaire',
                'reserve' => 'Réserviste',
            ],
        ]);
    }

    private function resolvePending(DriverRepository $repository): ?array
    {
        $pending = $_SESSION['admin_driver_pending'] ?? null;
        if (!is_array($pending)) {
            return null;
        }

        $action = $pending['action'] ?? null;
        if (!is_string($action) || !in_array($action, [...self::DRIVER_ACTIONS, ...self::ENGAGEMENT_ACTIONS], true)) {
            unset($_SESSION['admin_driver_pending']);
            return null;
        }

        if (in_array($action, self::ENGAGEMENT_ACTIONS, true)) {
            return $this->resolveEngagementPending($repository, $pending);
        }

        if ($action === 'delete') {
            return $this->resolveDriverDelete($repository, $pending);
        }

        return $this->resolveDriverPending($repository, $pending);
    }

    private function resolveDriverPending(DriverRepository $repository, array $pending): ?array
    {
        $driver = $pending['driver'] ?? null;
        $imageTemp = $pending['image_temp'] ?? null;
        $isUpdate = ($pending['action'] ?? '') === 'update';
        $driverId = $pending['driver_id'] ?? null;
        $imageIsNew = is_string($imageTemp);
        if (!$this->validDriverPayload($driver) || ($isUpdate && !is_int($driverId)) || (!$isUpdate && $driverId !== null)) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_driver_pending']);
            return null;
        }
        if (!$imageIsNew && !$isUpdate) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_driver_pending']);
            return null;
        }
        if ($imageIsNew && $this->stagedFile($imageTemp) === null) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_driver_pending']);
            return null;
        }

        $current = $isUpdate ? $repository->find($driverId) : null;
        if ($isUpdate && $current === null) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_driver_pending']);
            return null;
        }

        $blocked = false;
        $blockedReason = '';
        if ($imageIsNew && $repository->photoTaken($driver['photo_pilote'], $isUpdate ? $driverId : null)) {
            $blocked = true;
            $blockedReason = 'Cette photo est déjà utilisée par un autre pilote.';
        } elseif ($imageIsNew && $this->photoFileExists($driver['photo_pilote']) && $driver['photo_pilote'] !== ($current['photo_pilote'] ?? null)) {
            $blocked = true;
            $blockedReason = 'Une photo porte déjà ce nom.';
        }

        return [
            'action' => $isUpdate ? 'update' : 'create',
            'driver_id' => $isUpdate ? $driverId : null,
            'engagement_id' => null,
            'driver' => $driver,
            'image_is_new' => $imageIsNew,
            'engagement' => null,
            'usage' => null,
            'blocked' => $blocked,
            'blocked_reason' => $blockedReason,
        ];
    }

    private function resolveDriverDelete(DriverRepository $repository, array $pending): ?array
    {
        $driverId = $pending['driver_id'] ?? null;
        if (!is_int($driverId)) {
            unset($_SESSION['admin_driver_pending']);
            return null;
        }

        $driver = $repository->find($driverId);
        if ($driver === null) {
            unset($_SESSION['admin_driver_pending']);
            return null;
        }

        $usage = $repository->usage($driverId);
        $blocked = array_sum($usage) > 0;

        return [
            'action' => 'delete',
            'driver_id' => $driver['id'],
            'engagement_id' => null,
            'driver' => $driver,
            'image_is_new' => false,
            'engagement' => null,
            'usage' => $usage,
            'blocked' => $blocked,
            'blocked_reason' => $blocked ? 'Des données sont encore rattachées à ce pilote.' : '',
        ];
    }

    private function resolveEngagementPending(DriverRepository $repository, array $pending): ?array
    {
        $driverId = $pending['driver_id'] ?? null;
        $action = $pending['action'] ?? '';
        if (!is_int($driverId)) {
            unset($_SESSION['admin_driver_pending']);
            return null;
        }

        $driver = $repository->find($driverId);
        if ($driver === null) {
            unset($_SESSION['admin_driver_pending']);
            return null;
        }

        if ($action === 'delete_engagement') {
            $engagementId = $pending['engagement_id'] ?? null;
            if (!is_int($engagementId)) {
                unset($_SESSION['admin_driver_pending']);
                return null;
            }
            $current = $repository->findEngagement($driverId, $engagementId);
            if ($current === null) {
                unset($_SESSION['admin_driver_pending']);
                return null;
            }
            $results = $repository->engagementResultCount($driverId, $current['saison_id'], $current['ecurie_id']);

            return [
                'action' => 'delete_engagement',
                'driver_id' => $driverId,
                'engagement_id' => $engagementId,
                'driver' => $driver,
                'image_is_new' => false,
                'engagement' => $current,
                'usage' => ['results_count' => $results],
                'blocked' => $results > 0,
                'blocked_reason' => $results > 0 ? 'Des résultats sont encore rattachés à cet engagement.' : '',
            ];
        }

        $engagement = $pending['engagement'] ?? null;
        $engagementId = $pending['engagement_id'] ?? null;
        $isUpdate = $action === 'update_engagement';
        if (!$this->validEngagementPayload($engagement) || ($isUpdate && !is_int($engagementId))) {
            unset($_SESSION['admin_driver_pending']);
            return null;
        }

        $current = $isUpdate ? $repository->findEngagement($driverId, $engagementId) : null;
        if ($isUpdate && $current === null) {
            unset($_SESSION['admin_driver_pending']);
            return null;
        }

        $season = $repository->findSeason($engagement['saison_id']);
        $team = $season === null ? null : $repository->findTeamForSeason($season['id'], $engagement['ecurie_id']);
        $blocked = false;
        $blockedReason = '';
        if ($season === null) {
            unset($_SESSION['admin_driver_pending']);
            return null;
        }
        if ($team === null) {
            $blocked = true;
            $blockedReason = 'Cette écurie n’est pas engagée sur cette saison.';
        } elseif ($repository->engagementExists($driverId, $season['id'], $engagement['ecurie_id'], $isUpdate ? $engagementId : null)) {
            $blocked = true;
            $blockedReason = 'Ce pilote a déjà un engagement avec cette écurie pour cette saison.';
        } elseif ($current !== null
            && ($current['saison_id'] !== $engagement['saison_id'] || $current['ecurie_id'] !== $engagement['ecurie_id'])
            && $repository->engagementResultCount($driverId, $current['saison_id'], $current['ecurie_id']) > 0
        ) {
            $blocked = true;
            $blockedReason = 'Des résultats sont rattachés à cet engagement. La saison et l’écurie ne peuvent plus être modifiées.';
        }

        $engagement['annee'] = $season['annee'];
        $engagement['nom_ecurie'] = $team['nom_ecurie'] ?? '';
        $engagement['nom_court'] = $team['nom_court'] ?? '';

        return [
            'action' => $isUpdate ? 'update_engagement' : 'create_engagement',
            'driver_id' => $driverId,
            'engagement_id' => $isUpdate ? $engagementId : null,
            'driver' => $driver,
            'image_is_new' => false,
            'engagement' => $engagement,
            'usage' => null,
            'blocked' => $blocked,
            'blocked_reason' => $blockedReason,
        ];
    }

    private function driverFromPost(string $location, bool $photoRequired, ?string $currentPhoto): array
    {
        $firstName = $this->boundedText($_POST['prenom_pilote'] ?? null, 100, true);
        $lastName = $this->boundedText($_POST['nom_pilote'] ?? null, 100, true);
        $nationality = $this->boundedText($_POST['nationalite_pilote'] ?? null, 100, true);
        $number = filter_var($_POST['numero_pilote'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => self::MIN_NUMBER, 'max_range' => self::MAX_NUMBER],
        ]);

        if ($firstName === false || $lastName === false) {
            $this->redirectWithNotice('error', 'Le prénom et le nom sont requis, sur 100 caractères au plus.', $location);
        }
        if ($nationality === false) {
            $this->redirectWithNotice('error', 'La nationalité est requise, sur 100 caractères au plus.', $location);
        }
        if ($number === false) {
            $this->redirectWithNotice('error', 'Le numéro doit être compris entre ' . self::MIN_NUMBER . ' et ' . self::MAX_NUMBER . '.', $location);
        }

        $photo = $this->optionalPhoto($location, $photoRequired);
        if ($photo === null) {
            if ($currentPhoto === null || $currentPhoto === '') {
                $this->redirectWithNotice('error', 'La photo du pilote est requise.', $location);
            }

            return [
                'driver' => [
                    'numero_pilote' => $number,
                    'prenom_pilote' => $firstName,
                    'nom_pilote' => $lastName,
                    'nationalite_pilote' => $nationality,
                    'photo_pilote' => $currentPhoto,
                ],
                'image_temp' => null,
            ];
        }

        return [
            'driver' => [
                'numero_pilote' => $number,
                'prenom_pilote' => $firstName,
                'nom_pilote' => $lastName,
                'nationalite_pilote' => $nationality,
                'photo_pilote' => $photo['path'],
            ],
            'image_temp' => $photo['temp'],
        ];
    }

    private function engagementFromPost(string $location): array
    {
        $seasonId = $this->validateId($_POST['saison_id'] ?? null);
        $teamId = $this->validateId($_POST['ecurie_id'] ?? null);
        $role = $_POST['role'] ?? null;
        $debut = $this->optionalYear($_POST['date_debut'] ?? null, 'L’année de début', $location);
        $end = $this->optionalYear($_POST['date_fin'] ?? null, 'L’année de fin', $location);
        $titles = $this->optionalTitles($_POST['nombre_titre_pilote'] ?? null, $location);

        if ($seasonId === null) {
            $this->redirectWithNotice('error', 'La saison est requise.', $location);
        }
        if ($teamId === null) {
            $this->redirectWithNotice('error', 'L’écurie est requise.', $location);
        }
        if (!is_string($role) || !in_array($role, ['titulaire', 'reserve'], true)) {
            $this->redirectWithNotice('error', 'Le rôle est invalide.', $location);
        }
        if ($debut !== null && $end !== null && $end < $debut) {
            $this->redirectWithNotice('error', 'L’année de fin doit être postérieure ou égale à l’année de début.', $location);
        }

        return [
            'saison_id' => $seasonId,
            'ecurie_id' => $teamId,
            'role' => $role,
            'date_debut' => $debut,
            'date_fin' => $end,
            'nombre_titre_pilote' => $titles,
        ];
    }

    private function validDriverPayload(mixed $driver): bool
    {
        if (!is_array($driver)) {
            return false;
        }

        $number = $driver['numero_pilote'] ?? null;
        $photo = $driver['photo_pilote'] ?? null;
        $filename = is_string($photo) ? $this->photoFilename(basename($photo)) : null;

        return is_int($number)
            && $number >= self::MIN_NUMBER
            && $number <= self::MAX_NUMBER
            && is_string($driver['prenom_pilote'] ?? null)
            && $driver['prenom_pilote'] !== ''
            && mb_strlen($driver['prenom_pilote']) <= 100
            && is_string($driver['nom_pilote'] ?? null)
            && $driver['nom_pilote'] !== ''
            && mb_strlen($driver['nom_pilote']) <= 100
            && is_string($driver['nationalite_pilote'] ?? null)
            && $driver['nationalite_pilote'] !== ''
            && mb_strlen($driver['nationalite_pilote']) <= 100
            && $filename !== null
            && $photo === '/assets/images/pilotes/' . $filename;
    }

    private function validEngagementPayload(mixed $engagement): bool
    {
        if (!is_array($engagement)) {
            return false;
        }

        $role = $engagement['role'] ?? null;
        $debut = $engagement['date_debut'] ?? null;
        $end = $engagement['date_fin'] ?? null;
        $titles = $engagement['nombre_titre_pilote'] ?? null;

        return is_int($engagement['saison_id'] ?? null)
            && is_int($engagement['ecurie_id'] ?? null)
            && ($role === 'titulaire' || $role === 'reserve')
            && $this->validOptionalYear($debut)
            && $this->validOptionalYear($end)
            && ($titles === null || (is_int($titles) && $titles >= 0 && $titles <= self::MAX_TITLES))
            && ($debut === null || $end === null || $end >= $debut);
    }

    private function validOptionalYear(mixed $value): bool
    {
        return $value === null || (is_int($value) && $value >= self::MIN_YEAR && $value <= self::MAX_YEAR);
    }

    private function optionalPhoto(string $location, bool $required): ?array
    {
        $file = $_FILES['photo_pilote'] ?? null;
        $error = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                $this->redirectWithNotice('error', 'La photo du pilote est requise.', $location);
            }

            return null;
        }

        return $this->stagePhoto($location);
    }

    private function stagePhoto(string $location): array
    {
        $file = $_FILES['photo_pilote'] ?? null;
        if (!is_array($file)) {
            $this->redirectWithNotice('error', 'La photo du pilote est requise.', $location);
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            $this->redirectWithNotice('error', 'La photo dépasse la taille autorisée.', $location);
        }
        if ($error !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
            $this->redirectWithNotice('error', 'L’envoi de la photo a échoué.', $location);
        }
        if ((int) ($file['size'] ?? 0) > 2_000_000) {
            $this->redirectWithNotice('error', 'La photo ne doit pas dépasser 2 Mo.', $location);
        }

        $filename = $this->photoFilename(is_string($file['name'] ?? null) ? $file['name'] : '');
        if ($filename === null) {
            $this->redirectWithNotice('error', 'Le fichier doit s’appeler comme norris.png, sans espace ni dossier.', $location);
        }

        $path = '/assets/images/pilotes/' . $filename;
        if (strlen($path) > 100) {
            $this->redirectWithNotice('error', 'Le nom du fichier est trop long.', $location);
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
            $this->redirectWithNotice('error', 'Impossible de préparer la photo pour le moment.', $location);
        }

        $temp = $staging . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $temp)) {
            $this->redirectWithNotice('error', 'Impossible d’enregistrer la photo pour le moment.', $location);
        }

        return ['path' => $path, 'temp' => $temp];
    }

    private function publishPhoto(array $confirmation, ?string $previousPhoto): void
    {
        if (!$confirmation['image_is_new']) {
            return;
        }

        $pending = $_SESSION['admin_driver_pending'] ?? null;
        $path = $confirmation['driver']['photo_pilote'] ?? null;
        $filename = is_string($path) ? $this->photoFilename(basename($path)) : null;
        $tempPath = $this->stagedFile(is_array($pending) ? ($pending['image_temp'] ?? null) : null);
        $location = $this->locationFor(is_array($pending) ? $pending : []);
        if ($filename === null || $tempPath === null || $path !== '/assets/images/pilotes/' . $filename) {
            $this->discardPendingImage(is_array($pending) ? $pending : null);
            unset($_SESSION['admin_driver_pending']);
            $this->redirectWithNotice('error', 'La photo envoyée n’est plus disponible.', $location);
        }

        $directory = $this->photosDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $this->redirectWithNotice('error', 'Impossible d’enregistrer la photo pour le moment.', $location);
        }

        $destination = $directory . DIRECTORY_SEPARATOR . $filename;
        if (is_file($destination) && $previousPhoto !== $path) {
            $this->discardPendingImage(is_array($pending) ? $pending : null);
            unset($_SESSION['admin_driver_pending']);
            $this->redirectWithNotice('error', 'Une photo porte déjà ce nom.', $location);
        }
        if (is_file($destination) && !unlink($destination)) {
            $this->redirectWithNotice('error', 'Impossible d’enregistrer la photo pour le moment.', $location);
        }

        if (!rename($tempPath, $destination)) {
            if (!copy($tempPath, $destination) || !unlink($tempPath)) {
                $this->redirectWithNotice('error', 'Impossible d’enregistrer la photo pour le moment.', $location);
            }
        }
    }

    private function photoFilename(string $name): ?string
    {
        $filename = basename(str_replace('\\', '/', $name));
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,70}\.(png|jpe?g|webp|gif)$/', $filename)) {
            return null;
        }

        return $filename;
    }

    private function photoFileExists(string $path): bool
    {
        $filename = $this->photoFilename(basename($path));
        if ($filename === null || $path !== '/assets/images/pilotes/' . $filename) {
            return false;
        }

        return is_file($this->photosDirectory() . DIRECTORY_SEPARATOR . $filename);
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

    private function deletePublishedPhoto(string $path): void
    {
        $filename = $this->photoFilename(basename($path));
        if ($filename === null || $path !== '/assets/images/pilotes/' . $filename) {
            return;
        }

        $file = $this->photosDirectory() . DIRECTORY_SEPARATOR . $filename;
        if (is_file($file)) {
            unlink($file);
        }
    }

    private function photosDirectory(): string
    {
        return dirname(__DIR__, 2) . '/public/assets/images/pilotes';
    }

    private function stagingDirectory(): string
    {
        return sys_get_temp_dir() . '/bingof1-driver-uploads';
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

    private function optionalYear(mixed $value, string $label, string $location): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        $year = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => self::MIN_YEAR, 'max_range' => self::MAX_YEAR],
        ]);
        if ($year === false) {
            $this->redirectWithNotice('error', $label . ' doit être comprise entre ' . self::MIN_YEAR . ' et ' . self::MAX_YEAR . '.', $location);
        }

        return $year;
    }

    private function optionalTitles(mixed $value, string $location): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        $titles = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => self::MAX_TITLES],
        ]);
        if ($titles === false) {
            $this->redirectWithNotice('error', 'Le nombre de titres doit être compris entre 0 et ' . self::MAX_TITLES . '.', $location);
        }

        return $titles;
    }

    private function redirectIfPendingBelongsElsewhere(?int $driverId, bool $leaveEditor): void
    {
        $pending = $_SESSION['admin_driver_pending'] ?? null;
        if (!is_array($pending)) {
            return;
        }

        $target = $this->locationFor($pending);
        $here = $driverId === null ? '/admin/pilotes' : '/admin/pilotes/' . $driverId;
        if ($leaveEditor || $here !== $target) {
            header('Location: ' . $target);
            exit();
        }
    }

    private function locationFor(array $pending): string
    {
        $action = $pending['action'] ?? '';
        $driverId = $pending['driver_id'] ?? null;
        if (in_array($action, ['update', 'delete', ...self::ENGAGEMENT_ACTIONS], true) && is_int($driverId)) {
            return '/admin/pilotes/' . $driverId;
        }

        return '/admin/pilotes';
    }

    private function validateId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function driverLabel(array $driver): string
    {
        return trim($driver['prenom_pilote'] . ' ' . $driver['nom_pilote']);
    }

    private function repository(): DriverRepository
    {
        return new DriverRepository((new Database())->getConnection());
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
        $_SESSION['admin_driver_notice'] = ['type' => $type, 'message' => $message];
        if ($location !== null) {
            header('Location: ' . $location);
            exit();
        }

        $pending = $_SESSION['admin_driver_pending'] ?? null;
        header('Location: ' . (is_array($pending) ? $this->locationFor($pending) : '/admin/pilotes'));
        exit();
    }

    private function redirectForPending(): never
    {
        $pending = $_SESSION['admin_driver_pending'] ?? null;
        header('Location: ' . (is_array($pending) ? $this->locationFor($pending) : '/admin/pilotes'));
        exit();
    }
}
