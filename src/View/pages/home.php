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
                <h3>Actualité</h3>
                <div class="actus-scroll">
                            <?php foreach ($actus as $a): ?>
                                <a href="<?= htmlspecialchars($a['link']) ?>" target="_blank" rel="noopener noreferrer" class="actu-line">
                                    <div class="actu-title"><?= htmlspecialchars($a['title']) ?></div>
                                    <div class="actu-date"><?= $a['date'] ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
             </div>
            <hr/>
            <div class="predictions-box">
                <h3>Prédictions</h3>
                <div class="prediction-list">
                    <div class="prediction-item">
                        <span>Pole position</span>
                        <strong>Max Verstappen</strong>
                    </div>
                    <div class="prediction-item">
                        <span>Vainqueur</span>
                        <strong>Charles Leclerc</strong>
                    </div>
                    <div class="prediction-item">
                        <span>Meilleur tour</span>
                        <strong>Lando Norris</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>