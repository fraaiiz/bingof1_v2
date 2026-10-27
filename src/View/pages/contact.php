<?php
$categoryLabels = [
    'bug' => 'Bug technique',
    'suggestion' => 'Suggestion',
    'course' => 'Problème lié à une course',
    'compte' => 'Problème de compte',
    'question' => 'Question',
    'autre' => 'Autre',
];
$statusLabels = [
    'nouveau' => 'Nouveau',
    'en_cours' => 'En cours',
    'traite' => 'Traité',
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
?>
<section class="contact-page">
    <h1>Contact</h1>

    <?php if (!empty($notice)): ?>
        <p class="contact-notice contact-notice--<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
            <?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="post" action="/contact" class="contact-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="page_url" value="<?= htmlspecialchars($draft['page_url'], ENT_QUOTES, 'UTF-8') ?>" data-ticket-page>
        <label>Catégorie
            <select name="category" required data-ticket-category>
                <option value="">Choisir…</option>
                <?php foreach ($categoryLabels as $category => $categoryLabel): ?>
                    <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"<?= $draft['category'] === $category ? ' selected' : '' ?>><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label data-ticket-course<?= $draft['category'] === 'course' ? '' : ' hidden' ?>>Course
            <select name="course_id" data-ticket-course-select>
                <option value="">Sélectionner une course…</option>
                <?php foreach ($courses as $course): ?>
                    <?php $coursePage = '/saisons/' . (int) $course['annee'] . '/courses/' . (int) $course['id'] . '/resultats'; ?>
                    <option value="<?= (int) $course['id'] ?>" data-page="<?= htmlspecialchars($coursePage, ENT_QUOTES, 'UTF-8') ?>"<?= (string) $course['id'] === $draft['course_id'] ? ' selected' : '' ?>>
                        <?= (int) $course['annee'] ?> · manche <?= (int) $course['manche'] ?> · <?= htmlspecialchars($course['circuit'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Email
            <input name="user_email" type="email" maxlength="255" value="<?= htmlspecialchars($draft['user_email'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="email">
        </label>
        <p class="contact-help">L’email est facultatif. Il permet de vous recontacter si vous n’êtes pas connecté.</p>
        <label>Sujet
            <input name="subject" type="text" maxlength="255" value="<?= htmlspecialchars($draft['subject'], ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label>Message
            <textarea name="message" maxlength="5000" required><?= htmlspecialchars($draft['message'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
        <button type="submit">Envoyer le message</button>
    </form>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <section class="contact-tickets" aria-labelledby="contact-tickets-title">
            <h2 id="contact-tickets-title">Mes tickets</h2>
            <?php if ($tickets === []): ?>
                <p class="contact-help">Aucun ticket pour le moment.</p>
            <?php else: ?>
                <div class="contact-table-wrap">
                    <table class="contact-table">
                        <thead>
                            <tr>
                                <th scope="col">N°</th>
                                <th scope="col">Catégorie</th>
                                <th scope="col">Sujet</th>
                                <th scope="col">Statut</th>
                                <th scope="col">Course</th>
                                <th scope="col">Date</th>
                                <th scope="col">Réponse</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td><?= (int) $ticket['id'] ?></td>
                                    <td><?= htmlspecialchars($label($categoryLabels, $ticket['category']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $ticket['subject'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($label($statusLabels, $ticket['status']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($ticket['nom_circuit'] !== null): ?>
                                            <?= (int) ($ticket['annee'] ?? 0) ?> · <?= htmlspecialchars($ticket['nom_circuit'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($formatDate($ticket['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $ticket['response'] !== null && $ticket['response'] !== '' ? nl2br(htmlspecialchars($ticket['response'], ENT_QUOTES, 'UTF-8')) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</section>
<script src="/assets/js/contact.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/contact.js') ?>" defer></script>
