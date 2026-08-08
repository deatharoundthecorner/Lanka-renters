<?php
$documentTitle = isset($pageTitle) ? (string) $pageTitle : 'Customer';
require_once dirname(__DIR__) . '/icon.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') ?> | Lanka Renters</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(customer_url('assets/css/customer-ui.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script src="<?= htmlspecialchars(customer_url('assets/js/customer-ui.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <script src="<?= htmlspecialchars(customer_url('assets/js/customer-workflows.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
