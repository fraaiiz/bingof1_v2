<section class="calendrier-page">
    <h1 class="calendrier-header">Le calendrier de la saison <?= htmlspecialchars($annee) ?></h1>

    <?php if (!empty($courses)): ?>
        <div class="calendrier-course-jump">
            <label for="course-jump">Accès rapide à une course</label>
            <select id="course-jump" data-course-jump>
                <?php foreach ($courses as $index => $course): ?>
                    <option value="<?= (int) $index ?>"<?= $index === $selectedCourseIndex ? ' selected' : '' ?>>
                        Manche <?= htmlspecialchars((string) $course['num_manche'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($course['nom_circuit'], ENT_QUOTES, 'UTF-8') ?><?= $course['is_cancelled'] === 'yes' ? ' (annulée)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="calendrier-caroussel" data-carousel aria-label="Carrousel des courses">
            <button type="button" class="carousel-btn carousel-btn-prev" data-previous aria-label="Course précédente"<?= $selectedCourseIndex === 0 ? ' disabled' : '' ?>>&lt;</button>

            <div class="calendrier-cards">
                <?php foreach ($courses as $index => $course): ?>
                    <?php
                    $isSelected = $index === $selectedCourseIndex;
                    $dateDebut = new DateTimeImmutable($course['date_fp1']);
                    $dateCourse = new DateTimeImmutable($course['date_course']);
                    $lieuCircuit = (string) ($course['circuit_pays'] ?? '');
                    if (($course['circuit_ville'] ?? '') !== '') {
                        $lieuCircuit .= ' (' . $course['circuit_ville'] . ')';
                    }
                    $imagePath = '/' . ltrim((string) ($course['circuit_image'] ?? ''), '/');
                    if ($imagePath !== '/' && strpos($imagePath, '/assets/') !== 0) {
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
                                <?php if ($course['is_cancelled'] === 'yes'): ?>
                                    <p class="circuit-cancelled" role="status">Course annulée — aucun résultat</p>
                                <?php endif; ?>
                                <p class="circuit-dates">
                                    <?= $dateDebut->format('d/m/Y') ?> - <?= $dateCourse->format('d/m/Y') ?>
                                </p>
                                <p class="circuit-country"><?= htmlspecialchars($lieuCircuit) ?></p>
                                
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
                                <?php if (!empty($course['circuit_image'])): ?>
                                    <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="Tracé du circuit <?= htmlspecialchars($course['nom_circuit']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <?php endif; ?>
                                <div class="trace-placeholder"<?= !empty($course['circuit_image']) ? '' : ' style="display: flex"' ?> aria-label="Image du circuit non disponible">
                                    <?= htmlspecialchars($course['nom_circuit']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="results-buttons">
                            <?php if ($course['is_cancelled'] === 'yes'): ?>
                                <span class="results-unavailable">Aucun résultat pour cette course</span>
                            <?php else: ?>
                                <a class="results" href="/saisons/<?= rawurlencode((string) $annee) ?>/courses/<?= rawurlencode((string) $course['id']) ?>/resultats">Accéder aux résultats</a>
                                <?php if ($canEditResults): ?>
                                    <a class="edit" href="/saisons/<?= rawurlencode((string) $annee) ?>/courses/<?= rawurlencode((string) $course['id']) ?>/resultats/edition">Éditer les résultats</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <button type="button" class="carousel-btn carousel-btn-next" data-next aria-label="Course suivante"<?= $selectedCourseIndex === count($courses) - 1 ? ' disabled' : '' ?>>&gt;</button>
        </div>

        <script src="/assets/js/calendrier.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/calendrier.js') ?>" defer></script>
    <?php else: ?>
        <h1 class="calendrier-empty">Le calendrier n'est pas encore disponible pour cette saison.</h1>
    <?php endif; ?>
</section>