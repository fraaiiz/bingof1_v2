<?php
$hostName = trim((string) ($hostName ?? ''));
$hostAddress = trim((string) ($hostAddress ?? ''));
$hostPhone = trim((string) ($hostPhone ?? ''));
?>
<article class="cgu-page">
    <h1>Conditions générales d’utilisation</h1>
    <p class="cgu-updated">En vigueur au 26 octobre 2026</p>

    <p>Les présentes conditions générales d’utilisation (les «&nbsp;CGU&nbsp;») encadrent l’accès et l’utilisation du site bingof1.fr (le «&nbsp;Site&nbsp;»), édité par la personne désignée à l’article 1 (l’«&nbsp;Éditeur&nbsp;»).</p>
    <p>Toute personne qui consulte le Site ou crée un compte (l’«&nbsp;Utilisateur&nbsp;») accepte les présentes CGU. La création d’un compte est réservée aux personnes âgées d’au moins 15 ans. Entre 15 et 18 ans, l’inscription suppose l’autorisation du représentant légal.</p>

    <h2>Article 1 — Éditeur et hébergeur</h2>
    <p>Le Site est édité à titre non professionnel par Florian Teindas, directeur de la publication.</p>
    <p>Le contact s’exerce par le <a href="/contact">formulaire de contact</a> ou par email à <a href="mailto:dev@bingof1.fr">dev@bingof1.fr</a>.</p>
    <?php if ($hostName !== ''): ?>
        <p>L’hébergement est assuré par: <?= htmlspecialchars($hostName, ENT_QUOTES, 'UTF-8') ?><?php if ($hostAddress !== ''): ?>, <?= htmlspecialchars($hostAddress, ENT_QUOTES, 'UTF-8') ?><?php endif; ?><?php if ($hostPhone !== ''): ?>. Téléphone : <?= htmlspecialchars($hostPhone, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>.</p>
    <?php else: ?>
        <p>Le nom et l’adresse de l’hébergeur seront indiqués dans cet article dès qu’ils sont connus.</p>
    <?php endif; ?>

    <h2>Article 2 — Services</h2>
    <p>Le Site est un service gratuit de divertissement et d’information autour de la Formule 1. Il propose&nbsp;:</p>
    <ul>
        <li>des pages d’information, un calendrier, des classements et des pages par saison&nbsp;;</li>
        <li>une grille personnelle, «&nbsp;Mon bingo&nbsp;»&nbsp;: vingt-cinq cases dont le texte est rédigé par l’Utilisateur, qu’il peut marquer comme réalisées puis enregistrer&nbsp;;</li>
        <li>la page «&nbsp;Les Bingos&nbsp;», qui affiche les grilles enregistrées, avec le pseudo de leur auteur&nbsp;;</li>
        <li>des pronostics&nbsp;: un Utilisateur connecté peut, pendant la période ouverte pour une course, désigner trois pilotes pour les trois premières places. Ces choix sont affichés sur l’accueil avec son pseudo&nbsp;;</li>
        <li>un salon de discussion sur l’accueil. La lecture est ouverte aux visiteurs. La publication est réservée aux comptes connectés. Le salon peut être fermé&nbsp;;</li>
        <li>un formulaire de contact, qui crée un ticket&nbsp;: catégorie, objet, message, adresse email, course concernée le cas échéant, et page depuis laquelle le message est envoyé. L’Utilisateur connecté peut consulter ses tickets et la réponse de l’Éditeur.</li>
    </ul>
    <p>L’accès au Site suppose un accès à Internet. Les frais de matériel, de logiciel et de connexion restent à la charge de l’Utilisateur.</p>

    <h2>Article 3 — Jeu gratuit</h2>
    <p>Le bingo et les pronostics sont des fonctions de divertissement, gratuites, sans mise, sans dépôt d’argent et sans gain, lot ou récompense. Le Site n’est pas un service de paris sportifs ni un jeu d’argent.</p>

    <h2>Article 4 — Compte</h2>
    <p>Un compte est nécessaire pour enregistrer une grille, publier un pronostic, écrire dans le salon et suivre ses tickets.</p>
    <p>L’inscription demande un pseudo, une adresse email et un mot de passe d’au moins 8 caractères, avec une majuscule, une minuscule, un chiffre et un caractère spécial. L’Utilisateur garantit que ces informations sont exactes et les tient à jour.</p>
    <p>Le pseudo est visible des autres visiteurs à côté de la grille, des pronostics et des messages.</p>
    <p>L’Utilisateur conserve ses identifiants secrets. Toute action réalisée depuis son compte lui est attribuée, sauf information contraire apportée à l’Éditeur.</p>
    <p>L’Éditeur peut refuser, suspendre ou supprimer un compte en cas de manquement aux CGU, d’atteinte aux droits d’un tiers ou de risque pour le fonctionnement du Site. L’Utilisateur peut demander la suppression de son compte depuis la page Contact.</p>

    <h2>Article 5 — Contenus publiés par l’Utilisateur</h2>
    <p>L’Utilisateur est responsable des textes de sa grille, des messages du salon et des informations envoyées via le contact.</p>
    <p>En enregistrant sa grille, il accepte qu’elle soit visible sur «&nbsp;Les Bingos&nbsp;», associée à son pseudo. En publiant un pronostic ou un message, il accepte le même affichage public du pseudo et du contenu.</p>
    <p>Il conserve ses droits sur ses textes. Il autorise l’Éditeur, à titre gratuit, à les héberger et à les afficher sur le Site pendant leur mise en ligne, dans le seul but de faire fonctionner le service.</p>

    <h2>Article 6 — Conduite et modération</h2>
    <p>L’Utilisateur s’abstient de publier ou de transmettre&nbsp;:</p>
    <ul>
        <li>des propos injurieux, diffamatoires, discriminatoires, ou qui appellent à la haine ou à la violence&nbsp;;</li>
        <li>du harcèlement visant une personne&nbsp;;</li>
        <li>du spam, de la publicité non sollicitée, des liens trompeurs, ou une tentative d’obtenir les identifiants d’un tiers&nbsp;;</li>
        <li>un contenu illicite, ou qui porte atteinte à la vie privée, au droit à l’image ou à un droit de propriété intellectuelle&nbsp;;</li>
        <li>une tentative d’accès non autorisé, de perturbation du Site ou de contournement des limites techniques.</li>
    </ul>
    <p>Le salon limite chaque message à 300 caractères et espace les envois. Un filtre peut refuser un message. L’Éditeur peut retirer un message ou une grille, fermer le salon et suspendre le compte, y compris sans avertissement préalable lorsque la gravité ou l’urgence le justifie.</p>

    <h2>Article 7 — Signalement</h2>
    <p>Un contenu qui paraît illicite peut être signalé depuis la <a href="/contact">page Contact</a>, en indiquant la page concernée, les faits et, si possible, la date et le pseudo. L’Éditeur examine le signalement et retire le contenu lorsqu’il est manifestement illicite ou contraire aux présentes CGU.</p>

    <h2>Article 8 — Propriété intellectuelle</h2>
    <p>La structure du Site, ses textes originaux, sa charte graphique et son code sont la propriété de l’Éditeur. Leur réutilisation non autorisée peut constituer une contrefaçon, dans les limites des exceptions prévues par le code de la propriété intellectuelle.</p>
    <p>Les noms de pilotes, d’écuries et de circuits, ainsi que les logos, photographies et marques liés à la Formule 1, restent la propriété de leurs titulaires. Leur présence sert à décrire la saison et les courses. Elle ne vaut ni affiliation, ni partenariat, ni sponsorisation de la part de la Formula One, de la FIA, des écuries ou des pilotes.</p>

    <h2>Article 9 — Données personnelles</h2>
    <p>L’Éditeur traite les données suivantes&nbsp;:</p>
    <ul>
        <li>le compte&nbsp;: pseudo, adresse email, mot de passe stocké sous forme d’empreinte, rôle&nbsp;;</li>
        <li>la grille de bingo et le texte des cases&nbsp;;</li>
        <li>les pronostics&nbsp;;</li>
        <li>les messages du salon&nbsp;;</li>
        <li>les tickets&nbsp;: email, catégorie, objet, message, course éventuelle, page d’origine et réponse apportée&nbsp;;</li>
        <li>les données techniques de session, nécessaires à la connexion.</li>
    </ul>
    <p>Ces données servent à créer et gérer le compte, à afficher les grilles, les pronostics et le salon, à répondre aux tickets, et à assurer la sécurité et la modération.</p>
    <p>Le traitement du compte et des contenus associés repose sur l’exécution du service demandé. La sécurité, la modération et la défense contre les abus reposent sur l’intérêt légitime de l’Éditeur.</p>
    <p>Les destinataires sont l’hébergeur du Site et, lorsqu’un email lié à un ticket est envoyé, le prestataire d’envoi d’emails. Les données ne sont pas vendues.</p>
    <p>Les données du compte, des grilles, des pronostics et des messages sont conservées tant que le compte existe. Après une demande de suppression, elles sont effacées ou dissociées du compte dans un délai raisonnable, sous réserve de ce que la loi impose de conserver. Les tickets sont conservés le temps de leur traitement, puis supprimés lorsqu’ils ne sont plus nécessaires. Un message retiré du salon peut rester enregistré le temps d’assurer la modération, sans rester visible.</p>
    <p>L’Utilisateur dispose d’un droit d’accès, de rectification, d’effacement, de limitation, de portabilité et d’opposition. Il les exerce depuis son compte lorsqu’une fonction le permet, ou par la page Contact. Une réclamation peut être adressée à la <a href="https://www.cnil.fr" rel="noopener noreferrer">CNIL</a>.</p>

    <h2>Article 10 — Cookies</h2>
    <p>Le Site dépose un cookie de session, nécessaire pour maintenir la connexion et sécuriser les formulaires. Il expire à la fermeture du navigateur ou lors de la déconnexion. Aucun cookie publicitaire ni de mesure d’audience n’est déposé. Ce cookie technique ne fait pas l’objet d’un bandeau de consentement.</p>

    <h2>Article 11 — Responsabilité et disponibilité</h2>
    <p>Les calendriers, classements, résultats et informations sportives sont donnés à titre indicatif. L’Éditeur les met à jour de bonne foi et ne garantit ni leur exhaustivité, ni leur concordance immédiate avec les résultats officiels.</p>
    <p>L’Éditeur met en œuvre des moyens raisonnables pour garder le Site accessible. L’accès peut être interrompu pour maintenance, mise à jour ou incident.</p>
    <p>L’Utilisateur répond des contenus qu’il publie. Le retrait d’un contenu signalé ne transfère pas à l’Éditeur la responsabilité des propos tenus par les Utilisateurs.</p>

    <h2>Article 12 — Modification et droit applicable</h2>
    <p>L’Éditeur peut modifier les CGU. La date indiquée en tête du document est alors mise à jour. L’utilisation du Site après cette date vaut acceptation de la version affichée.</p>
    <p>Les CGU sont soumises au droit français. Sous réserve des règles d’ordre public qui protègent le consommateur, un litige relatif aux CGU est porté devant les tribunaux du ressort du domicile de l’Éditeur, après une tentative de résolution amiable via la page Contact.</p>
</article>
