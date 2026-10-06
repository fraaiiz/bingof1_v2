<h1>Le classement <?= htmlspecialchars((string) $annee, ENT_QUOTES, 'UTF-8') ?></h1>

<div class="classement-content">
    <div class="classement-table-left">
        <table>
            <caption>Classement Pilotes</caption>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pilote</th>
                    <th>Écurie</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pilotes === []): ?>
                    <tr>
                        <td colspan="4"><p>Aucun pilote classé pour cette saison.</p></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pilotes as $pilote): ?>
                        <tr>
                            <td><p><?= (int) $pilote['rank'] ?></p></td>
                            <td><p><?= htmlspecialchars($pilote['name'], ENT_QUOTES, 'UTF-8') ?></p></td>
                            <td><p><?= htmlspecialchars($pilote['team_name'] !== '' ? $pilote['team_name'] : '-', ENT_QUOTES, 'UTF-8') ?></p></td>
                            <td><p><?= rtrim(rtrim(number_format($pilote['points'], 1, ',', ''), '0'), ',') ?></p></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="classement-table-right">
        <table>
            <caption>Classement Écurie</caption>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Écurie</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($equipes === []): ?>
                    <tr>
                        <td colspan="3"><p>Aucune écurie enregistrée pour cette saison.</p></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipes as $equipe): ?>
                        <tr>
                            <td><p><?= (int) $equipe['rank'] ?></p></td>
                            <td><p><?= htmlspecialchars($equipe['name'], ENT_QUOTES, 'UTF-8') ?></p></td>
                            <td><p><?= rtrim(rtrim(number_format($equipe['points'], 1, ',', ''), '0'), ',') ?></p></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
