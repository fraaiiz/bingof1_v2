<?php
$driverLabel = static function (array $person): string {
    return trim($person['prenom_pilote'] . ' ' . $person['nom_pilote']);
};
$publicPhoto = static function (?string $path): ?string {
    if (!is_string($path) || !preg_match('#^/assets/images/pilotes/[A-Za-z0-9][A-Za-z0-9._-]+\.(png|jpe?g|webp|gif)$#', $path)) {
        return null;
    }

    return $path;
};
$yearOrDash = static function (mixed $value): string {
    return $value === null || $value === '' ? '—' : (string) $value;
};
$usageLabels = [
    'engagements_count' => ['engagement', 'engagements'],
    'results_count' => ['résultat', 'résultats'],
    'predictions_count' => ['pronostic', 'pronostics'],
];
$selectedSeasonId = $editedEngagement['saison_id'] ?? null;
if ($selectedSeasonId === null && $seasons !== []) {
    $selectedSeasonId = $seasons[0]['id'];
}
$selectedTeamId = $editedEngagement['ecurie_id'] ?? null;
$teamsForSeason = array_values(array_filter(
    $teams,
    static fn (array $team): bool => $selectedSeasonId !== null && $team['saison_id'] === $selectedSeasonId
));
$textValue = static function (string $key) use ($driver): string {
    if ($driver === null || ($driver[$key] ?? null) === null || $driver[$key] === '') {
        return '';
    }

    return (string) $driver[$key];
};
$carriedEngagement = $editedEngagement ?? ($engagements[0] ?? null);
$engagementValue = static function (string $key) use ($carriedEngagement): string {
    if (!is_array($carriedEngagement) || ($carriedEngagement[$key] ?? null) === null || $carriedEngagement[$key] === '') {
        return '';
    }

    return (string) $carriedEngagement[$key];
};
?>
<section class="admin-pilotes">
    <?php if ($driver === null): ?>
        <p class="admin-pilotes-back"><a href="/admin">Retour à l’administration</a></p>
        <h1>Pilotes</h1>
    <?php else: ?>
        <p class="admin-pilotes-back"><a href="/admin/pilotes">Retour aux pilotes</a></p>
        <h1><?= htmlspecialchars($driverLabel($driver), ENT_QUOTES, 'UTF-8') ?></h1>
    <?php endif; ?>

    <?php if (!empty($notice)): ?>
        <p class="admin-pilotes-notice admin-pilotes-notice--<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
            <?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($confirmation !== null): ?>
        <?php $confirmedDriver = $confirmation['driver']; ?>
        <section class="admin-pilotes-confirm" aria-labelledby="driver-confirm-title">
            <h2 id="driver-confirm-title">Confirmation</h2>
            <?php if ($confirmation['action'] === 'create' || $confirmation['action'] === 'update'): ?>
                <p><?= $confirmation['action'] === 'create' ? 'Créer le pilote' : 'Enregistrer les modifications de' ?> <strong><?= htmlspecialchars($driverLabel($confirmedDriver), ENT_QUOTES, 'UTF-8') ?></strong> ?</p>
                <ul class="admin-pilotes-usage">
                    <li>N° <?= (int) $confirmedDriver['numero_pilote'] ?></li>
                    <li><?= htmlspecialchars($confirmedDriver['nationalite_pilote'], ENT_QUOTES, 'UTF-8') ?></li>
                    <?php if ($confirmation['image_is_new']): ?>
                        <li>Photo : <?= htmlspecialchars(basename($confirmedDriver['photo_pilote']), ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endif; ?>
                </ul>
            <?php elseif ($confirmation['action'] === 'delete'): ?>
                <p>Supprimer définitivement <strong><?= htmlspecialchars($driverLabel($confirmedDriver), ENT_QUOTES, 'UTF-8') ?></strong> ?</p>
                <?php if ($confirmation['usage'] !== null): ?>
                    <ul class="admin-pilotes-usage">
                        <?php foreach ($usageLabels as $key => $labels): ?>
                            <?php $count = (int) $confirmation['usage'][$key]; ?>
                            <li><?= $count ?> <?= htmlspecialchars($count === 1 ? $labels[0] : $labels[1], ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php elseif ($confirmation['engagement'] !== null): ?>
                <?php $engagement = $confirmation['engagement']; ?>
                <?php if ($confirmation['action'] === 'delete_engagement'): ?>
                    <p>Retirer l’engagement de <strong><?= htmlspecialchars($driverLabel($confirmedDriver), ENT_QUOTES, 'UTF-8') ?></strong> avec <strong><?= htmlspecialchars($engagement['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?></strong> pour <strong><?= (int) $engagement['annee'] ?></strong> ?</p>
                    <ul class="admin-pilotes-usage">
                        <?php $count = (int) ($confirmation['usage']['results_count'] ?? 0); ?>
                        <li><?= $count ?> <?= htmlspecialchars($count === 1 ? 'résultat' : 'résultats', ENT_QUOTES, 'UTF-8') ?></li>
                    </ul>
                <?php else: ?>
                    <p><?= $confirmation['action'] === 'update_engagement' ? 'Enregistrer l’engagement de' : 'Engager' ?> <strong><?= htmlspecialchars($driverLabel($confirmedDriver), ENT_QUOTES, 'UTF-8') ?></strong> avec <strong><?= htmlspecialchars($engagement['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?></strong> pour la saison <strong><?= (int) $engagement['annee'] ?></strong> ?</p>
                    <ul class="admin-pilotes-usage">
                        <li><?= htmlspecialchars($roleLabels[$engagement['role']] ?? $engagement['role'], ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Début en F1 : <?= htmlspecialchars($yearOrDash($engagement['date_debut']), ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Fin : <?= htmlspecialchars($yearOrDash($engagement['date_fin']), ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Titres : <?= htmlspecialchars($yearOrDash($engagement['nombre_titre_pilote']), ENT_QUOTES, 'UTF-8') ?></li>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($confirmation['blocked']): ?>
                <p class="admin-pilotes-notice admin-pilotes-notice--error" role="alert">
                    <?= htmlspecialchars($confirmation['blocked_reason'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="post" action="/admin/pilotes/confirmation" class="admin-pilotes-confirm-actions">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!$confirmation['blocked']): ?>
                    <button type="submit" name="decision" value="confirm">Confirmer</button>
                <?php endif; ?>
                <button type="submit" name="decision" value="cancel" class="admin-pilotes-cancel">Annuler</button>
            </form>
        </section>
    <?php elseif ($driver === null): ?>
        <form method="post" action="/admin/pilotes" class="admin-driver-form" enctype="multipart/form-data">
            <h2>Nouveau pilote</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="admin-driver-fields">
                <label>Prénom
                    <input name="prenom_pilote" type="text" maxlength="100" required>
                </label>
                <label>Nom
                    <input name="nom_pilote" type="text" maxlength="100" required>
                </label>
                <label>Numéro
                    <input name="numero_pilote" type="number" inputmode="numeric" min="<?= (int) $minNumber ?>" max="<?= (int) $maxNumber ?>" required>
                </label>
                <label>Nationalité
                    <input name="nationalite_pilote" type="text" maxlength="100" required>
                </label>
                <label class="admin-pilotes-wide">Photo
                    <input name="photo_pilote" type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-photo-input required>
                    <span class="admin-pilotes-help">Fichier du type norris.png, 2 Mo maximum.</span>
                </label>
                <span class="admin-pilotes-photo-preview" data-photo-preview hidden>
                    <img alt="">
                    <span data-photo-name></span>
                </span>
            </div>
            <div class="admin-driver-actions">
                <button type="submit">Créer</button>
            </div>
        </form>

        <?php if ($drivers === []): ?>
            <p class="admin-pilotes-empty">Aucun pilote enregistré.</p>
        <?php else: ?>
            <div class="admin-pilotes-table-wrap">
                <table class="admin-pilotes-table">
                    <thead>
                        <tr>
                            <th scope="col">Photo</th>
                            <th scope="col">N°</th>
                            <th scope="col">Pilote</th>
                            <th scope="col">Nationalité</th>
                            <th scope="col">Engagements</th>
                            <th scope="col"><span class="admin-pilotes-sr">Fiche</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($drivers as $driverRow): ?>
                            <?php $photo = $publicPhoto($driverRow['photo_pilote']); ?>
                            <tr>
                                <td>
                                    <?php if ($photo !== null): ?>
                                        <img class="admin-pilotes-thumb" src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="" onerror="this.hidden = true">
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $driverRow['numero_pilote'] ?></td>
                                <td><?= htmlspecialchars($driverLabel($driverRow), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($driverRow['nationalite_pilote'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ($driverRow['engagements'] === []): ?>
                                        —
                                    <?php else: ?>
                                        <ul class="admin-pilotes-engagement-list">
                                            <?php foreach ($driverRow['engagements'] as $engagement): ?>
                                                <li><?= (int) $engagement['annee'] ?> · <?= htmlspecialchars($engagement['nom_court'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($roleLabels[$engagement['role']] ?? $engagement['role'], ENT_QUOTES, 'UTF-8') ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="admin-pilotes-open" href="/admin/pilotes/<?= (int) $driverRow['id'] ?>">Modifier</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <form method="post" action="/admin/pilotes/<?= (int) $driver['id'] ?>/suppression" class="admin-pilotes-delete-driver">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="admin-pilotes-cancel">Supprimer le pilote</button>
        </form>

        <form method="post" action="/admin/pilotes/<?= (int) $driver['id'] ?>" class="admin-driver-form" enctype="multipart/form-data">
            <h2>Identité</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="admin-driver-fields">
                <label>Prénom
                    <input name="prenom_pilote" type="text" maxlength="100" value="<?= htmlspecialchars($textValue('prenom_pilote'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>Nom
                    <input name="nom_pilote" type="text" maxlength="100" value="<?= htmlspecialchars($textValue('nom_pilote'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>Numéro
                    <input name="numero_pilote" type="number" inputmode="numeric" min="<?= (int) $minNumber ?>" max="<?= (int) $maxNumber ?>" value="<?= htmlspecialchars($textValue('numero_pilote'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>Nationalité
                    <input name="nationalite_pilote" type="text" maxlength="100" value="<?= htmlspecialchars($textValue('nationalite_pilote'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label class="admin-pilotes-wide">Photo
                    <?php $currentPhoto = $publicPhoto($driver['photo_pilote']); ?>
                    <?php if ($currentPhoto !== null): ?>
                        <img class="admin-pilotes-current-photo" src="<?= htmlspecialchars($currentPhoto, ENT_QUOTES, 'UTF-8') ?>" alt="Photo actuelle de <?= htmlspecialchars($driverLabel($driver), ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <input name="photo_pilote" type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-photo-input>
                    <span class="admin-pilotes-help">Fichier du type norris.png, 2 Mo maximum. Sans nouveau fichier, la photo actuelle est conservée.</span>
                </label>
                <span class="admin-pilotes-photo-preview" data-photo-preview hidden>
                    <img alt="">
                    <span data-photo-name></span>
                </span>
            </div>
            <div class="admin-driver-actions">
                <button type="submit">Enregistrer les modifications</button>
            </div>
        </form>

        <div class="admin-pilotes-table-wrap">
            <table class="admin-pilotes-table">
                <thead>
                    <tr>
                        <th scope="col">Saison</th>
                        <th scope="col">Écurie</th>
                        <th scope="col">Rôle</th>
                        <th scope="col">Début</th>
                        <th scope="col">Fin</th>
                        <th scope="col">Titres</th>
                        <th scope="col"><span class="admin-pilotes-sr">Édition</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($engagements === []): ?>
                        <tr>
                            <td colspan="7">Aucun engagement pour ce pilote.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($engagements as $engagement): ?>
                            <tr<?= $editedEngagement !== null && (int) $engagement['id'] === (int) $editedEngagement['id'] ? ' class="admin-pilotes-current"' : '' ?>>
                                <td><?= (int) $engagement['annee'] ?></td>
                                <td><?= htmlspecialchars($engagement['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($roleLabels[$engagement['role']] ?? $engagement['role'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($yearOrDash($engagement['date_debut']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($yearOrDash($engagement['date_fin']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($yearOrDash($engagement['nombre_titre_pilote']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a class="admin-pilotes-open" href="/admin/pilotes/<?= (int) $driver['id'] ?>/engagements/<?= (int) $engagement['id'] ?>#engagement">Modifier</a>
                                    <form method="post" action="/admin/pilotes/<?= (int) $driver['id'] ?>/engagements/<?= (int) $engagement['id'] ?>/suppression">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="admin-pilotes-cancel">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($editedEngagement !== null): ?>
            <form method="post" action="/admin/pilotes/<?= (int) $driver['id'] ?>/engagements/<?= (int) $editedEngagement['id'] ?>/suppression" class="admin-pilotes-delete-driver">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="admin-pilotes-cancel">Supprimer l’engagement</button>
            </form>
        <?php endif; ?>

        <?php if ($seasons === []): ?>
            <p class="admin-pilotes-empty">Aucune saison n’est disponible pour engager ce pilote.</p>
        <?php elseif ($teams === []): ?>
            <p class="admin-pilotes-empty">Aucune écurie n’est engagée sur une saison. L’engagement d’un pilote se fait avec une écurie déjà inscrite à une saison.</p>
        <?php else: ?>
            <?php
            $engagementFormAction = $editedEngagement !== null
                ? '/admin/pilotes/' . (int) $driver['id'] . '/engagements/' . (int) $editedEngagement['id']
                : '/admin/pilotes/' . (int) $driver['id'] . '/engagements';
            ?>
            <form method="post" action="<?= htmlspecialchars($engagementFormAction, ENT_QUOTES, 'UTF-8') ?>" class="admin-driver-form" id="engagement" data-engagement-form data-teams="<?= htmlspecialchars(json_encode($teams, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
                <h2><?= $editedEngagement !== null ? 'Modifier l’engagement' : 'Nouvel engagement' ?></h2>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="admin-driver-fields">
                    <label>Saison
                        <select name="saison_id" required>
                            <?php foreach ($seasons as $season): ?>
                                <option value="<?= (int) $season['id'] ?>"<?= (int) $season['id'] === (int) $selectedSeasonId ? ' selected' : '' ?>><?= (int) $season['annee'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Écurie
                        <select name="ecurie_id" required>
                            <?php foreach ($teamsForSeason as $team): ?>
                                <option value="<?= (int) $team['id'] ?>"<?= (int) $team['id'] === (int) $selectedTeamId ? ' selected' : '' ?>><?= htmlspecialchars($team['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <p class="admin-pilotes-help admin-pilotes-wide" data-teams-empty<?= $teamsForSeason === [] ? '' : ' hidden' ?>>Aucune écurie n’est engagée pour cette saison.</p>
                    <label>Rôle
                        <select name="role" required>
                            <?php foreach ($roleLabels as $role => $label): ?>
                                <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>"<?= $editedEngagement !== null && $editedEngagement['role'] === $role ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Début en F1
                        <input name="date_debut" type="number" inputmode="numeric" min="<?= (int) $minYear ?>" max="<?= (int) $maxYear ?>" value="<?= htmlspecialchars($engagementValue('date_debut'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>Fin d’engagement
                        <input name="date_fin" type="number" inputmode="numeric" min="<?= (int) $minYear ?>" max="<?= (int) $maxYear ?>" value="<?= htmlspecialchars($engagementValue('date_fin'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>Titres pilote
                        <input name="nombre_titre_pilote" type="number" inputmode="numeric" min="0" max="<?= (int) $maxTitles ?>" value="<?= htmlspecialchars($engagementValue('nombre_titre_pilote'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                </div>
                <p class="admin-pilotes-help">L’écurie proposée dépend des écuries déjà inscrites à la saison.<?php if ($editedEngagement === null && $engagements !== []): ?> Le début en F1, la fin et les titres du dernier engagement sont repris et restent modifiables.<?php endif; ?></p>
                <div class="admin-driver-actions">
                    <button type="submit" data-engagement-submit<?= $teamsForSeason === [] ? ' disabled' : '' ?>><?= $editedEngagement !== null ? 'Enregistrer les modifications' : 'Ajouter l’engagement' ?></button>
                    <?php if ($editedEngagement !== null): ?>
                        <a class="admin-pilotes-back-button" href="/admin/pilotes/<?= (int) $driver['id'] ?>">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($confirmation === null): ?>
        <script src="/assets/js/admin-pilotes.js?v=<?= filemtime(__DIR__ . '/../../../../public/assets/js/admin-pilotes.js') ?>" defer></script>
    <?php endif; ?>
</section>
