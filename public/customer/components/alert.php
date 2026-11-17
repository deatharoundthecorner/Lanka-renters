<?php
$type = in_array(($type ?? ''), ['success', 'warning', 'error', 'info'], true) ? (string) $type : 'info';
$message = (string) ($message ?? '');
?>
<div class="alert alert--<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
    <span class="alert__icon" aria-hidden="true"><?= customer_icon($type === 'success' ? 'check' : 'info') ?></span>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
</div>
