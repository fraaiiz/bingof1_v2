<?php
$filledCells = count(array_filter($cells, static fn (array $cell): bool => trim($cell['label']) !== ''));
$validatedCells = count(array_filter($cells, static fn (array $cell): bool => $cell['validated']));
$renderGrid = static function (array $gridCells, string $class): void {
    ?>
    <div class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>" aria-label="Grille de bingo 5 par 5">
        <?php foreach ($gridCells as $position => $cell): ?>
            <?php $label = trim($cell['label']); ?>
            <button
                type="button"
                class="bingo-cell<?= $cell['validated'] ? ' checked-cell' : '' ?>"
                data-bingo-cell
                data-position="<?= (int) $position ?>"
                aria-pressed="<?= $cell['validated'] ? 'true' : 'false' ?>"
                aria-label="Case <?= (int) $position ?><?= $label !== '' ? ' : ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') : ', vide' ?>"
            ><?= $label !== '' ? htmlspecialchars($label, ENT_QUOTES, 'UTF-8') : 'Case ' . (int) $position ?></button>
        <?php endforeach; ?>
    </div>
    <?php
};
?>
<section class="bingo-page">
    <div class="bingo-panel">
        <h1>Mon bingo</h1>

        <?php if (!empty($notice)): ?>
            <p class="bingo-notice" role="status"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post" action="/mon-bingo" class="bingo-form" data-bingo-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <?php foreach ($cells as $position => $cell): ?>
                <input
                    type="hidden"
                    name="cells[<?= (int) $position ?>][label]"
                    value="<?= htmlspecialchars($cell['label'], ENT_QUOTES, 'UTF-8') ?>"
                    data-cell-label="<?= (int) $position ?>"
                >
                <input
                    type="hidden"
                    name="cells[<?= (int) $position ?>][validated]"
                    value="<?= $cell['validated'] ? '1' : '0' ?>"
                    data-cell-validated="<?= (int) $position ?>"
                >
            <?php endforeach; ?>

            <?php $renderGrid($cells, 'bingo-grid'); ?>

            <div class="bingo-buttons">
                <button type="button" data-open-bingo="edit">Éditer la grille</button>
                <button type="button" data-toggle-validation aria-pressed="false">Valider le bingo</button>
                <button type="submit">Enregistrer la grille</button>
            </div>

            <dialog class="bingo-overlay" data-bingo-overlay aria-labelledby="bingo-overlay-title">
                <div class="bingo-overlay-content">
                    <header class="bingo-overlay-header">
                        <div>
                            <h2 id="bingo-overlay-title" data-overlay-title>Ma grille de bingo</h2>
                            <p data-overlay-description>Sélectionnez une case pour la modifier.</p>
                        </div>
                        <button type="button" class="bingo-overlay-close" data-close-bingo aria-label="Fermer la grille">&times;</button>
                    </header>

                    <?php $renderGrid($cells, 'bingo-grid bingo-overlay-grid'); ?>

                    <div class="bingo-cell-preview" data-cell-preview hidden>
                        <p>Case <span data-preview-position>1</span></p>
                        <div data-preview-content></div>
                    </div>

                    <div class="bingo-cell-editor" data-cell-editor>
                        <label for="bingo-cell-label">Texte de la case <span data-selected-position>1</span></label>
                        <div class="bingo-cell-editor-controls">
                            <textarea id="bingo-cell-label" maxlength="255" rows="3" data-cell-input></textarea>
                            <div class="bingo-cell-editor-actions">
                                <button type="submit">Enregistrer la grille</button>
                            </div>
                        </div>
                    </div>
                </div>
            </dialog>
        </form>
    </div>

    <aside class="bingo-rules">
        <h2>Les règles</h2>
        <p><strong>Modifier votre grille :</strong> Appuyez sur « Éditer la grille » pour modifier le texte d’une case. Appuyez directement sur une case pour la consulter.</p>
        <p><strong>Enregistrer :</strong> Appuyez sur « Enregistrer la grille » pour sauvegarder vos changements.</p>
        <p><strong>Valider :</strong> Activez « Valider le bingo », puis sélectionnez les événements réalisés.</p>
        <p>Vous pouvez modifier votre grille à tout moment. Les grilles enregistrées sont visibles dans « Les Bingos ».</p>
    </aside>
</section>
<script src="/assets/js/bingo.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/bingo.js') ?>" defer></script>
