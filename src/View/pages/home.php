<div class="home-page">
    <div class="home-header">
        <?php if (!empty($course)): ?>
            <h1 class='text-center'>Prochaine course :</h1>
            <h2 class='text-center'><strong><?= htmlspecialchars($course['nom_circuit']) ?></strong> <span id='countdown'></span></h2>

            <script>
                window.countdownData = {
                    start: <?= json_encode($course['date_fp1']) ?>,
                    end: <?= json_encode($course['date_course']) ?>,
                    status: <?= json_encode($status) ?>
                };
            </script>
            <script src="/assets/js/countdown.js"></script>
        <?php else: ?>
            <p>Aucune course à venir.</p>
        <?php endif; ?>
        <br>
    </div>

    <div class="home-content">
        <div class="home-card-left">
            <h3>Salon de discussion</h3>
            <div class="chat-box">
                <div class="chat-messages">
                    <div class="message">
                        <span class="username">Utilisateur1:</span> <span class="text">Bonjour à tous !</span>
                    </div>
                    <div class="message">
                        <span class="username">Utilisateur2:</span> <span class="text">Salut ! Comment ça va ?</span>
                    </div>
                </div>
                <form class="chat-form">
                    <input type="text" placeholder="Tapez votre message..." />
                    <button type="submit">Envoyer</button>
                </form>
            </div>
        </div>

        <div class="home-card-right">
            <div class="webhook-box">
                <h3>Actualités</h3>
                <div class="actus-scroll">
                            <?php foreach ($actus as $a): ?>
                                <a href="<?= htmlspecialchars($a['link']) ?>" target="_blank" rel="noopener noreferrer" class="actu-line">
                                    <div class="actu-title"><?= htmlspecialchars($a['title']) ?></div>
                                    <div class="actu-date"><?= $a['date'] ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
             </div>
            <?php if (empty($hidePredictions)): ?>
                <hr/>
                <div class="predictions-box">
                    <h3>Prédictions</h3>
                    <?php if (!empty($predictionNotice)): ?>
                        <p class="prediction-notice prediction-notice--<?= htmlspecialchars($predictionNotice['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
                            <?= htmlspecialchars($predictionNotice['message'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($course) && $status === 'en_cours'): ?>
                        <?php if (!$isLoggedIn): ?>
                            <p class="prediction-message">Connectez-vous pour participer aux pronostics.</p>
                            <a class="prediction-link" href="/login">Se connecter</a>
                        <?php elseif ($predictionOpen && !empty($drivers)): ?>
                            <form class="prediction-form" method="POST" action="/predictions">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                                <?php foreach (['p1' => 'P1', 'p2' => 'P2', 'p3' => 'P3'] as $field => $label): ?>
                                    <label>
                                        <span><?= $label ?></span>
                                        <select name="<?= $field ?>" required>
                                            <option value="">Choisir un pilote</option>
                                            <?php foreach ($drivers as $driver): ?>
                                                <option value="<?= (int) $driver['id'] ?>"<?= isset($userPrediction[$field]) && (int) $userPrediction[$field] === (int) $driver['id'] ? ' selected' : '' ?>>
                                                    <?= htmlspecialchars($driver['numero_pilote'] . ' - ' . $driver['prenom_pilote'] . ' ' . $driver['nom_pilote'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                <?php endforeach; ?><br>
                                <button type="submit">Enregistrer ma prédiction</button>
                            </form>
                        <?php elseif (!$predictionOpen): ?>
                            <p class="prediction-message">La course a commencé, les pronostics sont fermés.</p>
                        <?php else: ?>
                            <p class="prediction-message">Aucun pilote titulaire n’est disponible pour cette course.</p>
                        <?php endif; ?>

                        <div class="participant-predictions">
                            <h4>Vos prédictions</h4>
                            <?php if (empty($participantPredictions)): ?>
                                <p class="prediction-message">Aucun pronostic enregistré pour le moment.</p>
                            <?php else: ?>
                                <div class="prediction-list">
                                    <?php foreach ($participantPredictions as $prediction): ?>
                                        <article class="prediction-participant">
                                            <div class="prediction-choices">
                                                <span><u><strong><?= htmlspecialchars($prediction['user_login'], ENT_QUOTES, 'UTF-8') ?></strong></u></span>
                                                <span><strong><?= htmlspecialchars($prediction['p1_name'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                                                <span><strong><?= htmlspecialchars($prediction['p2_name'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                                                <span><strong><?= htmlspecialchars($prediction['p3_name'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!empty($course)): ?>
                        <p class="prediction-message">Les pronostics pour <?= htmlspecialchars($course['nom_circuit'], ENT_QUOTES, 'UTF-8') ?> seront ouverts à partir des EL1.</p>
                    <?php else: ?>
                        <p class="prediction-message">Aucune course n’est disponible pour les pronostics.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>