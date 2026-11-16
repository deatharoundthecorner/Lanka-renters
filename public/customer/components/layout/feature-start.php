<?php
$pageTitle = (string) ($viewData['page_title'] ?? 'Customer');
require __DIR__ . '/header.php';
?>
<div class="customer-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="customer-main">
        <?php require __DIR__ . '/navbar.php'; ?>
        <main class="customer-content" id="main-content">
            <?php require __DIR__ . '/page-header.php'; ?>
            <?php if (is_array($viewData['flash'] ?? null)): ?>
                <?php $featureFlashTone = ($viewData['flash']['tone'] ?? '') === 'success' ? 'success' : 'error'; ?>
                <div class="alert alert--<?= $featureFlashTone ?>" role="status">
                    <?= customer_icon($featureFlashTone === 'success' ? 'check' : 'alert') ?>
                    <p><?= htmlspecialchars((string) ($viewData['flash']['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endif; ?>

