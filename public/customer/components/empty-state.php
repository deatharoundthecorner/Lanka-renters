<?php
$title = (string) ($title ?? 'No data available');
$description = (string) ($description ?? 'There is nothing to display yet.');
?>
<div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('info') ?></span>
    <h3><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
    <p><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
</div>
