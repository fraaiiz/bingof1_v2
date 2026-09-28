<section class="calendrier-page">
    <h1 class="calendrier-header">Le calendrier de la saison <?= htmlspecialchars($annee) ?></h1>

    <div class="calendrier-caroussel" aria-label="Carrousel des circuits">
        <button type="button" class="carousel-btn carousel-btn-prev" aria-label="Précédent">&lt;</button>

        <article class="calendrier-card active">
            <div class="circuit-content">
                <div class="circuit-infos">
                    <h1 class="circuit-round">Manche 15</h1>
                    <h2>Monza</h2>
                    <h3>04/09/2026 - 06/09/2026</h3>
                    <p class="circuit-country">Italie</p>
                    <p class="circuit-name">Formula 1 Pirelli Gran Premio d'Italia 2026</p>

                    <div class="circuit-meta">
                        <span>Nombre de tours: 53 tours</span>
                        <span>Longueur: 5,793 km</span>
                        <span>Nombre de virages: 11</span>
                        <span>Meilleur tour: 1:18.792 (Max Verstappen - 2025)</span>
                        <span>Dernier vainqueur: Kimi Antonelli</span>
                    </div>
                </div>

                <div class="circuit-visual">
                    <img src="/assets/images/monza.png" alt="Tracé du circuit de Monza" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="trace-placeholder" aria-label="Image du circuit non disponible">Circuit de Monza</div>
                </div>
            </div>
        </article>

        <button type="button" class="carousel-btn carousel-btn-next" aria-label="Suivant">&gt;</button>
    </div>
</section>