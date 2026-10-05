<?php
$pilotesPourRecherche = [];
foreach ($pilotes as $pilote) {
    $pilotLabelParts = [trim($pilote['prenom_pilote'] . ' ' . $pilote['nom_pilote'])];
    if (!empty($pilote['nom_ecurie'])) {
        $pilotLabelParts[] = $pilote['nom_ecurie'];
    }
    if (!empty($pilote['numero_pilote'])) {
        $pilotLabelParts[] = '#' . $pilote['numero_pilote'];
    }
    $pilotLabelParts[] = $pilote['role'] === 'reserve' ? '(Réserve)' : '(Titulaire)';
    $pilotesPourRecherche[] = [
        'id' => (int) $pilote['id'],
        'label' => implode(' - ', $pilotLabelParts),
    ];
}

$sessionDefinitions = [
    'fp1' => ['label' => 'Essais Libres 1', 'timeFields' => [['key' => 'time', 'label' => 'Temps']]],
    'fp2' => ['label' => 'Essais Libres 2', 'timeFields' => [['key' => 'time', 'label' => 'Temps']]],
    'fp3' => ['label' => 'Essais Libres 3', 'timeFields' => [['key' => 'time', 'label' => 'Temps']]],
    'qualif_course' => ['label' => 'Qualifications', 'timeFields' => [
        ['key' => 'q1_time', 'label' => 'Q1'],
        ['key' => 'q2_time', 'label' => 'Q2'],
        ['key' => 'q3_time', 'label' => 'Q3'],
    ]],
    'qualif_sprint' => ['label' => 'Qualifications Sprint', 'timeFields' => [
        ['key' => 'sq1_time', 'label' => 'SQ1'],
        ['key' => 'sq2_time', 'label' => 'SQ2'],
        ['key' => 'sq3_time', 'label' => 'SQ3'],
    ]],
    'sprint' => ['label' => 'Sprint', 'timeFields' => [['key' => 'time', 'label' => 'Temps']], 'showRaceFields' => true, 'showGap' => true, 'showPoints' => true],
    'course' => ['label' => 'Course', 'timeFields' => [['key' => 'time', 'label' => 'Temps']], 'showRaceFields' => true, 'showGap' => true, 'showPoints' => true],
];
$pilotesParId = [];
foreach ($pilotesPourRecherche as $pilote) {
    $pilotesParId[$pilote['id']] = $pilote['label'];
}
?>
<section class="edit-resultat-page">
    <a class="edit-resultat-back" href="/saisons/<?= rawurlencode((string) $annee) ?>/courses/<?= rawurlencode((string) $course['id']) ?>/resultats">Retour aux résultats</a>
    <header>
        <p class="edit-resultat-round">Manche <?= htmlspecialchars((string) $course['num_manche'], ENT_QUOTES, 'UTF-8') ?></p>
        <h1>Éditer les résultats</h1>
        <p class="edit-resultat-course"><?= htmlspecialchars($course['nom_circuit'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($course['pays_circuit'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="edit-resultat-year">Saison <?= htmlspecialchars((string) $annee, ENT_QUOTES, 'UTF-8') ?></p>
    </header>
    <?php if ($notice !== null): ?>
        <p class="edit-resultat-notice edit-resultat-notice--<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
            <?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    <?php if ($pilotes === []): ?>
        <p class="edit-resultat-no-pilots">Aucun pilote n'est engagé pour cette saison.</p>
    <?php endif; ?>

    <?php if ($sessions === []): ?>
        <p class="edit-resultat-empty">Aucune séance n'est configurée pour ce format.</p>
    <?php else: ?>
        <div class="edit-resultat-sessions">
            <?php foreach ($sessions as $sessionRow): ?>
                <?php
                $sessionId = (int) $sessionRow['id'];
                $sessionKey = (string) $sessionRow['session_type'];
                $session = $sessionDefinitions[$sessionKey] ?? [
                    'label' => ucfirst(str_replace('_', ' ', $sessionKey)),
                    'timeFields' => [['key' => 'time', 'label' => 'Temps']],
                ];
                $sessionResults = $oldResults[$sessionId] ?? $existingResults[$sessionId] ?? [];
                $sessionErrors = $validationErrors[$sessionId] ?? [];
                $pointsByPosition = $sessionKey === 'sprint'
                    ? [1 => 8, 2 => 7, 3 => 6, 4 => 5, 5 => 4, 6 => 3, 7 => 2, 8 => 1]
                    : [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1];
                ?>
                <form class="edit-resultat-form" method="post" data-result-session="<?= !empty($session['showGap']) ? htmlspecialchars($sessionKey, ENT_QUOTES, 'UTF-8') : '' ?>" action="/saisons/<?= rawurlencode((string) $annee) ?>/courses/<?= rawurlencode((string) $course['id']) ?>/resultats/edition">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                    <details class="edit-resultat-session"<?= $sessionErrors !== [] ? ' open' : '' ?>>
                        <summary>
                            <span><?= htmlspecialchars($session['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="edit-resultat-arrow" aria-hidden="true">&#9660;</span>
                        </summary>
                        <div class="edit-resultat-table-wrap">
                            <table class="edit-resultat-table">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Pilote</th>
                                        <?php foreach ($session['timeFields'] as $timeField): ?>
                                            <th scope="col"><?= htmlspecialchars($timeField['label'], ENT_QUOTES, 'UTF-8') ?></th>
                                        <?php endforeach; ?>
                                        <?php if (!empty($session['showRaceFields'])): ?>
                                            <th scope="col">Tours</th>
                                            <th scope="col">Statut</th>
                                        <?php endif; ?>
                                        <?php if (!empty($session['showGap'])): ?>
                                            <th scope="col">Écart</th>
                                        <?php endif; ?>
                                        <?php if (!empty($session['showPoints'])): ?>
                                            <th scope="col">Points</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($position = 1; $position <= 22; $position++): ?>
                                        <?php
                                        $row = $sessionResults[$position] ?? [];
                                        $rowError = $sessionErrors[$position] ?? null;
                                        $pilotId = (int) ($row['pilot_id'] ?? $row['pilote_id'] ?? 0);
                                        $pilotQuery = $row['pilot_query'] ?? ($pilotesParId[$pilotId] ?? '');
                                        $pilotError = ($rowError['field'] ?? null) === 'pilot_query' ? $rowError['message'] : null;
                                        ?>
                                        <tr>
                                            <th scope="row"><?= $position ?></th>
                                            <td>
                                                <div class="edit-resultat-autocomplete">
                                                    <input
                                                        class="edit-resultat-input edit-resultat-pilot<?= $pilotError !== null ? ' is-invalid' : '' ?>"
                                                        type="search"
                                                        name="results[<?= $position ?>][pilot_query]"
                                                        value="<?= htmlspecialchars((string) $pilotQuery, ENT_QUOTES, 'UTF-8') ?>"
                                                        autocomplete="off"
                                                        role="combobox"
                                                        aria-autocomplete="list"
                                                        aria-haspopup="listbox"
                                                        aria-expanded="false"
                                                        aria-controls="pilotes-options-<?= $sessionId ?>-<?= $position ?>"
                                                        data-pilot-search
                                                        <?= $pilotError !== null ? 'aria-invalid="true" aria-describedby="result-error-' . $sessionId . '-' . $position . '-pilot"' : '' ?>
                                                        aria-label="Rechercher le pilote en position <?= $position ?> pour <?= htmlspecialchars($session['label'], ENT_QUOTES, 'UTF-8') ?>"
                                                        placeholder="Rechercher un pilote..."
                                                    >
                                                    <input type="hidden" name="results[<?= $position ?>][pilot_id]" value="<?= $pilotId ?: '' ?>" data-pilot-id>
                                                    <div
                                                        class="edit-resultat-suggestions"
                                                        id="pilotes-options-<?= $sessionId ?>-<?= $position ?>"
                                                        role="listbox"
                                                        hidden
                                                    ></div>
                                                    <?php if ($pilotError !== null): ?>
                                                        <span class="edit-resultat-field-error" id="result-error-<?= $sessionId ?>-<?= $position ?>-pilot" role="alert"><?= htmlspecialchars($pilotError, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <?php foreach ($session['timeFields'] as $timeField): ?>
                                                <?php
                                                $fieldKey = $timeField['key'];
                                                $timeValue = $row[$fieldKey . '_input'] ?? $row[$fieldKey] ?? '';
                                                $timeError = ($rowError['field'] ?? null) === $fieldKey ? $rowError['message'] : null;
                                                $timeErrorId = 'result-error-' . $sessionId . '-' . $position . '-' . $fieldKey;
                                                ?>
                                                <td>
                                                    <input
                                                        class="edit-resultat-input edit-resultat-time<?= $timeError !== null ? ' is-invalid' : '' ?>"
                                                        type="text"
                                                        name="results[<?= $position ?>][<?= $fieldKey ?>]"
                                                        value="<?= htmlspecialchars((string) $timeValue, ENT_QUOTES, 'UTF-8') ?>"
                                                        inputmode="decimal"
                                                        data-result-time
                                                        <?= $timeError !== null ? 'aria-invalid="true" aria-describedby="' . $timeErrorId . '"' : '' ?>
                                                        aria-label="<?= htmlspecialchars($timeField['label'], ENT_QUOTES, 'UTF-8') ?>, position <?= $position ?>"
                                                        placeholder="00:00.000"
                                                    >
                                                    <?php if ($timeError !== null): ?>
                                                        <span class="edit-resultat-field-error" id="<?= $timeErrorId ?>" role="alert"><?= htmlspecialchars($timeError, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <?php if (!empty($session['showRaceFields'])): ?>
                                                <?php
                                                $lapsError = ($rowError['field'] ?? null) === 'tours' ? $rowError['message'] : null;
                                                $statusError = ($rowError['field'] ?? null) === 'status' ? $rowError['message'] : null;
                                                ?>
                                                <td>
                                                    <input
                                                        class="edit-resultat-input edit-resultat-laps<?= $lapsError !== null ? ' is-invalid' : '' ?>"
                                                        type="number"
                                                        name="results[<?= $position ?>][tours]"
                                                        value="<?= htmlspecialchars((string) ($row['tours'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                        min="0"
                                                        max="99"
                                                        step="1"
                                                        inputmode="numeric"
                                                        data-result-laps
                                                        <?= $lapsError !== null ? 'aria-invalid="true" aria-describedby="result-error-' . $sessionId . '-' . $position . '-laps"' : '' ?>
                                                        aria-label="Nombre de tours, position <?= $position ?> pour <?= htmlspecialchars($session['label'], ENT_QUOTES, 'UTF-8') ?>"
                                                        placeholder="-"
                                                    >
                                                    <?php if ($lapsError !== null): ?>
                                                        <span class="edit-resultat-field-error" id="result-error-<?= $sessionId ?>-<?= $position ?>-laps" role="alert"><?= htmlspecialchars($lapsError, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <select
                                                        class="edit-resultat-input edit-resultat-status<?= $statusError !== null ? ' is-invalid' : '' ?>"
                                                        name="results[<?= $position ?>][status]"
                                                        <?= $statusError !== null ? 'aria-invalid="true" aria-describedby="result-error-' . $sessionId . '-' . $position . '-status"' : '' ?>
                                                        aria-label="Statut, position <?= $position ?> pour <?= htmlspecialchars($session['label'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-result-status
                                                    >
                                                        <option value=""<?= ($row['status'] ?? '') === '' ? ' selected' : '' ?>>Terminé</option>
                                                        <option value="dnf"<?= ($row['status'] ?? '') === 'dnf' ? ' selected' : '' ?>>DNF</option>
                                                        <option value="dsq"<?= ($row['status'] ?? '') === 'dsq' ? ' selected' : '' ?>>DSQ</option>
                                                        <option value="np"<?= ($row['status'] ?? '') === 'np' ? ' selected' : '' ?>>NP</option>
                                                    </select>
                                                    <?php if ($statusError !== null): ?>
                                                        <span class="edit-resultat-field-error" id="result-error-<?= $sessionId ?>-<?= $position ?>-status" role="alert"><?= htmlspecialchars($statusError, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if (!empty($session['showGap'])): ?>
                                                <td><output class="edit-resultat-calculated" data-result-gap>--</output></td>
                                            <?php endif; ?>
                                            <?php if (!empty($session['showPoints'])): ?>
                                                <td><output class="edit-resultat-calculated" data-result-points><?= $pointsByPosition[$position] ?? 0 ?></output></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        <button class="edit-resultat-save" type="submit">Enregistrer <?= htmlspecialchars($session['label'], ENT_QUOTES, 'UTF-8') ?></button>
                    </details>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>

<script type="application/json" id="edit-resultat-pilotes"><?= json_encode($pilotesPourRecherche, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<script src="/assets/js/edit-resultat.js" defer></script>