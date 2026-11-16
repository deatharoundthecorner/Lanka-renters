<?php
$stat = is_array($stat ?? null) ? $stat : [];
$tone = in_array(($stat['tone'] ?? ''), ['info', 'success', 'warning', 'neutral'], true)
    ? (string) $stat['tone']
    : 'neutral';
?>
<article class="stat-card">
    <div class="stat-card__top">
        <span class="stat-card__icon stat-card__icon--<?= htmlspecialchars($tone, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
            <?= customer_icon((string) ($stat['icon'] ?? 'info')) ?>
        </span>
        <span class="status-badge status-badge--<?= htmlspecialchars($tone, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string) ($stat['value'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>
    <h3><?= htmlspecialchars((string) ($stat['label'] ?? 'Statistic'), ENT_QUOTES, 'UTF-8') ?></h3>
    <p><?= htmlspecialchars((string) ($stat['detail'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
</article>
