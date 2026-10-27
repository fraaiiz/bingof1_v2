<?php
$roleLabels = [
    'user' => 'Utilisateur',
    'editor' => 'Éditeur',
    'admin' => 'Administrateur',
];
$categoryLabels = [
    'bug' => 'Bug',
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
$usageLabels = [
    'bingos_count' => ['bingo', 'bingos'],
    'predictions_count' => ['pronostic', 'pronostics'],
    'messages_count' => ['message', 'messages'],
    'tickets_count' => ['ticket', 'tickets'],
];
$label = static function (array $labels, string $value): string {
    return $labels[$value] ?? $value;
};
$formatDate = static function (string $value): string {
    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

    return $date === false ? $value : $date->format('d/m/Y H:i');
};
?>
<section class="admin-users">
    <?php if ($user === null): ?>
        <p class="admin-users-back"><a href="/admin">Retour à l’administration</a></p>
        <h1>Utilisateurs</h1>
    <?php else: ?>
        <p class="admin-users-back"><a href="/admin/users">Retour aux utilisateurs</a></p>
        <h1><?= htmlspecialchars($user['user_login'], ENT_QUOTES, 'UTF-8') ?></h1>
    <?php endif; ?>

    <?php if (!empty($notice)): ?>
        <p class="admin-users-notice admin-users-notice--<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
            <?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($confirmation !== null): ?>
        <?php $confirmedUser = $confirmation['action'] === 'update' ? $confirmation['posted'] : $confirmation['user']; ?>
        <section class="admin-users-confirm" aria-labelledby="user-confirm-title">
            <h2 id="user-confirm-title">Confirmation</h2>
            <?php if ($confirmation['action'] === 'update'): ?>
                <p>Attribuer à <strong><?= htmlspecialchars($confirmation['user']['user_login'], ENT_QUOTES, 'UTF-8') ?></strong> le rôle <strong><?= htmlspecialchars($label($roleLabels, $confirmedUser['user_role']), ENT_QUOTES, 'UTF-8') ?></strong> ?</p>
            <?php else: ?>
                <p>Supprimer définitivement <strong><?= htmlspecialchars($confirmation['user']['user_login'], ENT_QUOTES, 'UTF-8') ?></strong> ?</p>
                <?php if ($confirmation['usage'] !== null): ?>
                    <ul class="admin-users-usage">
                        <?php foreach ($usageLabels as $key => $labels): ?>
                            <?php $count = (int) $confirmation['usage'][$key]; ?>
                            <li><?= $count ?> <?= htmlspecialchars($count === 1 ? $labels[0] : $labels[1], ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($confirmation['blocked']): ?>
                <p class="admin-users-notice admin-users-notice--error" role="alert">
                    <?= htmlspecialchars($confirmation['blocked_reason'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="post" action="/admin/users/confirmation" class="admin-users-confirm-actions">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!$confirmation['blocked']): ?>
                    <button type="submit" name="decision" value="confirm">Confirmer</button>
                <?php endif; ?>
                <button type="submit" name="decision" value="cancel" class="admin-users-cancel">Annuler</button>
            </form>
        </section>
    <?php elseif ($user === null): ?>
        <?php if ($users === []): ?>
            <p class="admin-users-empty">Aucun compte enregistré.</p>
        <?php else: ?>
            <div class="admin-users-table-wrap">
                <table class="admin-users-table">
                    <thead>
                        <tr>
                            <th scope="col">Pseudo</th>
                            <th scope="col">Email</th>
                            <th scope="col">Rôle</th>
                            <th scope="col">Bingos</th>
                            <th scope="col">Pronostics</th>
                            <th scope="col">Messages</th>
                            <th scope="col">Tickets</th>
                            <th scope="col"><span class="admin-users-sr">Fiche</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $account): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($account['user_login'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ((int) $account['id'] === $currentUserId): ?>
                                        <span class="admin-users-you">Vous</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($account['user_email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($label($roleLabels, $account['user_role']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $account['bingos_count'] ?></td>
                                <td><?= (int) $account['predictions_count'] ?></td>
                                <td><?= (int) $account['messages_count'] ?></td>
                                <td><?= (int) $account['tickets_count'] ?></td>
                                <td>
                                    <a class="admin-users-open" href="/admin/users/<?= (int) $account['id'] ?>">Afficher</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <form method="post" action="/admin/users/<?= (int) $user['id'] ?>/suppression" class="admin-users-delete">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="admin-users-cancel">Supprimer le compte</button>
        </form>

        <section class="admin-user-form">
            <h2>Compte</h2>
            <dl class="admin-user-identity">
                <div>
                    <dt>Pseudo</dt>
                    <dd><?= htmlspecialchars($user['user_login'], ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd><?= htmlspecialchars($user['user_email'], ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
            </dl>
            <form method="post" action="/admin/users/<?= (int) $user['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="admin-user-fields">
                    <label>Rôle
                        <select name="user_role" required>
                            <?php foreach ($roleLabels as $role => $roleLabel): ?>
                                <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>"<?= $user['user_role'] === $role ? ' selected' : '' ?>><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="admin-user-actions">
                    <button type="submit">Enregistrer le rôle</button>
                </div>
            </form>
        </section>

        <section class="admin-users-activity" aria-labelledby="user-activity-title">
            <h2 id="user-activity-title">Activité</h2>
            
            <details>
            <summary>Bingos</summary>
            <?php if ($activity['bingos'] === []): ?>
                <p class="admin-users-empty">Aucun bingo.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($activity['bingos'] as $year): ?>
                        <li><a href="/les-bingos?annee=<?= (int) $year ?>&amp;user=<?= (int) $user['id'] ?>">Saison <?= (int) $year ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            </details>
            
            <details>
                <summary>Pronostics</summary>
                <?php if ($activity['predictions'] === []): ?>
                    <p class="admin-users-empty">Aucun pronostic.</p>
                <?php else: ?>
                    <ul class="admin-users-feed">
                        <?php foreach ($activity['predictions'] as $prediction): ?>
                            <li>
                                <span><?= (int) $prediction['annee'] ?> · manche <?= (int) $prediction['manche'] ?> · <?= htmlspecialchars($prediction['circuit'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span>P1 <?= htmlspecialchars($prediction['p1'], ENT_QUOTES, 'UTF-8') ?> · P2 <?= htmlspecialchars($prediction['p2'], ENT_QUOTES, 'UTF-8') ?> · P3 <?= htmlspecialchars($prediction['p3'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span><?= htmlspecialchars($formatDate($prediction['date_post']), ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </details>
            <details>
                <summary>Messages</summary>
                <?php if ($activity['messages'] === []): ?>
                    <p class="admin-users-empty">Aucun message.</p>
                <?php else: ?>
                    <ul class="admin-users-feed">
                        <?php foreach ($activity['messages'] as $message): ?>
                            <li>
                                <span><?= htmlspecialchars($formatDate($message['date_post']), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="admin-users-message"><?= htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($message['deleted']): ?>
                                    <span class="admin-users-deleted">Supprimé</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </details>
            <details>
                <summary>Tickets</summary>
                <?php if ($activity['tickets'] === []): ?>
                    <p class="admin-users-empty">Aucun ticket.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($activity['tickets'] as $ticket): ?>
                            <li>
                                <a href="/admin/tickets/<?= (int) $ticket['id'] ?>"><?= htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') ?></a>
                                · <?= htmlspecialchars($label($categoryLabels, $ticket['category']), ENT_QUOTES, 'UTF-8') ?>
                                · <?= htmlspecialchars($label($statusLabels, $ticket['status']), ENT_QUOTES, 'UTF-8') ?>
                                · <?= htmlspecialchars($formatDate($ticket['created_at']), ENT_QUOTES, 'UTF-8') ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </details>
        </section>
    <?php endif; ?>
</section>
