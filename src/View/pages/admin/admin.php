<?php
$adminGroups = [
    [
        'title' => 'Championnat',
        'actions' => [
            ['href' => '/admin/saisons', 'label' => 'Saisons'],
            ['href' => '/admin/ecuries', 'label' => 'Écuries'],
            ['href' => '/admin/pilotes', 'label' => 'Pilotes'],
        ],
    ],
    [
        'title' => 'Comptes',
        'actions' => [
            ['href' => '/admin/users', 'label' => 'Utilisateurs'],
            ['href' => '/admin/tickets', 'label' => 'Tickets'],
        ],
    ],
    [
        'title' => 'Site',
        'actions' => [
            ['href' => '/admin/settings', 'label' => 'Paramètres'],
        ],
    ],
];
?>
<section class="admin-page">
    <h1>Administration</h1>
    <div class="admin-groups">
        <?php foreach ($adminGroups as $group): ?>
            <section class="admin-group">
                <h2><?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <nav aria-label="<?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <ul class="admin-actions">
                        <?php foreach ($group['actions'] as $action): ?>
                            <li>
                                <a class="admin-action-button" href="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </section>
        <?php endforeach; ?>
    </div>
</section>
