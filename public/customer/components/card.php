<?php
$cardTitle = isset($cardTitle) ? (string) $cardTitle : '';
$cardText = isset($cardText) ? (string) $cardText : '';
?>
<article class="card">
    <?php if ($cardTitle !== ''): ?>
        <div class="card__header">
            <h2><?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?></h2>
        </div>
    <?php endif; ?>
    <?php if ($cardText !== ''): ?>
        <p><?= htmlspecialchars($cardText, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</article>
