<?php
$documentTitle = isset($pageTitle) ? (string) $pageTitle : 'Customer';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') ?> | Lanka Renters</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(customer_url('assets/css/customer-foundation.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
