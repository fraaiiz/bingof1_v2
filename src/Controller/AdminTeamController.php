<?php

namespace App\Controller;

use App\Service\ErrorPage;
use App\Database\Database;
use App\Repository\TeamRepository;
use App\Service\Csrf;
use App\View\ViewRenderer;
use PDOException;

class AdminTeamController
{
    private const MIN_YEAR = 1800;
    private const MAX_YEAR = 2155;
    private const MAX_TITLES = 999;

    private const TEAM_ACTIONS = ['create', 'update', 'delete'];
    private const ENTRY_ACTIONS = ['create_entry', 'update_entry', 'delete_entry'];

    public function index(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $this->redirectIfPendingBelongsElsewhere(null, false);

        try {
            $repository = $this->repository();
            $this->render($repository->findAll(), $this->resolvePending($repository), null, [], null, [], []);
        } catch (PDOException $exception) {
            error_log('Unable to load teams: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger les écuries pour le moment.';
        }
    }

    public function show(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $teamId = $this->validateId($id);
        if ($teamId === null) {
            ErrorPage::notFound();
        }

        $this->redirectIfPendingBelongsElsewhere($teamId, false);

        try {
            $repository = $this->repository();
            $team = $repository->find($teamId);
            if ($team === null) {
                ErrorPage::notFound();
            }

            $this->render(
                [],
                $this->resolvePending($repository),
                $team,
                $repository->findEntries($teamId),
                null,
                $repository->findSeasons(),
                $repository->findMotoristes()
            );
        } catch (PDOException $exception) {
            error_log('Unable to load team: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger cette écurie pour le moment.';
        }
    }

    public function editEntry(string $id, string $entryId): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $teamId = $this->validateId($id);
        $editedEntryId = $this->validateId($entryId);
        if ($teamId === null || $editedEntryId === null) {
            ErrorPage::notFound();
        }

        $this->redirectIfPendingBelongsElsewhere($teamId, true);
        $this->renderTeam($teamId, $editedEntryId);
    }

    public function prepareCreate(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $this->queueTeam(null);
    }

    public function prepareUpdate(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $teamId = $this->validateId($id);
        if ($teamId === null) {
            ErrorPage::notFound();
        }

        $this->queueTeam($teamId);
    }

    public function prepareDelete(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $teamId = $this->validateId($id);
        $location = '/admin/ecuries/' . (int) $teamId;
        if ($teamId === null) {
            ErrorPage::notFound();
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_team_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        try {
            if ($this->repository()->find($teamId) === null) {
                $this->redirectWithNotice('error', 'Cette écurie est introuvable.', '/admin/ecuries');
            }

            $_SESSION['admin_team_pending'] = [
                'action' => 'delete',
                'team_id' => $teamId,
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare team deletion: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cette suppression pour le moment.', $location);
        }

        $this->redirectForPending();
    }

    public function prepareEntry(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $teamId = $this->validateId($id);
        if ($teamId === null) {
            ErrorPage::notFound();
        }

        $this->queueEntry($teamId, null);
    }

    public function prepareEntryUpdate(string $id, string $entryId): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $teamId = $this->validateId($id);
        $editedEntryId = $this->validateId($entryId);
        if ($teamId === null || $editedEntryId === null) {
            ErrorPage::notFound();
        }

        $this->queueEntry($teamId, $editedEntryId);
    }

    public function prepareEntryDelete(string $id, string $entryId): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $teamId = $this->validateId($id);
        $deletedEntryId = $this->validateId($entryId);
        $location = '/admin/ecuries/' . (int) $teamId;
        if ($teamId === null || $deletedEntryId === null) {
            ErrorPage::notFound();
        }

        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_team_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        try {
            if ($this->repository()->findEntry($teamId, $deletedEntryId) === null) {
                $this->redirectWithNotice('error', 'Cette inscription est introuvable.', $location);
            }

            $_SESSION['admin_team_pending'] = [
                'action' => 'delete_entry',
                'team_id' => $teamId,
                'entry_id' => $deletedEntryId,
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare team entry deletion: ' . $exception->getMessage());
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
        $pending = $_SESSION['admin_team_pending'] ?? null;
        if ($decision === 'cancel') {
            $this->discardPendingImage(is_array($pending) ? $pending : null);
            $location = is_array($pending) ? $this->locationFor($pending) : '/admin/ecuries';
            unset($_SESSION['admin_team_pending']);
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

            $name = $confirmation['team']['nom_ecurie'];
            if ($confirmation['action'] === 'create') {
                $repository->create($confirmation['team']);
                unset($_SESSION['admin_team_pending']);
                $this->redirectWithNotice('success', $name . ' a été créée.', '/admin/ecuries');
            }

            if ($confirmation['action'] === 'update') {
                if ($repository->find($confirmation['team_id']) === null) {
                    unset($_SESSION['admin_team_pending']);
                    $this->redirectWithNotice('error', 'Cette écurie est introuvable.', '/admin/ecuries');
                }
                $repository->update($confirmation['team_id'], $confirmation['team']);
                unset($_SESSION['admin_team_pending']);
                $this->redirectWithNotice('success', $name . ' a été mise à jour.', '/admin/ecuries/' . $confirmation['team_id']);
            }

            if ($confirmation['action'] === 'delete') {
                $repository->delete($confirmation['team_id']);
                unset($_SESSION['admin_team_pending']);
                $this->redirectWithNotice('success', $name . ' a été supprimée.', '/admin/ecuries');
            }

            if ($confirmation['action'] === 'create_entry' || $confirmation['action'] === 'update_entry') {
                $previous = $confirmation['action'] === 'update_entry'
                    ? $repository->findEntry($confirmation['team_id'], $confirmation['entry_id'])
                    : null;
                if ($confirmation['action'] === 'update_entry' && $previous === null) {
                    $this->discardPendingImage(is_array($pending) ? $pending : null);
                    unset($_SESSION['admin_team_pending']);
                    $this->redirectWithNotice('error', 'Cette inscription est introuvable.', '/admin/ecuries/' . $confirmation['team_id']);
                }

                $created = [];
                try {
                    $created = $this->publishNewImages($confirmation, $previous);
                    if ($confirmation['action'] === 'create_entry') {
                        $repository->createEntry($confirmation['team_id'], $confirmation['entry']);
                    } else {
                        $repository->updateEntry($confirmation['team_id'], $confirmation['entry_id'], $confirmation['entry']);
                    }
                } catch (PDOException $exception) {
                    $this->rollbackPublished($created);
                    throw $exception;
                }

                unset($_SESSION['admin_team_pending']);
                $this->redirectWithNotice(
                    'success',
                    $confirmation['action'] === 'create_entry'
                        ? 'L’inscription de ' . $name . ' pour ' . $confirmation['entry']['annee'] . ' a été enregistrée.'
                        : 'L’inscription de ' . $name . ' pour ' . $confirmation['entry']['annee'] . ' a été mise à jour.',
                    '/admin/ecuries/' . $confirmation['team_id']
                );
            }

            if ($confirmation['action'] === 'delete_entry') {
                $repository->deleteEntry($confirmation['team_id'], $confirmation['entry_id']);
                unset($_SESSION['admin_team_pending']);
                $this->redirectWithNotice(
                    'success',
                    'L’inscription de ' . $name . ' pour ' . $confirmation['entry']['annee'] . ' a été supprimée.',
                    '/admin/ecuries/' . $confirmation['team_id']
                );
            }

            $this->redirectWithNotice('error', 'Action invalide.');
        } catch (PDOException $exception) {
            error_log('Unable to confirm team action: ' . $exception->getMessage());
            $this->discardPendingImage(is_array($pending) ? $pending : null);
            unset($_SESSION['admin_team_pending']);
            $this->redirectWithNotice('error', 'Impossible d’enregistrer cette action pour le moment.');
        }
    }

    private function renderTeam(int $teamId, ?int $entryId): void
    {
        try {
            $repository = $this->repository();
            $team = $repository->find($teamId);
            $editedEntry = $team === null || $entryId === null ? null : $repository->findEntry($teamId, $entryId);
            if ($team === null || $editedEntry === null) {
                ErrorPage::notFound();
            }

            $this->render(
                [],
                null,
                $team,
                $repository->findEntries($teamId),
                $editedEntry,
                $repository->findSeasons(),
                $repository->findMotoristes()
            );
        } catch (PDOException $exception) {
            error_log('Unable to load team entry: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Impossible de charger cette inscription pour le moment.';
        }
    }

    private function queueTeam(?int $teamId): never
    {
        $location = $teamId === null ? '/admin/ecuries' : '/admin/ecuries/' . $teamId;
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_team_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        try {
            if ($teamId !== null && $this->repository()->find($teamId) === null) {
                $this->redirectWithNotice('error', 'Cette écurie est introuvable.', '/admin/ecuries');
            }

            $_SESSION['admin_team_pending'] = [
                'action' => $teamId === null ? 'create' : 'update',
                'team_id' => $teamId,
                'team' => $this->teamFromPost($location),
            ];
        } catch (PDOException $exception) {
            error_log('Unable to prepare team: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cette écurie pour le moment.', $location);
        }

        $this->redirectForPending();
    }

    private function queueEntry(int $teamId, ?int $entryId): never
    {
        $location = $entryId === null
            ? '/admin/ecuries/' . $teamId
            : '/admin/ecuries/' . $teamId . '/saisons/' . $entryId;
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->redirectWithNotice('error', 'Requête invalide.', $location);
        }
        if (isset($_SESSION['admin_team_pending'])) {
            $this->redirectWithNotice('error', 'Confirme ou annule l’action en cours avant d’en lancer une autre.');
        }

        $posted = null;
        try {
            $repository = $this->repository();
            if ($repository->find($teamId) === null) {
                $this->redirectWithNotice('error', 'Cette écurie est introuvable.', '/admin/ecuries');
            }

            $current = null;
            if ($entryId !== null) {
                $current = $repository->findEntry($teamId, $entryId);
                if ($current === null) {
                    $this->redirectWithNotice('error', 'Cette inscription est introuvable.', '/admin/ecuries/' . $teamId);
                }
            }

            $baseline = $current ?? ($repository->findEntries($teamId)[0] ?? null);
            $posted = $this->entryFromPost($location, $baseline);
            $entry = $posted['entry'];
            if ($repository->findSeason($entry['saison_id']) === null) {
                $this->discardPendingImage($posted);
                $this->redirectWithNotice('error', 'Cette saison est introuvable.', $location);
            }
            if ($repository->findMotoriste($entry['motoriste_id']) === null) {
                $this->discardPendingImage($posted);
                $this->redirectWithNotice('error', 'Ce motoriste est introuvable.', $location);
            }
            if ($repository->entryExists($teamId, $entry['saison_id'], $entryId)) {
                $this->discardPendingImage($posted);
                $this->redirectWithNotice('error', 'Cette écurie est déjà inscrite à cette saison.', $location);
            }
            if ($current !== null
                && $current['saison_id'] !== $entry['saison_id']
                && array_sum($repository->entryUsage($teamId, $current['saison_id'])) > 0
            ) {
                $this->discardPendingImage($posted);
                $this->redirectWithNotice('error', 'Des pilotes ou des résultats sont rattachés à cette inscription. La saison ne peut plus être modifiée.', $location);
            }
            foreach (['logo' => 'logo_temp', 'voiture' => 'car_temp'] as $kind => $tempKey) {
                if ($posted[$tempKey] === null) {
                    continue;
                }
                $previousPath = $current[$kind] ?? null;
                if (is_string($entry[$kind]) && $this->imageFileExists($kind, $entry[$kind]) && $entry[$kind] !== $previousPath) {
                    $this->discardPendingImage($posted);
                    $this->redirectWithNotice('error', 'Une image porte déjà ce nom.', $location);
                }
            }

            $_SESSION['admin_team_pending'] = [
                'action' => $entryId === null ? 'create_entry' : 'update_entry',
                'team_id' => $teamId,
                'entry_id' => $entryId,
                'entry' => $entry,
                'logo_temp' => $posted['logo_temp'],
                'car_temp' => $posted['car_temp'],
            ];
        } catch (PDOException $exception) {
            $this->discardPendingImage($posted);
            error_log('Unable to prepare team entry: ' . $exception->getMessage());
            $this->redirectWithNotice('error', 'Impossible de préparer cette inscription pour le moment.', $location);
        }

        $this->redirectForPending();
    }

    private function render(
        array $teams,
        ?array $confirmation,
        ?array $team,
        array $entries,
        ?array $editedEntry,
        array $seasons,
        array $motoristes
    ): void {
        $notice = $_SESSION['admin_team_notice'] ?? null;
        unset($_SESSION['admin_team_notice']);

        (new ViewRenderer())->render('View/pages/admin/admin-ecuries', [
            'title' => 'BingoF1 - ' . ($team === null ? 'Écuries' : $team['nom_ecurie']),
            'teams' => $teams,
            'team' => $team,
            'entries' => $entries,
            'editedEntry' => $editedEntry,
            'seasons' => $seasons,
            'motoristes' => $motoristes,
            'confirmation' => $confirmation,
            'notice' => $notice,
            'csrfToken' => Csrf::token(),
            'minYear' => self::MIN_YEAR,
            'maxYear' => self::MAX_YEAR,
            'maxTitles' => self::MAX_TITLES,
        ]);
    }

    private function resolvePending(TeamRepository $repository): ?array
    {
        $pending = $_SESSION['admin_team_pending'] ?? null;
        if (!is_array($pending)) {
            return null;
        }

        $action = $pending['action'] ?? null;
        if (!is_string($action) || !in_array($action, [...self::TEAM_ACTIONS, ...self::ENTRY_ACTIONS], true)) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_team_pending']);
            return null;
        }

        if (in_array($action, self::ENTRY_ACTIONS, true)) {
            return $this->resolveEntryPending($repository, $pending);
        }

        if ($action === 'delete') {
            return $this->resolveTeamDelete($repository, $pending);
        }

        return $this->resolveTeamPending($repository, $pending);
    }

    private function resolveTeamPending(TeamRepository $repository, array $pending): ?array
    {
        $team = $pending['team'] ?? null;
        $isUpdate = ($pending['action'] ?? '') === 'update';
        $teamId = $pending['team_id'] ?? null;
        if (!$this->validTeamPayload($team) || ($isUpdate && !is_int($teamId)) || (!$isUpdate && $teamId !== null)) {
            unset($_SESSION['admin_team_pending']);
            return null;
        }
        if ($isUpdate && $repository->find($teamId) === null) {
            unset($_SESSION['admin_team_pending']);
            return null;
        }

        return [
            'action' => $isUpdate ? 'update' : 'create',
            'team_id' => $isUpdate ? $teamId : null,
            'entry_id' => null,
            'team' => $team,
            'logo_is_new' => false,
            'car_is_new' => false,
            'entry' => null,
            'usage' => null,
            'blocked' => false,
            'blocked_reason' => '',
        ];
    }

    private function resolveTeamDelete(TeamRepository $repository, array $pending): ?array
    {
        $teamId = $pending['team_id'] ?? null;
        if (!is_int($teamId)) {
            unset($_SESSION['admin_team_pending']);
            return null;
        }

        $team = $repository->find($teamId);
        if ($team === null) {
            unset($_SESSION['admin_team_pending']);
            return null;
        }

        $usage = $repository->usage($teamId);

        return [
            'action' => 'delete',
            'team_id' => $team['id'],
            'entry_id' => null,
            'team' => $team,
            'logo_is_new' => false,
            'car_is_new' => false,
            'entry' => null,
            'usage' => $usage,
            'blocked' => array_sum($usage) > 0,
            'blocked_reason' => array_sum($usage) > 0 ? 'Des données sont encore rattachées à cette écurie.' : '',
        ];
    }

    private function resolveEntryPending(TeamRepository $repository, array $pending): ?array
    {
        $teamId = $pending['team_id'] ?? null;
        $action = $pending['action'] ?? '';
        if (!is_int($teamId)) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_team_pending']);
            return null;
        }

        $team = $repository->find($teamId);
        if ($team === null) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_team_pending']);
            return null;
        }

        if ($action === 'delete_entry') {
            $entryId = $pending['entry_id'] ?? null;
            if (!is_int($entryId)) {
                unset($_SESSION['admin_team_pending']);
                return null;
            }
            $current = $repository->findEntry($teamId, $entryId);
            if ($current === null) {
                unset($_SESSION['admin_team_pending']);
                return null;
            }
            $usage = $repository->entryUsage($teamId, $current['saison_id']);

            return [
                'action' => 'delete_entry',
                'team_id' => $teamId,
                'entry_id' => $entryId,
                'team' => $team,
                'logo_is_new' => false,
                'car_is_new' => false,
                'entry' => $current,
                'usage' => $usage,
                'blocked' => array_sum($usage) > 0,
                'blocked_reason' => array_sum($usage) > 0 ? 'Des pilotes ou des résultats sont encore rattachés à cette inscription.' : '',
            ];
        }

        $entry = $pending['entry'] ?? null;
        $entryId = $pending['entry_id'] ?? null;
        $isUpdate = $action === 'update_entry';
        $logoTemp = $pending['logo_temp'] ?? null;
        $carTemp = $pending['car_temp'] ?? null;
        $logoIsNew = is_string($logoTemp);
        $carIsNew = is_string($carTemp);
        if (!$this->validEntryPayload($entry) || ($isUpdate && !is_int($entryId))) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_team_pending']);
            return null;
        }
        if (($logoIsNew && $this->stagedFile($logoTemp) === null) || ($carIsNew && $this->stagedFile($carTemp) === null)) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_team_pending']);
            return null;
        }

        $current = $isUpdate ? $repository->findEntry($teamId, $entryId) : null;
        if ($isUpdate && $current === null) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_team_pending']);
            return null;
        }

        $season = $repository->findSeason($entry['saison_id']);
        $engine = $repository->findMotoriste($entry['motoriste_id']);
        if ($season === null) {
            $this->discardPendingImage($pending);
            unset($_SESSION['admin_team_pending']);
            return null;
        }

        $blocked = false;
        $blockedReason = '';
        if ($engine === null) {
            $blocked = true;
            $blockedReason = 'Ce motoriste est introuvable.';
        } elseif ($repository->entryExists($teamId, $season['id'], $isUpdate ? $entryId : null)) {
            $blocked = true;
            $blockedReason = 'Cette écurie est déjà inscrite à cette saison.';
        } elseif ($current !== null
            && $current['saison_id'] !== $entry['saison_id']
            && array_sum($repository->entryUsage($teamId, $current['saison_id'])) > 0
        ) {
            $blocked = true;
            $blockedReason = 'Des pilotes ou des résultats sont rattachés à cette inscription. La saison ne peut plus être modifiée.';
        } elseif (($logoIsNew && is_string($entry['logo']) && $this->imageFileExists('logo', $entry['logo']) && $entry['logo'] !== ($current['logo'] ?? null))
            || ($carIsNew && is_string($entry['voiture']) && $this->imageFileExists('voiture', $entry['voiture']) && $entry['voiture'] !== ($current['voiture'] ?? null))
        ) {
            $blocked = true;
            $blockedReason = 'Une image porte déjà ce nom.';
        }

        $entry['annee'] = $season['annee'];
        $entry['nom_motoriste'] = $engine['nom_motoriste'] ?? '';
        $entry['pays_motoriste'] = $engine['pays_motoriste'] ?? '';

        return [
            'action' => $isUpdate ? 'update_entry' : 'create_entry',
            'team_id' => $teamId,
            'entry_id' => $isUpdate ? $entryId : null,
            'team' => $team,
            'logo_is_new' => $logoIsNew,
            'car_is_new' => $carIsNew,
            'entry' => $entry,
            'usage' => null,
            'blocked' => $blocked,
            'blocked_reason' => $blockedReason,
        ];
    }

    private function teamFromPost(string $location): array
    {
        $name = $this->boundedText($_POST['nom_ecurie'] ?? null, 100, true);
        $shortName = $this->boundedText($_POST['nom_court'] ?? null, 50, true);
        $nationality = $this->boundedText($_POST['nationalite_ecurie'] ?? null, 100, true);
        $year = filter_var($_POST['annee_creation'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => self::MIN_YEAR, 'max_range' => self::MAX_YEAR],
        ]);

        if ($name === false) {
            $this->redirectWithNotice('error', 'Le nom est requis, sur 100 caractères au plus.', $location);
        }
        if ($shortName === false) {
            $this->redirectWithNotice('error', 'Le nom court est requis, sur 50 caractères au plus.', $location);
        }
        if ($nationality === false) {
            $this->redirectWithNotice('error', 'La nationalité est requise, sur 100 caractères au plus.', $location);
        }
        if ($year === false) {
            $this->redirectWithNotice('error', 'L’année de création doit être comprise entre ' . self::MIN_YEAR . ' et ' . self::MAX_YEAR . '.', $location);
        }

        return [
            'nom_ecurie' => $name,
            'nom_court' => $shortName,
            'nationalite_ecurie' => $nationality,
            'annee_creation' => $year,
        ];
    }

    private function entryFromPost(string $location, ?array $current): array
    {
        $seasonId = $this->validateId($_POST['saison_id'] ?? null);
        $engineId = $this->validateId($_POST['motoriste_id'] ?? null);
        $titles = $this->optionalTitles($_POST['nombre_titre'] ?? null, $location);
        if ($seasonId === null) {
            $this->redirectWithNotice('error', 'La saison est requise.', $location);
        }
        if ($engineId === null) {
            $this->redirectWithNotice('error', 'Le motoriste est requis.', $location);
        }

        $staged = [];
        $logo = $this->optionalImage('logo', 'logo', $location, false, $staged);
        $car = $this->optionalImage('voiture', 'voiture', $location, false, $staged);

        return [
            'entry' => [
                'saison_id' => $seasonId,
                'motoriste_id' => $engineId,
                'logo' => $logo['path'] ?? ($current['logo'] ?? null),
                'voiture' => $car['path'] ?? ($current['voiture'] ?? null),
                'nombre_titre' => $titles,
            ],
            'logo_temp' => $logo['temp'] ?? null,
            'car_temp' => $car['temp'] ?? null,
        ];
    }

    private function validTeamPayload(mixed $team): bool
    {
        if (!is_array($team)) {
            return false;
        }

        $year = $team['annee_creation'] ?? null;

        return is_string($team['nom_ecurie'] ?? null)
            && $team['nom_ecurie'] !== ''
            && mb_strlen($team['nom_ecurie']) <= 100
            && is_string($team['nom_court'] ?? null)
            && $team['nom_court'] !== ''
            && mb_strlen($team['nom_court']) <= 50
            && is_string($team['nationalite_ecurie'] ?? null)
            && $team['nationalite_ecurie'] !== ''
            && mb_strlen($team['nationalite_ecurie']) <= 100
            && is_int($year)
            && $year >= self::MIN_YEAR
            && $year <= self::MAX_YEAR;
    }

    private function validEntryPayload(mixed $entry): bool
    {
        if (!is_array($entry)) {
            return false;
        }

        $titles = $entry['nombre_titre'] ?? null;

        return is_int($entry['saison_id'] ?? null)
            && is_int($entry['motoriste_id'] ?? null)
            && $this->optionalImagePath('logo', $entry['logo'] ?? null)
            && $this->optionalImagePath('voiture', $entry['voiture'] ?? null)
            && ($titles === null || (is_int($titles) && $titles >= 0 && $titles <= self::MAX_TITLES));
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

    private function optionalImage(string $field, string $kind, string $location, bool $required, array &$staged): ?array
    {
        $file = $_FILES[$field] ?? null;
        $error = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                $this->discardTemps($staged);
                $this->redirectWithNotice('error', ($kind === 'logo' ? 'Le logo' : 'La photo de la voiture') . ' est requis.', $location);
            }

            return null;
        }

        $image = $this->stageImage($field, $kind, $location, $staged);
        $staged[] = $image['temp'];

        return $image;
    }

    private function stageImage(string $field, string $kind, string $location, array $staged): array
    {
        $file = $_FILES[$field] ?? null;
        if (!is_array($file)) {
            $this->discardTemps($staged);
            $this->redirectWithNotice('error', 'L’envoi de l’image a échoué.', $location);
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            $this->discardTemps($staged);
            $this->redirectWithNotice('error', 'L’image dépasse la taille autorisée.', $location);
        }
        if ($error !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
            $this->discardTemps($staged);
            $this->redirectWithNotice('error', 'L’envoi de l’image a échoué.', $location);
        }
        if ((int) ($file['size'] ?? 0) > 2_000_000) {
            $this->discardTemps($staged);
            $this->redirectWithNotice('error', 'L’image ne doit pas dépasser 2 Mo.', $location);
        }

        $filename = $this->imageFilename(is_string($file['name'] ?? null) ? $file['name'] : '');
        if ($filename === null) {
            $this->discardTemps($staged);
            $this->redirectWithNotice('error', 'Le fichier doit s’appeler comme ferrari.png, sans espace ni dossier.', $location);
        }

        $path = $this->imagePrefix($kind) . $filename;
        if (strlen($path) > 255) {
            $this->discardTemps($staged);
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
            $this->discardTemps($staged);
            $this->redirectWithNotice('error', 'Le fichier doit être une image PNG, JPEG, WEBP ou GIF.', $location);
        }

        $directory = $this->stagingDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $this->discardTemps($staged);
            $this->redirectWithNotice('error', 'Impossible de préparer l’image pour le moment.', $location);
        }

        $temp = $directory . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $temp)) {
            $this->discardTemps($staged);
            $this->redirectWithNotice('error', 'Impossible d’enregistrer l’image pour le moment.', $location);
        }

        return ['path' => $path, 'temp' => $temp];
    }

    private function publishNewImages(array $confirmation, ?array $previous): array
    {
        $pending = $_SESSION['admin_team_pending'] ?? null;
        $jobs = [];
        foreach (['logo' => 'logo_temp', 'voiture' => 'car_temp'] as $kind => $tempKey) {
            $flag = $kind === 'logo' ? 'logo_is_new' : 'car_is_new';
            if (empty($confirmation[$flag])) {
                continue;
            }

            $path = $confirmation['entry'][$kind] ?? null;
            $filename = is_string($path) ? $this->imageFilename(basename($path)) : null;
            $tempPath = $this->stagedFile(is_array($pending) ? ($pending[$tempKey] ?? null) : null);
            $previousPath = $previous[$kind] ?? null;
            if ($filename === null || $tempPath === null || $path !== $this->imagePrefix($kind) . $filename) {
                $this->rollbackPublished([]);
                $this->failImage('L’image envoyée n’est plus disponible.', is_array($pending) ? $pending : null);
            }
            if ($this->imageFileExists($kind, $path) && $previousPath !== $path) {
                $this->failImage('Une image porte déjà ce nom.', is_array($pending) ? $pending : null);
            }

            $jobs[] = [
                'kind' => $kind,
                'temp' => $tempPath,
                'path' => $path,
                'filename' => $filename,
                'created' => $previousPath !== $path,
            ];
        }

        $created = [];
        foreach ($jobs as $job) {
            $directory = $this->imageDirectory($job['kind']);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                $this->rollbackPublished($created);
                $this->failImage('Impossible d’enregistrer l’image pour le moment.', is_array($pending) ? $pending : null);
            }

            $destination = $directory . DIRECTORY_SEPARATOR . $job['filename'];
            if (is_file($destination) && !unlink($destination)) {
                $this->rollbackPublished($created);
                $this->failImage('Impossible d’enregistrer l’image pour le moment.', is_array($pending) ? $pending : null);
            }
            if (!rename($job['temp'], $destination)) {
                if (!copy($job['temp'], $destination) || !unlink($job['temp'])) {
                    $this->rollbackPublished($created);
                    $this->failImage('Impossible d’enregistrer l’image pour le moment.', is_array($pending) ? $pending : null);
                }
            }
            if ($job['created']) {
                $created[] = $job['path'];
            }
        }

        return $created;
    }

    private function failImage(string $message, ?array $pending): never
    {
        $location = is_array($pending) ? $this->locationFor($pending) : '/admin/ecuries';
        $this->discardPendingImage($pending);
        unset($_SESSION['admin_team_pending']);
        $this->redirectWithNotice('error', $message, $location);
    }

    private function imagePrefix(string $kind): string
    {
        return $kind === 'logo' ? '/assets/images/logos/' : '/assets/images/voitures/';
    }

    private function imageDirectory(string $kind): string
    {
        return dirname(__DIR__, 2) . '/public/assets/images/' . ($kind === 'logo' ? 'logos' : 'voitures');
    }

    private function imageFilename(string $name): ?string
    {
        $filename = basename(str_replace('\\', '/', $name));
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,70}\.(png|jpe?g|webp|gif)$/', $filename)) {
            return null;
        }

        return $filename;
    }

    private function optionalImagePath(string $kind, mixed $path): bool
    {
        return $path === null || $this->isImagePath($kind, $path);
    }

    private function isImagePath(string $kind, mixed $path): bool
    {
        $filename = is_string($path) ? $this->imageFilename(basename($path)) : null;

        return $filename !== null && $path === $this->imagePrefix($kind) . $filename;
    }

    private function imageFileExists(string $kind, string $path): bool
    {
        if (!$this->isImagePath($kind, $path)) {
            return false;
        }

        return is_file($this->imageDirectory($kind) . DIRECTORY_SEPARATOR . basename($path));
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

        $this->discardTemps([
            $pending['logo_temp'] ?? null,
            $pending['car_temp'] ?? null,
        ]);
    }

    private function discardTemps(array $temps): void
    {
        foreach ($temps as $temp) {
            $file = $this->stagedFile($temp);
            if ($file !== null) {
                unlink($file);
            }
        }
    }

    private function rollbackPublished(array $paths): void
    {
        foreach ($paths as $path) {
            if (!is_string($path)) {
                continue;
            }
            foreach (['logo', 'voiture'] as $kind) {
                if ($this->isImagePath($kind, $path)) {
                    $file = $this->imageDirectory($kind) . DIRECTORY_SEPARATOR . basename($path);
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }

    private function stagingDirectory(): string
    {
        return sys_get_temp_dir() . '/bingof1-team-uploads';
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

    private function redirectIfPendingBelongsElsewhere(?int $teamId, bool $leaveEditor): void
    {
        $pending = $_SESSION['admin_team_pending'] ?? null;
        if (!is_array($pending)) {
            return;
        }

        $target = $this->locationFor($pending);
        $here = $teamId === null ? '/admin/ecuries' : '/admin/ecuries/' . $teamId;
        if ($leaveEditor || $here !== $target) {
            header('Location: ' . $target);
            exit();
        }
    }

    private function locationFor(array $pending): string
    {
        $action = $pending['action'] ?? '';
        $teamId = $pending['team_id'] ?? null;
        if (in_array($action, ['update', 'delete', ...self::ENTRY_ACTIONS], true) && is_int($teamId)) {
            return '/admin/ecuries/' . $teamId;
        }

        return '/admin/ecuries';
    }

    private function validateId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function repository(): TeamRepository
    {
        return new TeamRepository((new Database())->getConnection());
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
        $_SESSION['admin_team_notice'] = ['type' => $type, 'message' => $message];
        if ($location !== null) {
            header('Location: ' . $location);
            exit();
        }

        $pending = $_SESSION['admin_team_pending'] ?? null;
        header('Location: ' . (is_array($pending) ? $this->locationFor($pending) : '/admin/ecuries'));
        exit();
    }

    private function redirectForPending(): never
    {
        $pending = $_SESSION['admin_team_pending'] ?? null;
        header('Location: ' . (is_array($pending) ? $this->locationFor($pending) : '/admin/ecuries'));
        exit();
    }
}
