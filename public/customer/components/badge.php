<?php
$status = in_array(($status ?? ''), ['success', 'warning', 'error', 'info', 'neutral'], true)
    ? (string) $status
    : 'neutral';
$text = (string) ($text ?? 'Status');
?>
<span class="status-badge status-badge--<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
    <span class="status-badge__dot" aria-hidden="true"></span>
    <?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?>
</span>
