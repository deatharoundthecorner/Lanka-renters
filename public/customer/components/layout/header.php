<?php
<<<<<<< HEAD
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
=======
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/app/helpers/AuthHelper.php';
AuthHelper::requireRole('customer');
include 'components/header.php';
?>

<div class="dashboard">

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">

        <?php include 'components/navbar.php'; ?>

        <main class="page-content">

            <!-- Page Content -->

        </main>

    </div>

</div>

<?php include 'components/footer.php'; ?>
>>>>>>> origin/develop
