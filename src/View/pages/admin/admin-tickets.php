<?php
$categoryLabels = [
    'bug' => 'Bug technique',
    'suggestion' => 'Suggestion',
    'course' => 'Course',
    'compte' => 'Compte',
    'question' => 'Question',
    'autre' => 'Autre',
];
$statusLabels = [
    'nouveau' => 'Nouveau',
    'en_cours' => 'En cours',
    'traite' => 'Traité',
];
$priorityLabels = [
    'basse' => 'Basse',
    'normale' => 'Normale',
    'haute' => 'Haute',
];
$label = static function (array $labels, ?string $value): string {
    if ($value === null || $value === '') {
        return '';
    }

    return $labels[$value] ?? $value;
};
$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

    return $date === false ? $value : $date->format('d/m/Y H:i');
};
$author = static function (array $ticket): string {
    if (!empty($ticket['user_login'])) {
        return (string) $ticket['user_login'];
    }
    if (!empty($ticket['user_email'])) {
        return (string) $ticket['user_email'];
    }

    return 'Invité';
};
?>
<section class="admin-tickets">
    <?php if ($ticket === null): ?>
        <p class="admin-tickets-back"><a href="/admin">Retour à l’administration</a></p>
        <h1>Tickets</h1>
    <?php else: ?>
        <p class="admin-tickets-back"><a href="/admin/tickets">Retour aux tickets</a></p>
        <h1>Ticket n°<?= (int) $ticket['id'] ?></h1>
    <?php endif; ?>

    <?php if (!empty($notice)): ?>
        <p class="admin-tickets-notice admin-tickets-notice--<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
            <?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($ticket === null): ?>
        <?php if ($tickets === []): ?>
            <p class="admin-tickets-empty">Aucun ticket.</p>
        <?php else: ?>
            <div class="admin-tickets-table-wrap">
                <table class="admin-tickets-table">
                    <thead>
                        <tr>
                            <th scope="col">N°</th>
                            <th scope="col">Auteur</th>
                            <th scope="col">Catégorie</th>
                            <th scope="col">Sujet</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Priorité</th>
                            <th scope="col">Date</th>
                            <th scope="col"><span class="admin-tickets-sr">Fiche</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $row): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <td><?= htmlspecialchars($author($row), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($label($categoryLabels, $row['category']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['subject'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($label($statusLabels, $row['status']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($label($priorityLabels, $row['priority']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formatDate($row['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><a class="admin-tickets-open" href="/admin/tickets/<?= (int) $row['id'] ?>">Afficher</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <dl class="admin-ticket-meta">
            <div><dt>Auteur</dt><dd><?= htmlspecialchars($author($ticket), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Email</dt><dd><?= $ticket['user_email'] !== null && $ticket['user_email'] !== '' ? htmlspecialchars($ticket['user_email'], ENT_QUOTES, 'UTF-8') : '—' ?></dd></div>
            <div><dt>Catégorie</dt><dd><?= htmlspecialchars($label($categoryLabels, $ticket['category']), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Statut</dt><dd><?= htmlspecialchars($label($statusLabels, $ticket['status']), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Créé le</dt><dd><?= htmlspecialchars($formatDate($ticket['created_at']), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div>
                <dt>Page</dt>
                <dd>
                    <?php $pageLink = \App\Service\PagePath::internal($ticket['page_url'] ?? null); ?>
                    <?php if ($pageLink !== null): ?>
                        <a href="<?= htmlspecialchars($pageLink, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($pageLink, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </dd>
            </div>
            <?php if ($ticket['nom_circuit'] !== null): ?>
                <div><dt>Course</dt><dd><?= (int) $ticket['annee'] ?> · manche <?= (int) $ticket['num_manche'] ?> · <?= htmlspecialchars($ticket['nom_circuit'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <?php endif; ?>
        </dl>

        <article class="admin-ticket-message">
            <h2><?= htmlspecialchars((string) $ticket['subject'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= nl2br(htmlspecialchars((string) $ticket['message'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php if (!empty($ticket['browser_info'])): ?>
                <p class="admin-tickets-empty">Navigateur : <?= htmlspecialchars($ticket['browser_info'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </article>

        <?php if ($ticket['status'] !== 'traite'): ?>
            <form method="post" action="/admin/tickets/<?= (int) $ticket['id'] ?>/prise-en-charge" class="admin-ticket-form">
                <h2>Suivi</h2>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <label>Priorité
                    <select name="priority" required>
                        <?php foreach ($priorityLabels as $priority => $priorityLabel): ?>
                            <option value="<?= htmlspecialchars($priority, ENT_QUOTES, 'UTF-8') ?>"<?= $ticket['priority'] === $priority ? ' selected' : '' ?>><?= htmlspecialchars($priorityLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit"><?= $ticket['status'] === 'nouveau' ? 'Prendre en charge' : 'Enregistrer la priorité' ?></button>
            </form>
        <?php endif; ?>

        <form method="post" action="/admin/tickets/<?= (int) $ticket['id'] ?>/reponse" class="admin-ticket-form">
            <h2>Réponse</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <label>Message
                <textarea name="response" maxlength="5000" required><?= htmlspecialchars((string) ($ticket['response'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <button type="submit"><?= $ticket['status'] === 'traite' ? 'Mettre à jour la réponse' : 'Envoyer la réponse' ?></button>
        </form>
    <?php endif; ?>
</section>
