<?php
$publicImage = static function (?string $path): ?string {
    if (!is_string($path) || !preg_match('#^/assets/images/(logos|voitures)/[A-Za-z0-9][A-Za-z0-9._-]+\.(png|jpe?g|webp|gif)$#', $path)) {
        return null;
    }

    return $path;
};
$yearOrDash = static function (mixed $value): string {
    return $value === null || $value === '' ? '—' : (string) $value;
};
$imageStatus = static function (mixed $path, bool $isNew): string {
    if (!is_string($path) || $path === '') {
        return 'à ajouter plus tard';
    }

    $name = basename($path);

    return $isNew ? $name : 'conservé (' . $name . ')';
};
$teamUsageLabels = [
    'seasons_count' => ['inscription', 'inscriptions'],
    'drivers_count' => ['pilote engagé', 'pilotes engagés'],
    'results_count' => ['résultat', 'résultats'],
];
$entryUsageLabels = [
    'drivers_count' => ['pilote engagé', 'pilotes engagés'],
    'results_count' => ['résultat', 'résultats'],
];
$textValue = static function (string $key) use ($team): string {
    if ($team === null || ($team[$key] ?? null) === null || $team[$key] === '') {
        return '';
    }

    return (string) $team[$key];
};
?>
<section class="admin-ecuries">
    <?php if ($team === null): ?>
        <p class="admin-ecuries-back"><a href="/admin">Retour à l’administration</a></p>
        <h1>Écuries</h1>
    <?php else: ?>
        <p class="admin-ecuries-back"><a href="/admin/ecuries">Retour aux écuries</a></p>
        <h1><?= htmlspecialchars($team['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?></h1>
    <?php endif; ?>

    <?php if (!empty($notice)): ?>
        <p class="admin-ecuries-notice admin-ecuries-notice--<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
            <?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($confirmation !== null): ?>
        <?php $confirmedTeam = $confirmation['team']; ?>
        <section class="admin-ecuries-confirm" aria-labelledby="team-confirm-title">
            <h2 id="team-confirm-title">Confirmation</h2>
            <?php if ($confirmation['action'] === 'create' || $confirmation['action'] === 'update'): ?>
                <p><?= $confirmation['action'] === 'create' ? 'Créer l’écurie' : 'Enregistrer les modifications de' ?> <strong><?= htmlspecialchars($confirmedTeam['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?></strong> ?</p>
                <ul class="admin-ecuries-usage">
                    <li><?= htmlspecialchars($confirmedTeam['nom_court'], ENT_QUOTES, 'UTF-8') ?></li>
                    <li><?= htmlspecialchars($confirmedTeam['nationalite_ecurie'], ENT_QUOTES, 'UTF-8') ?></li>
                    <li>Création : <?= (int) $confirmedTeam['annee_creation'] ?></li>
                </ul>
            <?php elseif ($confirmation['action'] === 'delete'): ?>
                <p>Supprimer définitivement <strong><?= htmlspecialchars($confirmedTeam['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?></strong> ?</p>
                <?php if ($confirmation['usage'] !== null): ?>
                    <ul class="admin-ecuries-usage">
                        <?php foreach ($teamUsageLabels as $key => $labels): ?>
                            <?php $count = (int) $confirmation['usage'][$key]; ?>
                            <li><?= $count ?> <?= htmlspecialchars($count === 1 ? $labels[0] : $labels[1], ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php elseif ($confirmation['entry'] !== null): ?>
                <?php $entry = $confirmation['entry']; ?>
                <?php if ($confirmation['action'] === 'delete_entry'): ?>
                    <p>Retirer l’inscription de <strong><?= htmlspecialchars($confirmedTeam['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?></strong> pour <strong><?= (int) $entry['annee'] ?></strong> ?</p>
                <?php else: ?>
                    <p><?= $confirmation['action'] === 'update_entry' ? 'Enregistrer l’inscription de' : 'Inscrire' ?> <strong><?= htmlspecialchars($confirmedTeam['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?></strong> pour la saison <strong><?= (int) $entry['annee'] ?></strong> ?</p>
                <?php endif; ?>
                <ul class="admin-ecuries-usage">
                    <?php if ($confirmation['action'] !== 'delete_entry'): ?>
                        <li>Motoriste : <?= htmlspecialchars($entry['nom_motoriste'], ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Titres constructeurs : <?= htmlspecialchars($yearOrDash($entry['nombre_titre']), ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Logo : <?= htmlspecialchars($imageStatus($entry['logo'] ?? null, !empty($confirmation['logo_is_new'])), ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Voiture : <?= htmlspecialchars($imageStatus($entry['voiture'] ?? null, !empty($confirmation['car_is_new'])), ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endif; ?>
                    <?php if ($confirmation['usage'] !== null): ?>
                        <?php foreach ($entryUsageLabels as $key => $labels): ?>
                            <?php $count = (int) $confirmation['usage'][$key]; ?>
                            <li><?= $count ?> <?= htmlspecialchars($count === 1 ? $labels[0] : $labels[1], ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>

            <?php if ($confirmation['blocked']): ?>
                <p class="admin-ecuries-notice admin-ecuries-notice--error" role="alert">
                    <?= htmlspecialchars($confirmation['blocked_reason'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="post" action="/admin/ecuries/confirmation" class="admin-ecuries-confirm-actions">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!$confirmation['blocked']): ?>
                    <button type="submit" name="decision" value="confirm">Confirmer</button>
                <?php endif; ?>
                <button type="submit" name="decision" value="cancel" class="admin-ecuries-cancel">Annuler</button>
            </form>
        </section>
    <?php elseif ($team === null): ?>
        <form method="post" action="/admin/ecuries" class="admin-team-form">
            <h2>Nouvelle écurie</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="admin-team-fields">
                <label>Nom
                    <input name="nom_ecurie" type="text" maxlength="100" required>
                </label>
                <label>Nom court
                    <input name="nom_court" type="text" maxlength="50" required>
                </label>
                <label>Nationalité
                    <input name="nationalite_ecurie" type="text" maxlength="100" required>
                </label>
                <label>Année de création
                    <input name="annee_creation" type="number" inputmode="numeric" min="<?= (int) $minYear ?>" max="<?= (int) $maxYear ?>" required>
                </label>
            </div>
            <div class="admin-team-actions">
                <button type="submit">Créer</button>
            </div>
        </form>

        <?php if ($teams === []): ?>
            <p class="admin-ecuries-empty">Aucune écurie enregistrée.</p>
        <?php else: ?>
            <div class="admin-ecuries-table-wrap">
                <table class="admin-ecuries-table">
                    <thead>
                        <tr>
                            <th scope="col">Logo</th>
                            <th scope="col">Écurie</th>
                            <th scope="col">Nationalité</th>
                            <th scope="col">Création</th>
                            <th scope="col">Saisons</th>
                            <th scope="col"><span class="admin-ecuries-sr">Fiche</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $teamRow): ?>
                            <?php $latestEntry = $teamRow['inscriptions'][0] ?? null; ?>
                            <?php $logo = $publicImage(is_array($latestEntry) ? $latestEntry['logo'] : null); ?>
                            <tr>
                                <td>
                                    <?php if ($logo !== null): ?>
                                        <img class="admin-ecuries-thumb" src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="" onerror="this.hidden = true">
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($teamRow['nom_court'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($teamRow['nationalite_ecurie'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $teamRow['annee_creation'] ?></td>
                                <td>
                                    <?php if ($teamRow['inscriptions'] === []): ?>
                                        —
                                    <?php else: ?>
                                        <ul class="admin-ecuries-entry-list">
                                            <?php foreach ($teamRow['inscriptions'] as $entry): ?>
                                                <li><?= (int) $entry['annee'] ?> · <?= htmlspecialchars($entry['nom_motoriste'], ENT_QUOTES, 'UTF-8') ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="admin-ecuries-open" href="/admin/ecuries/<?= (int) $teamRow['id'] ?>">Modifier</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <form method="post" action="/admin/ecuries/<?= (int) $team['id'] ?>/suppression" class="admin-ecuries-delete">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="admin-ecuries-cancel">Supprimer l’écurie</button>
        </form>

        <form method="post" action="/admin/ecuries/<?= (int) $team['id'] ?>" class="admin-team-form">
            <h2>Identité</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="admin-team-fields">
                <label>Nom
                    <input name="nom_ecurie" type="text" maxlength="100" value="<?= htmlspecialchars($textValue('nom_ecurie'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>Nom court
                    <input name="nom_court" type="text" maxlength="50" value="<?= htmlspecialchars($textValue('nom_court'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>Nationalité
                    <input name="nationalite_ecurie" type="text" maxlength="100" value="<?= htmlspecialchars($textValue('nationalite_ecurie'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>Année de création
                    <input name="annee_creation" type="number" inputmode="numeric" min="<?= (int) $minYear ?>" max="<?= (int) $maxYear ?>" value="<?= htmlspecialchars($textValue('annee_creation'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
            </div>
            <div class="admin-team-actions">
                <button type="submit">Enregistrer les modifications</button>
            </div>
        </form>

        <div class="admin-ecuries-table-wrap">
            <table class="admin-ecuries-table">
                <thead>
                    <tr>
                        <th scope="col">Saison</th>
                        <th scope="col">Motoriste</th>
                        <th scope="col">Titres</th>
                        <th scope="col">Logo</th>
                        <th scope="col">Voiture</th>
                        <th scope="col"><span class="admin-ecuries-sr">Édition</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($entries === []): ?>
                        <tr>
                            <td colspan="6">Aucune inscription pour cette écurie.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                            <?php $logo = $publicImage($entry['logo']); ?>
                            <?php $car = $publicImage($entry['voiture']); ?>
                            <tr<?= $editedEntry !== null && (int) $entry['id'] === (int) $editedEntry['id'] ? ' class="admin-ecuries-current"' : '' ?>>
                                <td><?= (int) $entry['annee'] ?></td>
                                <td><?= htmlspecialchars($entry['nom_motoriste'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($yearOrDash($entry['nombre_titre']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ($logo !== null): ?>
                                        <img class="admin-ecuries-thumb" src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="" onerror="this.hidden = true">
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($car !== null): ?>
                                        <img class="admin-ecuries-thumb" src="<?= htmlspecialchars($car, ENT_QUOTES, 'UTF-8') ?>" alt="" onerror="this.hidden = true">
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="admin-ecuries-open" href="/admin/ecuries/<?= (int) $team['id'] ?>/saisons/<?= (int) $entry['id'] ?>#inscription">Modifier</a>
                                    <form method="post" action="/admin/ecuries/<?= (int) $team['id'] ?>/saisons/<?= (int) $entry['id'] ?>/suppression">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="admin-ecuries-cancel">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($editedEntry !== null): ?>
            <form method="post" action="/admin/ecuries/<?= (int) $team['id'] ?>/saisons/<?= (int) $editedEntry['id'] ?>/suppression" class="admin-ecuries-delete">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="admin-ecuries-cancel">Supprimer l’inscription</button>
            </form>
        <?php endif; ?>

        <?php if ($seasons === []): ?>
            <p class="admin-ecuries-empty">Aucune saison n’est disponible pour inscrire cette écurie.</p>
        <?php elseif ($motoristes === []): ?>
            <p class="admin-ecuries-empty">Aucun motoriste n’est enregistré.</p>
        <?php else: ?>
            <?php
            $carriedEntry = $editedEntry ?? ($entries[0] ?? null);
            $entryFormAction = $editedEntry !== null
                ? '/admin/ecuries/' . (int) $team['id'] . '/saisons/' . (int) $editedEntry['id']
                : '/admin/ecuries/' . (int) $team['id'] . '/saisons';
            $selectedSeasonId = $editedEntry['saison_id'] ?? $seasons[0]['id'];
            $selectedEngineId = is_array($carriedEntry) ? ($carriedEntry['motoriste_id'] ?? null) : null;
            $currentLogo = $publicImage(is_array($carriedEntry) ? ($carriedEntry['logo'] ?? null) : null);
            $currentCar = $publicImage(is_array($carriedEntry) ? ($carriedEntry['voiture'] ?? null) : null);
            $titlesValue = '';
            if (is_array($carriedEntry) && ($carriedEntry['nombre_titre'] ?? null) !== null && $carriedEntry['nombre_titre'] !== '') {
                $titlesValue = (string) $carriedEntry['nombre_titre'];
            }
            if ($editedEntry !== null) {
                $imageHelp = 'Facultatif, 2 Mo maximum. Sans nouveau fichier, l’image de cette inscription est conservée.';
            } elseif ($entries !== []) {
                $imageHelp = 'Facultatif, 2 Mo maximum. Sans nouveau fichier, l’image de la dernière saison est reprise.';
            } else {
                $imageHelp = 'Facultatif, 2 Mo maximum. Le fichier peut être ajouté plus tard.';
            }
            ?>
            <form method="post" action="<?= htmlspecialchars($entryFormAction, ENT_QUOTES, 'UTF-8') ?>" class="admin-team-form" id="inscription" enctype="multipart/form-data">
                <h2><?= $editedEntry !== null ? 'Modifier l’inscription' : 'Inscrire à une saison' ?></h2>
                <p class="admin-ecuries-help">Le logo, la voiture et les titres peuvent être ajoutés plus tard. Sans nouveau fichier, ceux déjà connus sont repris.</p>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="admin-team-fields">
                    <label>Saison
                        <select name="saison_id" required>
                            <?php foreach ($seasons as $season): ?>
                                <option value="<?= (int) $season['id'] ?>"<?= (int) $season['id'] === (int) $selectedSeasonId ? ' selected' : '' ?>><?= (int) $season['annee'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Motoriste
                        <select name="motoriste_id" required>
                            <?php foreach ($motoristes as $engine): ?>
                                <option value="<?= (int) $engine['id'] ?>"<?= (int) $engine['id'] === (int) $selectedEngineId ? ' selected' : '' ?>><?= htmlspecialchars($engine['nom_motoriste'] . ' — ' . $engine['pays_motoriste'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Titres constructeurs
                        <input name="nombre_titre" type="number" inputmode="numeric" min="0" max="<?= (int) $maxTitles ?>" value="<?= htmlspecialchars($titlesValue, ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label class="admin-ecuries-wide">Logo
                        <?php if ($currentLogo !== null): ?>
                            <img class="admin-ecuries-current" src="<?= htmlspecialchars($currentLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo de <?= htmlspecialchars($team['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                        <input name="logo" type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-image-input="logo">
                        <span class="admin-ecuries-help"><?= htmlspecialchars($imageHelp, ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <span class="admin-ecuries-photo-preview" data-image-preview="logo" hidden>
                        <img alt="">
                        <span data-image-name></span>
                    </span>
                    <label class="admin-ecuries-wide">Voiture
                        <?php if ($currentCar !== null): ?>
                            <img class="admin-ecuries-current" src="<?= htmlspecialchars($currentCar, ENT_QUOTES, 'UTF-8') ?>" alt="Voiture actuelle de <?= htmlspecialchars($team['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                        <input name="voiture" type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-image-input="voiture">
                        <span class="admin-ecuries-help"><?= htmlspecialchars($imageHelp, ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <span class="admin-ecuries-photo-preview" data-image-preview="voiture" hidden>
                        <img alt="">
                        <span data-image-name></span>
                    </span>
                </div>
                <div class="admin-team-actions">
                    <button type="submit"><?= $editedEntry !== null ? 'Enregistrer les modifications' : 'Inscrire à la saison' ?></button>
                    <?php if ($editedEntry !== null): ?>
                        <a class="admin-ecuries-back-button" href="/admin/ecuries/<?= (int) $team['id'] ?>">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($confirmation === null): ?>
        <script src="/assets/js/admin-ecuries.js?v=<?= filemtime(__DIR__ . '/../../../../public/assets/js/admin-ecuries.js') ?>" defer></script>
    <?php endif; ?>
</section>
