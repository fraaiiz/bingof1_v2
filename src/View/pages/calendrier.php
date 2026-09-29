<section class="calendrier-page">
    <h1 class="calendrier-header">Le calendrier de la saison <?= htmlspecialchars($annee) ?></h1>

    <?php if (!empty($courses)): ?>
        <div class="calendrier-caroussel" data-carousel aria-label="Carrousel des courses">
            <button type="button" class="carousel-btn carousel-btn-prev" data-previous aria-label="Course précédente"<?= $selectedCourseIndex === 0 ? ' disabled' : '' ?>>&lt;</button>

            <div class="calendrier-cards">
                <?php foreach ($courses as $index => $course): ?>
                    <?php
                    $isSelected = $index === $selectedCourseIndex;
                    $dateDebut = new DateTimeImmutable($course['date_fp1']);
                    $dateCourse = new DateTimeImmutable($course['date_course']);
                    $imagePath = '/' . ltrim($course['image_trace'], '/');
                    if (strpos($imagePath, '/assets/') !== 0) {
                        $imagePath = '/assets' . $imagePath;
                    }
                    $informations = [
                        'Nombre de tours' => $course['nombre_tours'],
                        'Longueur' => $course['longueur_circuit'] ? $course['longueur_circuit'] . ' km' : null,
                        'Nombre de virages' => $course['nombre_virages'],
                        'Meilleur tour' => $course['best_lap'],
                        'Dernier vainqueur' => $course['last_winner'],
                    ];
                    ?>
                    <article class="calendrier-card<?= $isSelected ? ' active' : '' ?>" data-course-card<?= $isSelected ? '' : ' hidden' ?> aria-hidden="<?= $isSelected ? 'false' : 'true' ?>">
                        <div class="circuit-content">
                            <div class="circuit-infos">
                                <p class="circuit-round">Manche <?= htmlspecialchars((string) $course['num_manche']) ?></p>
                                <h2><?= htmlspecialchars($course['nom_circuit']) ?></h2>
                                <p class="circuit-dates">
                                    <?= $dateDebut->format('d/m/Y') ?> - <?= $dateCourse->format('d/m/Y') ?>
                                </p>
                                <p class="circuit-country"><?= htmlspecialchars($course['pays_circuit']) ?></p>
                                
                                <?php if (array_filter($informations, static fn ($value) => $value !== null && $value !== '') !== []): ?>
                                    <div class="circuit-meta">
                                        <?php foreach ($informations as $label => $value): ?>
                                            <?php if ($value !== null && $value !== ''): ?>
                                                <span><?= htmlspecialchars($label) ?>: <?= htmlspecialchars((string) $value) ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="circuit-visual">
                                <?php if (!empty($course['image_trace'])): ?>
                                    <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="Tracé du circuit <?= htmlspecialchars($course['nom_circuit']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <?php endif; ?>
                                <div class="trace-placeholder"<?= !empty($course['image_trace']) ? '' : ' style="display: flex"' ?> aria-label="Image du circuit non disponible">
                                    <?= htmlspecialchars($course['nom_circuit']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="results-buttons">
                            <button class="results">Accéder aux résultats</button>
                            <button class="edit">Éditer les résultats</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <button type="button" class="carousel-btn carousel-btn-next" data-next aria-label="Course suivante"<?= $selectedCourseIndex === count($courses) - 1 ? ' disabled' : '' ?>>&gt;</button>
        </div>

        <script src="/assets/js/calendrier.js" defer></script>
    <?php else: ?>
        <h1 class="calendrier-empty">Le calendrier n'est pas encore disponible pour cette saison.</h1>
    <?php endif; ?>
</section>