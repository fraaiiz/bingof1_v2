<?php
$dateDebut = new DateTimeImmutable($course['date_fp1']);
$dateCourse = new DateTimeImmutable($course['date_course']);
$sessionDefinitions = [
	'fp1' => ['label' => 'Essais Libres 1', 'timeFields' => [['key' => 'time', 'label' => 'Temps']]],
	'fp2' => ['label' => 'Essais Libres 2', 'timeFields' => [['key' => 'time', 'label' => 'Temps']]],
	'fp3' => ['label' => 'Essais Libres 3', 'timeFields' => [['key' => 'time', 'label' => 'Temps']]],
	'qualif_course' => ['label' => 'Qualifications', 'timeFields' => [
		['key' => 'q1_time', 'label' => 'Q1'],
		['key' => 'q2_time', 'label' => 'Q2'],
		['key' => 'q3_time', 'label' => 'Q3'],
	]],
	'qualif_sprint' => ['label' => 'Qualifications Sprint', 'timeFields' => [
		['key' => 'sq1_time', 'label' => 'SQ1'],
		['key' => 'sq2_time', 'label' => 'SQ2'],
		['key' => 'sq3_time', 'label' => 'SQ3'],
	]],
	'sprint' => ['label' => 'Sprint', 'timeFields' => [['key' => 'time', 'label' => 'Temps']], 'showLaps' => true, 'showPoints' => true],
	'course' => ['label' => 'Course', 'timeFields' => [['key' => 'time', 'label' => 'Temps']], 'showLaps' => true, 'showPoints' => true],
];
?>
<section class="resultat-page">
	<a class="resultat-back" href="/saisons/<?= rawurlencode((string) $annee) ?>/calendrier">Retour au calendrier <?= htmlspecialchars((string) $annee, ENT_QUOTES, 'UTF-8') ?></a>
	<header class="resultat-header">
		<p class="resultat-round">Manche <?= htmlspecialchars((string) $course['num_manche'], ENT_QUOTES, 'UTF-8') ?></p>
		<h1>Résultats du circuit <?= htmlspecialchars($course['pays_circuit'], ENT_QUOTES, 'UTF-8') ?></h1>
		<p class="resultat-circuit"><?= htmlspecialchars($course['nom_circuit'], ENT_QUOTES, 'UTF-8') ?></p>
		<p class="resultat-dates"><?= $dateDebut->format('d/m/Y') ?> - <?= $dateCourse->format('d/m/Y') ?></p>
	</header>
	<?php if ($course['is_cancelled'] === 'yes'): ?>
		<p class="resultat-cancelled" role="status">Cette course a été annulée et n'a pas eu lieu. Aucun résultat n'est disponible.</p>
	<?php else: ?>
	<div class="resultat-sessions">
		<?php if ($sessions === []): ?>
			<p class="resultat-sessions-empty">Aucune séance n'est configurée pour ce format.</p>
		<?php endif; ?>
		<?php foreach ($sessions as $index => $sessionRow): ?>
			<?php
			$sessionId = (int) $sessionRow['id'];
			$sessionType = (string) $sessionRow['session_type'];
			$session = $sessionDefinitions[$sessionType] ?? [
				'label' => $sessionType,
				'timeFields' => [['key' => 'time', 'label' => 'Temps']],
			];
			$sessionResults = $resultsBySession[$sessionId] ?? [];
			$pointsByPosition = $sessionType === 'sprint'
				? [1 => 8, 2 => 7, 3 => 6, 4 => 5, 5 => 4, 6 => 3, 7 => 2, 8 => 1]
				: [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1];
			$leadersByTimeField = [];
			foreach ($session['timeFields'] as $timeField) {
				foreach ($sessionResults as $candidate) {
					$candidateHasStatus = !empty($candidate['dnf']) || !empty($candidate['dsq']) || !empty($candidate['np']);
					if ($candidateHasStatus || $candidate[$timeField['key']] === null) {
						continue;
					}

					if (!isset($leadersByTimeField[$timeField['key']])
						|| (int) $candidate[$timeField['key']] < (int) $leadersByTimeField[$timeField['key']][$timeField['key']]) {
						$leadersByTimeField[$timeField['key']] = $candidate;
					}
				}
			}
			?>
			<details class="resultat-session">
				<summary>
					<span><?= htmlspecialchars($session['label'], ENT_QUOTES, 'UTF-8') ?></span>
					<span class="resultat-session-arrow" aria-hidden="true">&#9660;</span>
				</summary>
				<div class="resultat-table-responsive">
					<table class="resultat-table">
						<thead>
							<tr>
								<th scope="col">#</th>
								<th scope="col">Pilote</th>
								<?php foreach ($session['timeFields'] as $timeField): ?>
									<th scope="col"><?= htmlspecialchars($timeField['label'] . ' / écart', ENT_QUOTES, 'UTF-8') ?></th>
								<?php endforeach; ?>
								<?php if (!empty($session['showLaps'])): ?>
									<th scope="col">Tours</th>
								<?php endif; ?>
								<?php if (!empty($session['showPoints'])): ?>
									<th scope="col">Points</th>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php if ($sessionResults === []): ?>
								<tr>
									<?php
									$columnCount = 2 + count($session['timeFields'])
										+ (!empty($session['showLaps']) ? 1 : 0)
										+ (!empty($session['showPoints']) ? 1 : 0);
									?>
									<td class="resultat-empty" colspan="<?= $columnCount ?>">Les résultats seront affichés ici.</td>
								</tr>
							<?php else: ?>
								<?php foreach ($sessionResults as $result): ?>
									<?php
									$position = (int) $result['position'];
									$isQualification = in_array($sessionType, ['qualif_course', 'qualif_sprint'], true);
									$status = !empty($result['dnf'])
										? 'DNF'
										: (!empty($result['dsq']) ? 'DSQ' : (!empty($result['np']) ? 'NP' : ''));
									$isQualificationCut = $isQualification && in_array($position, [10, 16], true);
									?>
									<tr<?= $isQualificationCut ? ' class="resultat-qualif-cut"' : '' ?>>
										<td class="resultat-pos-cell">
											<?= htmlspecialchars((string) $position, ENT_QUOTES, 'UTF-8') ?>
											<?php if ($isQualification && $position === 10): ?>
												<span class="resultat-cut-label">Q2</span>
											<?php endif; ?>
											<?php if ($isQualification && $position === 16): ?>
												<span class="resultat-cut-label">Q1</span>
											<?php endif; ?>
										</td>
										<td>
											<?= htmlspecialchars(trim($result['prenom_pilote'] . ' ' . $result['nom_pilote']), ENT_QUOTES, 'UTF-8') ?>
										</td>
											<?php foreach ($session['timeFields'] as $timeField): ?>
												<?php $time = $result[$timeField['key']]; ?>
												<?php $leader = $leadersByTimeField[$timeField['key']] ?? null; ?>
												<?php
												$lastPositionInStage = match ($timeField['key']) {
													'q1_time', 'sq1_time' => 22,
													'q2_time', 'sq2_time' => 16,
													'q3_time', 'sq3_time' => 10,
													default => 22,
												};
												$eliminatedBeforeStage = $isQualification && $position > $lastPositionInStage;
												?>
											<td>
													<?php if ($status !== ''): ?>
														<span class="resultat-status"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
													<?php elseif ($result['legacy_delta'] !== null): ?>
														<?= htmlspecialchars($result['legacy_delta'], ENT_QUOTES, 'UTF-8') ?>
									<?php elseif ($eliminatedBeforeStage): ?>
										-
												<?php elseif ($time === null): ?>
													<span class="resultat-no-time">NO TIME</span>
													<?php elseif ($leader === null || (int) $result['pilote_id'] === (int) $leader['pilote_id']): ?>
														<?= htmlspecialchars(\App\Service\RaceTime::formatMilliseconds((int) $time), ENT_QUOTES, 'UTF-8') ?>
												<?php else: ?>
														<?php if (!empty($session['showLaps']) && $result['tours'] !== null && $leader['tours'] !== null && (int) $result['tours'] < (int) $leader['tours']): ?>
															+<?= (int) $leader['tours'] - (int) $result['tours'] ?> tour<?= (int) $leader['tours'] - (int) $result['tours'] > 1 ? 's' : '' ?>
														<?php else: ?>
															<?php $gap = max(0, (int) $time - (int) $leader[$timeField['key']]); ?>
															+<?= $gap < 60000 ? number_format($gap / 1000, 3, '.', '') : htmlspecialchars(\App\Service\RaceTime::formatMilliseconds($gap), ENT_QUOTES, 'UTF-8') ?>
														<?php endif; ?>
												<?php endif; ?>
											</td>
										<?php endforeach; ?>
										<?php if (!empty($session['showLaps'])): ?>
											<td><?= $result['tours'] === null ? '-' : htmlspecialchars((string) $result['tours'], ENT_QUOTES, 'UTF-8') ?></td>
										<?php endif; ?>
								<?php if (!empty($session['showPoints'])): ?>
									<?php
									$points = $status === 'DSQ' || $status === 'NP' ? 0 : ($pointsByPosition[$position] ?? 0);
									if (!empty($course['half_points'])) {
										$points /= 2;
									}
									$pointsDisplay = rtrim(rtrim(number_format($points, 1, ',', ''), '0'), ',');
									?>
									<td><?= $points > 0 ? $pointsDisplay : '' ?></td>
								<?php endif; ?>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</details>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</section>
