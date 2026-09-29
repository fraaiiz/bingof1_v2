<?php
$assetPath = static function ($path) {
    if (empty($path)) {
        return null;
    }

    $path = '/' . ltrim($path, '/');
    return strpos($path, '/assets/') === 0 ? $path : '/assets' . $path;
};
?>
<h1>Les informations <?= htmlspecialchars((string) $annee, ENT_QUOTES, 'UTF-8') ?></h1>

<?php if (!empty($equipes)): ?>
    <?php
    $equipe = $equipes[0];
    $logoPath = $assetPath($equipe['logo']);
    $carPath = $assetPath($equipe['voiture']);
    ?>
    <div class="infos-content">
        <section class="infos-ecuries" data-infos-carousel aria-label="Écuries de la saison">
            <div class="infos-ecuries-cards">
                <div class="infos-ecurie" data-team-card>
                    <div class="ecurie-header">
                        <div class="ecurie-visuals">
                            <div class="ecurie-logo-wrap">
                                <?php if ($logoPath !== null): ?>
                                    <img data-team-logo src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo <?= htmlspecialchars($equipe['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?>" class="ecurie-logo" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                                <?php else: ?>
                                    <img data-team-logo alt="" class="ecurie-logo" hidden>
                                <?php endif; ?>
                                <div class="team-logo-placeholder"<?= $logoPath !== null ? ' hidden' : '' ?> aria-hidden="true" data-team-logo-placeholder><?= htmlspecialchars($equipe['nom_court'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                        <div class="ecurie-title-block">
                            <h2 data-team-name><?= htmlspecialchars($equipe['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?></h2>
                        </div>
                        <img data-team-car<?= $carPath === null ? ' hidden' : '' ?><?= $carPath !== null ? ' src="' . htmlspecialchars($carPath, ENT_QUOTES, 'UTF-8') . '"' : '' ?> alt="Voiture <?= htmlspecialchars($equipe['nom_ecurie'], ENT_QUOTES, 'UTF-8') ?>" class="ecurie-car" onerror="this.hidden = true;">
                    </div>

                    <div class="ecurie-details">
                        <p data-team-nationality>Nationalité : <?= htmlspecialchars($equipe['nationalite_ecurie'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p data-team-creation>Création : <?= htmlspecialchars((string) $equipe['annee_creation'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p data-team-engine>Motoriste : <?= htmlspecialchars($equipe['nom_motoriste'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p data-team-titles>Titres constructeurs : <?= htmlspecialchars((string) ($equipe['nombre_titre'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="infos-carousel-controls">
                        <button type="button" class="carousel-btn carousel-btn-prev" data-previous aria-label="Écurie précédente">&lt;</button>
                        <p class="infos-position" data-position aria-live="polite">Écurie 1 / <?= count($equipes) ?></p>
                        <button type="button" class="carousel-btn carousel-btn-next" data-next aria-label="Écurie suivante">&gt;</button>
                    </div>
                </div>
            </div>

            <script type="application/json" data-team-data><?= json_encode($equipes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
        </section>

        <div class="infos-pilotes">
            <div class="pilote-grid" data-pilote-grid>
                <?php foreach ($equipe['pilotes'] as $pilote): ?>
                    <?php $photoPath = $assetPath($pilote['photo_pilote']); ?>
                    <div class="card-pilote">
                        <div class="driver-photo">
                            <?php if ($photoPath !== null): ?>
                                <img src="<?= htmlspecialchars($photoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Photo de <?= htmlspecialchars($pilote['prenom_pilote'] . ' ' . $pilote['nom_pilote'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                            <?php endif; ?>
                            <div class="driver-photo-placeholder"<?= $photoPath !== null ? ' hidden' : '' ?> aria-hidden="true">Photo indisponible</div>
                        </div>
                        <div class="driver-details">
                            <h2><?= htmlspecialchars((string) $pilote['numero_pilote'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($pilote['prenom_pilote'] . ' ' . $pilote['nom_pilote'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p>Nationalité : <?= htmlspecialchars($pilote['nationalite_pilote'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p>Début en F1 : <?= htmlspecialchars((string) ($pilote['date_debut'] ?? 'Non renseignée'), ENT_QUOTES, 'UTF-8') ?></p>
                            <p>Titres : <?= htmlspecialchars((string) ($pilote['nombre_titre_pilote'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="reserve" data-team-reserves<?= empty($equipe['reserves']) ? ' hidden' : '' ?>>
                <h3>Réservistes</h3>
                <p data-team-reserves-list><?= htmlspecialchars(implode(', ', array_map(static function ($pilote) {
                    return $pilote['prenom_pilote'] . ' ' . $pilote['nom_pilote'];
                }, $equipe['reserves'])), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
    <script src="/assets/js/infos.js" defer></script>
<?php else: ?>
    <p class="infos-empty">Aucune écurie n'est disponible pour la saison <?= htmlspecialchars((string) $annee, ENT_QUOTES, 'UTF-8') ?>.</p>
<?php endif; ?>