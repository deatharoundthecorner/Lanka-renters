<?php
$paymentStatusValue = (string) ($paymentStatus ?? 'pending');
$paymentStatusTone = ['pending' => 'warning', 'completed' => 'success', 'failed' => 'error', 'refunded' => 'info'][$paymentStatusValue] ?? 'neutral';
?>
<span class="status-badge status-badge--<?= htmlspecialchars($paymentStatusTone, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($paymentStatusValue), ENT_QUOTES, 'UTF-8') ?></span>
