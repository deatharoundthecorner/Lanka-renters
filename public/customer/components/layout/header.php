<?php
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