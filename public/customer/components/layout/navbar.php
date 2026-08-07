<?php
$customerName = (string) ($viewData['customer']['name'] ?? 'Customer');
?>
<header class="customer-navbar">
    <a class="brand" href="<?= htmlspecialchars(customer_url('dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">Lanka Renters</a>
    <span>Signed in as <?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></span>
</header>
