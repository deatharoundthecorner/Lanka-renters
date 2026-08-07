<?php

if (!defined('CUSTOMER_DASHBOARD_VIEW')) {
    require_once dirname(__DIR__) . '/_bootstrap.php';
    header('Location: ' . customer_url('dashboard.php'), true, 302);
    exit;
}

$pageTitle = $viewData['page_title'];
require dirname(__DIR__) . '/components/layout/header.php';
?>
<div class="customer-shell">
    <?php require dirname(__DIR__) . '/components/layout/sidebar.php'; ?>

    <div class="customer-main">
        <?php require dirname(__DIR__) . '/components/layout/navbar.php'; ?>

        <main class="customer-content" id="main-content">
            <?php require dirname(__DIR__) . '/components/layout/page-header.php'; ?>

            <section class="foundation-card" aria-labelledby="foundation-heading">
                <p class="eyebrow">Phase 1 foundation</p>
                <h2 id="foundation-heading">Customer MVC is connected</h2>
                <p>
                    This request passed through the Customer controller and model before this view was rendered.
                    Booking and vehicle features intentionally begin in a later phase.
                </p>
            </section>
        </main>
    </div>
</div>
<?php require dirname(__DIR__) . '/components/layout/footer.php'; ?>
