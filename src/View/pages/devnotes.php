<article class="devnotes-page">
    <h2>À propos</h2>
    <div class="devnotes-intro">
        <p>
            Je suis étudiant en développement web et passionné de Formule 1.<br>
            Ce site est avant tout un terrain d’entraînement pour moi et n’est pas forcément destiné à un large public.
        </p>
        <p>
            L’idée m’est venue avec un ami <u><strong>(coucou Poun)</strong></u> :
            nous avons l’habitude de créer un bingo pour chaque saison afin d’anticiper les événements qui pourraient survenir.
        </p>
        <p>
            Pour des raisons de droits, je n’utilise pas d’API officielles pour les timings ou les cartographies afin de ne pas poser de problème avec Liberty Media ou la FIA.<br>
            Les images présentes sur le site appartiennent à leurs propriétaires respectifs.
        </p>
    </div>

    <h2 class="devnotes-notes">Devnotes</h2>

    <section class="devnotes-version">
        <h3 class="devnotes-version-title">Version 2</h3>
        <p class="devnotes-version-label">Refonte en cours</p>

    </section>
    
    <section class="devnotes-version">
        <h3 class="devnotes-version-title">Version 1</h3>
        <p class="devnotes-version-label">Version actuellement en ligne</p>
        
    <div class="devnote-day">
        <h4 class="devnote-date">19 Août 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Ajout d'une colonne pilote_principal_id dans la table pilotes afin de gérer les doublons lorsqu'un titulaire remplace un pilote dans une autre écurie</li>
            <li class="tag-improve">Modification du classement pilotes pour agréger les points et les places du doublon sur le pilote principal, sans afficher le doublon</li>
            <li class="tag-improve">Les points du doublon restent rattachés à l'écurie de la voiture dans le classement écuries, et non à l'écurie d'origine du pilote</li>
            <li class="tag-improve">Modification de la page edit_resultats avec l'affichage de l'écurie de rattachement pour faciliter la saisie en cas de doublons</li>
            <li class="tag-improve">Modification des prédictions afin de tenir compte des doublons de pilotes sans les afficher plusieurs fois</li>
        </ul>
    </div>
    
    <div class="devnote-day">
        <h4 class="devnote-date">26 Juillet 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Ajout du circuit de Malaysie dans le calendrier (BDD, horaires à confirmer)</li>
            <li class="tag-fix">Modification de la logique d'affichage des manches dans le calendrier en intégrant un ordre des courses au lieu d'un tri par ID</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">03 Juillet 2026</h4>
        <ul class="devnote-list">
            <li class="tag-fix">Correction de la fonctionnalité d'enregistrement des identifiants pour une connexion plus rapide via l'enregistrement automatique des identifiants dans le navigateur</li>
            <li class="tag-improve">Séparation des méthodes de connexion et de création de compte sur login.php avec la création d'une page register.php</li>
            <li class="tag-fix">Correction d'un bug sur l'affichage des pilotes dans la page des infos</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">25 Mai 2026</h4>
        <ul class="devnote-list">
            <li class="tag-fix">Modification de l'affichage des pilotes réserves dans la page des infos</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">27 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-fix">Modification de classement pour inclure les pilotes réserve uniquement si ils ont participé à une course</li>
            <li class="tag-fix">Modification de edit_resultats pour permettre les pilotes réserves en limitant a 22 pilotes dans le classement des sessions.</li>
            <li class="tag-fix">Ajustement de la BDD pour inclure les pilotes réserves.</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">25 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Ajout de l'opening 2026 au clic sur la photo de classe (mutée car video non officielle)</li>
            <li class="tag-fix">Modification d'une balise erronnée dans infos</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">19 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Ajout de la photo de classe 2026 dans infos</li>
            <li class="tag-fix">Correction d'un bug concernant l'affichage des tableaux dans edit_resultats</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">17 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Amélioration du classement pour tenir compte de la meilleure position de course en cas d'égalité de points</li>
            <li class="tag-improve">Ajout d'un flux RSS des actualités F1 sur la page d'accueil</li>
            <li class="tag-fix">Modification de la logique d'affichage des prédictions qui vont rester visibles 1 jour après la fin de la course</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">15 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Ajout d'un statut pour les courses annulées dans la BDD</li>
            <li class="tag-fix">Modification de l'affichage des courses annulées dans calendrier</li>
            <li class="tag-fix">Modification de la gestion du compte à rebours de la page d'accueil avant la prochaine course en tenant compte des courses annulées</li>
            <li class="tag-fix">Correction d'un bug concernant la récupération de la structure des week-ends pour l'affichage des tableaux dans resultats et edit_resultats</li>
            <li class="tag-fix">Modification de la logique pour afficher "Week-end annulé" lorsqu'une course est annulée et bloquer l'accès a l'édition des résultats</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">13 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Ajout de séparations visuelles pour voir les différences entre les sessions de qualifications dans resultats et edit_resultats</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">08 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-fix">Correction couleur d'affichage course si la course est terminée (se base sur l'heure et pas sur le jour)</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">07 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-fix">Correction pour le maintien de l'ordre des pilotes en fonction de leur position par session dans edit_resultats</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">06 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Optimisation de la page edit_resultats avec integration d'une option vide par défaut</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">04 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-security">Sécurité : ajout de Content Security Policy (CSP) pour bloquer le contenu non autorisé</li>
            <li class="tag-security">Sécurité : redirection forcée vers HTTPS</li>
            <li class="tag-security">Sécurité : ajout des headers HSTS, X-Frame-Options, X-Content-Type-Options et Referrer-Policy</li>
            <li class="tag-security">Scripts externes autorisés : CDN Bootstrap et Google Tag Manager</li>
            <li class="tag-security">Externalisation du JS dans des scripts propres</li>
            <li class="tag-security">Amélioration sécurité: Ajout de protection CSRF sur l'edition de résultats des courses</li>
            <li class="tag-security">Amélioration sécurité: Ajout de protection CSRF sur le bingo</li>
            <li class="tag-security">Amélioration sécurité: Ajout de protection CSRF sur le formulaire de contact</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">03 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-security">Amélioration sécurité: Ajout de protection CSRF sur les tickets support</li>
            <li class="tag-security">Amélioration sécurité: Ajout de protection CSRF sur les predictions</li>
            <li class="tag-security">Amélioration sécurité: Ajout de protection CSRF à l'API de chat</li>
            <li class="tag-security">Amélioration sécurité: Centralisation de la configuration de session</li>
            <li class="tag-security">Amélioration sécurité: Ajout token CSRF contre les requêtes malveillantes</li>
            <li class="tag-security">Amélioration sécurité: régénération de l'ID de session après connexion</li>
            <li class="tag-security">Amélioration sécurité: Anti-brute-force avec compteur de tentative de connexion</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">02 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Correction de la logique d'affichage des boutons "Prendre en charge" et "Répondre/Voir la réponse" dans tickets</li>
            <li class="tag-fix">Correction de l'enregistrement de la réponse dans la BDD même si utilisateur invité</li>
            <li class="tag-fix">Correction ticket_update pour afficher la réponse envoyée et retrait du select de priorité si ticket traité</li>
            <li class="tag-fix">Correction edit_resultat (preremplissage si valeur deja presentes, protection contre les doublons et affichage visuel des doublons par tableaux)</li>
            <li class="tag-fix">Correction tableau course dans resultats (retrait colonne position en doublon)</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">01 Mars 2026</h4>
        <ul class="devnote-list">
            <li class="tag-fix">Correction affichage photos pilotes vue mobile</li>
            <li class="tag-feature">Modification des pages resultats.php et edit_resultats.php pour inclure les temps des EL et qualifications</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">28 Février 2026</h4>
        <ul class="devnote-list">
            <li class="tag-fix">Correction bug concernant la réponse des tickets support par mail</li>
            <li class="tag-feature">Ajout favicon (logo dans l'onglet du navigateur)</li>
            <li class="tag-fix">Correction des balises &lt;title&gt; des pages (titres dans l'onglet du navigateur)</li>
            <li class="tag-fix">Correction logique fermeture des votes des prédictions (passe de 3h a 5h vu que basé sur l'heure de l'emission d'après-course)</li>
            <li class="tag-fix">Changement de la page infos.php</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">27 Février 2026</h4>
        <ul class="devnote-list">
            <li class="tag-feature">Voir le bingo des autres utilisateurs</li>
            <li class="tag-improve">Correction lien mort dans devNotes</li>
            <li class="tag-improve">Lien "Le bingo" renommé pour differencier avec la nouvelle page</li>
            <li class="tag-improve">Modification de la logique du décompte avant la prochaine course</li>
            <li class="tag-improve">Redimensionnement des logos écuries dans "classement" et centrage vertical des éléments des tableaux</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">26 février 2026</h4>
        <ul class="devnote-list">
            <li class="tag-feature">Création page DevNotes</li>
            <li class="tag-improve">Ajustements logique affichage prédictions: la colonne s'affichera 1j avant et s'enlevera 1j après le week-end. Le formulaire se fermera 3h avant le debut de la course</li>
            <li class="tag-feature">Ajout logo du site</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">25 février 2026</h4>
        <ul class="devnote-list">
            <li class="tag-feature">Création page contact</li>
            <li class="tag-feature">Création système support par tickets</li>
            <li class="tag-feature">Envoi de réponse tickets sur page contact et/ou email</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">24 février 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Ajustements dump SQL global</li>
            <li class="tag-improve">Optimisation requêtes SQL</li>
            <li class="tag-fix">Stabilisation affichage général</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">23 février 2026</h4>
        <ul class="devnote-list">
            <li class="tag-feature">Requête SQL récupération circuits</li>
            <li class="tag-improve">Optimisation structure affichage page prédictions</li>
            <li class="tag-improve">Amélioration logique données prédictions</li>
            <li class="tag-fix">Correction problème zoom lié aux unités vh</li>
            <li class="tag-feature">Mise en place footer sticky</li>
            <li class="tag-improve">Stabilisation taille police footer malgré zoom</li>
            <li class="tag-improve">Désactivation suggestions automatiques navigateur</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">22 février 2026</h4>
        <ul class="devnote-list">
            <li class="tag-improve">Optimisation affichage de la page Bingo</li>
            <li class="tag-fix">Correction problème zoom lié aux unités vh</li>
            <li class="tag-feature">Mise en place footer sticky</li>
            <li class="tag-improve">Stabilisation taille police footer malgré zoom</li>
            <li class="tag-improve">Désactivation suggestions automatiques navigateur</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">21 février 2026</h4>
        <ul class="devnote-list">
            <li class="tag-security">Révocation ancien certificat SSL</li>
            <li class="tag-security">Réaffectation certificat SSL au site</li>
            <li class="tag-security">Activation HTTPS fonctionnel</li>
            <li class="tag-feature">Migration base de données vers serveur</li>
            <li class="tag-fix">Correction erreur “Aucune base sélectionnée”</li>
            <li class="tag-security">Vérification sécurisation fichier database.php</li>
        </ul>
    </div>

    <div class="devnote-day">
        <h4 class="devnote-date">20 février 2026</h4>
        <ul class="devnote-list">
            <li class="tag-feature">Mise en ligne du site avec structure et contenu de base</li>
        </ul>
    </div>
    </section>
</article>
