<?php
$usageLabels = [
    'courses_count' => ['course', 'courses'],
    'drivers_count' => ['pilote engagé', 'pilotes engagés'],
    'teams_count' => ['écurie engagée', 'écuries engagées'],
    'bingos_count' => ['bingo', 'bingos'],
];
$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $date = new DateTimeImmutable($value);

    return $date->format('d/m/Y H:i');
};
?>
<section class="admin-seasons">
    <?php if ($season === null): ?>
        <p class="admin-seasons-back"><a href="/admin">Retour à l’administration</a></p>
        <h1>Saisons</h1>
    <?php else: ?>
        <p class="admin-seasons-back"><a href="/admin/saisons">Retour aux saisons</a></p>
        <h1>Saison <?= (int) $season['annee'] ?></h1>
    <?php endif; ?>

    <?php if (!empty($notice)): ?>
        <p class="admin-seasons-notice admin-seasons-notice--<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
            <?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($confirmation !== null): ?>
        <section class="admin-seasons-confirm" aria-labelledby="season-confirm-title">
            <h2 id="season-confirm-title">Confirmation</h2>
            <?php if ($confirmation['action'] === 'create'): ?>
                <p>Créer la saison <strong><?= (int) $confirmation['annee'] ?></strong> ? Elle apparaîtra dans le menu des saisons.</p>
            <?php elseif ($confirmation['action'] === 'create_course' || $confirmation['action'] === 'update_course'): ?>
                <?php $course = $confirmation['course']; ?>
                <p><?= $confirmation['action'] === 'update_course' ? 'Enregistrer les modifications de cette course pour la saison' : 'Ajouter cette course à la saison' ?> <strong><?= (int) $confirmation['annee'] ?></strong> ?</p>
                <ul class="admin-seasons-usage">
                    <?php
                    $lieuCircuit = (string) $confirmation['circuit']['pays'];
                    if (($confirmation['circuit']['ville'] ?? '') !== '') {
                        $lieuCircuit .= ' (' . $confirmation['circuit']['ville'] . ')';
                    }
                    ?>
                    <li>Manche <?= (int) $course['num_manche'] ?></li>
                    <li><?= htmlspecialchars((string) $course['nom_circuit'], ENT_QUOTES, 'UTF-8') ?></li>
                    <li><?= $confirmation['circuit']['is_new'] ? 'Nouveau circuit' : 'Circuit existant' ?> : <?= htmlspecialchars($lieuCircuit, ENT_QUOTES, 'UTF-8') ?></li>
                    <li><?= htmlspecialchars($formats[(int) $course['format_id']] ?? 'Format inconnu', ENT_QUOTES, 'UTF-8') ?></li>
                    <li><?= htmlspecialchars((string) $confirmation['circuit']['image'], ENT_QUOTES, 'UTF-8') ?></li>
                    <?php foreach ($dateFields as $field): ?>
                        <?php if ($course[$field['column']] !== null): ?>
                            <li><?= htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') ?> : <?= htmlspecialchars($formatDate($course[$field['column']]), ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php elseif ($confirmation['action'] === 'delete_course'): ?>
                <?php
                $course = $confirmation['course'];
                $courseUsageLabels = [
                    'results_count' => ['résultat', 'résultats'],
                    'predictions_count' => ['pronostic', 'pronostics'],
                    'tickets_count' => ['ticket', 'tickets'],
                ];
                ?>
                <p>Supprimer définitivement la manche <strong><?= (int) $course['num_manche'] ?></strong><?php if ($course['nom_circuit'] !== ''): ?> — <?= htmlspecialchars((string) $course['nom_circuit'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?> de la saison <strong><?= (int) $confirmation['annee'] ?></strong> ?</p>
                <ul class="admin-seasons-usage">
                    <?php foreach ($courseUsageLabels as $key => $labels): ?>
                        <?php $count = (int) $confirmation['usage'][$key]; ?>
                        <li><?= $count ?> <?= htmlspecialchars($count === 1 ? $labels[0] : $labels[1], ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Supprimer définitivement la saison <strong><?= (int) $confirmation['current_annee'] ?></strong> ?</p>
                <?php if ($confirmation['usage'] !== null): ?>
                    <ul class="admin-seasons-usage">
                        <?php foreach ($usageLabels as $key => $labels): ?>
                            <?php $count = (int) $confirmation['usage'][$key]; ?>
                            <li><?= $count ?> <?= htmlspecialchars($count === 1 ? $labels[0] : $labels[1], ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($confirmation['blocked']): ?>
                <p class="admin-seasons-notice admin-seasons-notice--error" role="alert">
                    <?= htmlspecialchars($confirmation['blocked_reason'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="post" action="/admin/saisons/confirmation" class="admin-seasons-confirm-actions">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!$confirmation['blocked']): ?>
                    <button type="submit" name="decision" value="confirm">Confirmer</button>
                <?php endif; ?>
                <button type="submit" name="decision" value="cancel" class="admin-seasons-cancel">Annuler</button>
            </form>
        </section>
    <?php elseif ($season === null): ?>
        <form method="post" action="/admin/saisons" class="admin-seasons-create">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="create">
            <label for="new-season-year">Nouvelle saison</label>
            <input id="new-season-year" name="annee" type="number" inputmode="numeric" min="<?= (int) $minYear ?>" max="<?= (int) $maxYear ?>" required>
            <button type="submit">Créer</button>
        </form>

        <?php if ($seasons === []): ?>
            <p class="admin-seasons-empty">Aucune saison enregistrée.</p>
        <?php else: ?>
            <div class="admin-seasons-table-wrap">
                <table class="admin-seasons-table">
                    <thead>
                        <tr>
                            <th scope="col">Année</th>
                            <th scope="col">Courses</th>
                            <th scope="col">Pilotes</th>
                            <th scope="col">Écuries</th>
                            <th scope="col">Bingos</th>
                            <th scope="col"><span class="admin-seasons-sr">Calendrier</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($seasons as $seasonRow): ?>
                            <tr>
                                <td><?= (int) $seasonRow['annee'] ?></td>
                                <td><?= (int) $seasonRow['courses_count'] ?></td>
                                <td><?= (int) $seasonRow['drivers_count'] ?></td>
                                <td><?= (int) $seasonRow['teams_count'] ?></td>
                                <td><?= (int) $seasonRow['bingos_count'] ?></td>
                                <td>
                                    <a class="admin-seasons-open" href="/admin/saisons/<?= (int) $seasonRow['id'] ?>">Modifier</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <form method="post" action="/admin/saisons" class="admin-seasons-delete-season">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="season_id" value="<?= (int) $season['id'] ?>">
            <button type="submit" class="admin-seasons-cancel">Supprimer la saison</button>
        </form>

        <div class="admin-seasons-table-wrap">
            <table class="admin-seasons-table">
                <thead>
                    <tr>
                        <th scope="col">Manche</th>
                        <th scope="col">Circuit</th>
                        <th scope="col">Pays</th>
                        <th scope="col">EL1</th>
                        <th scope="col">Course</th>
                        <th scope="col">Format</th>
                        <th scope="col"><span class="admin-seasons-sr">Édition</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($courses === []): ?>
                        <tr>
                            <td colspan="7">Aucune course pour cette saison.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <tr<?= $editedCourse !== null && (int) $course['id'] === (int) $editedCourse['id'] ? ' class="admin-seasons-current"' : '' ?>>
                                <td><?= (int) $course['num_manche'] ?></td>
                                <td><?= htmlspecialchars($course['nom_circuit'], ENT_QUOTES, 'UTF-8') ?></td>
                                <?php
                                $lieuCircuit = (string) $course['circuit_pays'];
                                if (($course['circuit_ville'] ?? '') !== '') {
                                    $lieuCircuit .= ' (' . $course['circuit_ville'] . ')';
                                }
                                ?>
                                <td><?= htmlspecialchars($lieuCircuit, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formatDate($course['date_fp1']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formatDate($course['date_course']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formats[(int) $course['format_id']] ?? 'Format inconnu', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a class="admin-seasons-open" href="/admin/saisons/<?= (int) $season['id'] ?>/courses/<?= (int) $course['id'] ?>">Modifier</a>
                                    <form method="post" action="/admin/saisons/<?= (int) $season['id'] ?>/courses/<?= (int) $course['id'] ?>/suppression">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="admin-seasons-cancel">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($editedCourse !== null): ?>
            <form method="post" action="/admin/saisons/<?= (int) $season['id'] ?>/courses/<?= (int) $editedCourse['id'] ?>/suppression" class="admin-seasons-delete-season">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="admin-seasons-cancel">Supprimer la course</button>
            </form>
        <?php endif; ?>

        <?php
        $editing = $editedCourse !== null;
        $courseFormAction = $editing
            ? '/admin/saisons/' . (int) $season['id'] . '/courses/' . (int) $editedCourse['id']
            : '/admin/saisons/' . (int) $season['id'] . '/courses';
        $textValue = static function (string $key) use ($editedCourse): string {
            if ($editedCourse === null || ($editedCourse[$key] ?? null) === null || $editedCourse[$key] === '') {
                return '';
            }

            return (string) $editedCourse[$key];
        };
        $dateValue = static function (string $key) use ($editedCourse): string {
            if ($editedCourse === null || ($editedCourse[$key] ?? null) === null || $editedCourse[$key] === '') {
                return '';
            }

            return (new DateTimeImmutable($editedCourse[$key]))->format('Y-m-d\TH:i');
        };
        ?>
        <form method="post" action="<?= htmlspecialchars($courseFormAction, ENT_QUOTES, 'UTF-8') ?>" class="admin-course-form" enctype="multipart/form-data" data-course-form data-sessions="<?= htmlspecialchars(json_encode($sessionTypesByFormat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>" data-circuits="<?= htmlspecialchars(json_encode($circuits, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>">
            <h2><?= $editing ? 'Modifier la manche ' . (int) $editedCourse['num_manche'] : 'Ajouter une course' ?></h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <fieldset>
                <legend>Course</legend>
                <div class="admin-course-fields">
                    <label>Manche
                        <input name="num_manche" type="number" min="1" max="30" value="<?= htmlspecialchars($textValue('num_manche'), ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label>Nom de l’événement
                        <input name="nom_circuit" type="text" maxlength="100" value="<?= htmlspecialchars($textValue('nom_circuit'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>Format
                        <select name="format_id" required>
                            <?php foreach ($formats as $formatId => $formatLabel): ?>
                                <option value="<?= (int) $formatId ?>"<?= $editing && (int) $editedCourse['format_id'] === (int) $formatId ? ' selected' : '' ?>><?= htmlspecialchars($formatLabel, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Statut
                        <select name="is_cancelled">
                            <option value="no"<?= $editing && $editedCourse['is_cancelled'] === 'no' ? ' selected' : '' ?>>Au programme</option>
                            <option value="yes"<?= $editing && $editedCourse['is_cancelled'] === 'yes' ? ' selected' : '' ?>>Annulée</option>
                        </select>
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend>Circuit</legend>
                <div class="admin-course-fields">
                    <label class="admin-trace-field">Rechercher un circuit
                        <input type="search" data-circuit-search placeholder="Pays ou ville, par exemple Miami" autocomplete="off">
                    </label>
                    <input type="hidden" name="circuit_id" value="<?= htmlspecialchars($textValue('circuit_id'), ENT_QUOTES, 'UTF-8') ?>" data-circuit-id>
                    <div class="admin-circuit-results" data-circuit-results hidden></div>
                    <p class="admin-seasons-help" data-circuit-empty hidden>Aucun circuit ne correspond. Tu peux en créer un.</p>
                    <div class="admin-trace-preview" data-circuit-selected hidden>
                        <img alt="" data-circuit-selected-image>
                        <span data-circuit-selected-name></span>
                        <button type="button" class="admin-seasons-cancel" data-circuit-clear>Changer</button>
                    </div>
                    <button type="button" class="admin-seasons-cancel" data-circuit-create>Le circuit n’existe pas</button>
                    <div class="admin-new-circuit" data-new-circuit hidden>
                        <label required>Pays
                            <input name="circuit_pays" type="text" maxlength="50" disabled>
                        </label>
                        <label>Ville
                            <input name="circuit_ville" type="text" maxlength="50" disabled>
                        </label>
                        <label class="admin-trace-field" required>Image du tracé
                            <input name="image_trace" type="file" accept="image/png,image/jpeg,image/webp" data-trace-input disabled>
                            <span class="admin-trace-preview" data-trace-preview hidden>
                                <img alt="" data-trace-image>
                                <span data-trace-name></span>
                            </span>
                        </label>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Dates</legend>
                <div class="admin-course-fields">
                    <?php $activeFormatId = $editing ? (int) $editedCourse['format_id'] : (int) array_key_first($sessionTypesByFormat); ?>
                    <?php $defaultSessions = $sessionTypesByFormat[$activeFormatId] ?? []; ?>
                    <?php foreach ($dateFields as $sessionType => $field): ?>
                        <?php $visible = in_array($sessionType, $defaultSessions, true); ?>
                        <label data-session="<?= htmlspecialchars($sessionType, ENT_QUOTES, 'UTF-8') ?>"<?= $visible ? '' : ' hidden' ?>>
                            <?= htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') ?>
                            <input name="<?= htmlspecialchars($field['column'], ENT_QUOTES, 'UTF-8') ?>" type="datetime-local" value="<?= htmlspecialchars($dateValue($field['column']), ENT_QUOTES, 'UTF-8') ?>"<?= $field['required'] ? ' required' : '' ?><?= $visible ? '' : ' disabled' ?>>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset>
                <legend>Détails</legend>
                <div class="admin-course-fields">
                    <label>Nombre de tours
                        <input name="nombre_tours" type="number" min="0" max="99" value="<?= htmlspecialchars($textValue('nombre_tours'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>Longueur (km)
                        <input name="longueur_circuit" type="text" maxlength="25" value="<?= htmlspecialchars($textValue('longueur_circuit'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>Nombre de virages
                        <input name="nombre_virages" type="number" min="0" max="99" value="<?= htmlspecialchars($textValue('nombre_virages'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>Meilleur tour
                        <input name="best_lap" type="text" maxlength="50" value="<?= htmlspecialchars($textValue('best_lap'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>Dernier vainqueur
                        <input name="last_winner" type="text" maxlength="50" value="<?= htmlspecialchars($textValue('last_winner'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>Points
                        <select name="half_points">
                            <option value="0"<?= !$editing || (int) $editedCourse['half_points'] === 0 ? ' selected' : '' ?>>Complets</option>
                            <option value="1"<?= $editing && (int) $editedCourse['half_points'] === 1 ? ' selected' : '' ?>>Moitié</option>
                        </select>
                    </label>
                </div>
            </fieldset>

            <div class="admin-course-actions">
                <button type="submit"><?= $editing ? 'Enregistrer les modifications' : 'Ajouter au calendrier' ?></button>
                <?php if ($editing): ?>
                    <a class="admin-course-back" href="/admin/saisons/<?= (int) $season['id'] ?>">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
        <script src="/assets/js/admin-saisons.js?v=<?= filemtime(__DIR__ . '/../../../../public/assets/js/admin-saisons.js') ?>" defer></script>
    <?php endif; ?>
</section>
