<?php

require_once __DIR__ . '/_bootstrap.php';

if (!isset($customerPageTitle) || !is_string($customerPageTitle)) {
    customer_error_response(404, 'Page not found', 'The requested Customer page does not exist.');
}

$customerPageDescription = isset($customerPageDescription) && is_string($customerPageDescription)
    ? $customerPageDescription
    : 'This route is protected and ready for its feature phase.';

$controller = new CustomerController();
$viewData = $controller->foundationPage($customerPageTitle, $customerPageDescription);
$pageTitle = $viewData['page_title'];

require __DIR__ . '/components/layout/header.php';
?>
<div class="customer-shell">
    <?php require __DIR__ . '/components/layout/sidebar.php'; ?>
    <div class="customer-main">
        <?php require __DIR__ . '/components/layout/navbar.php'; ?>
        <main class="customer-content" id="main-content">
            <?php require __DIR__ . '/components/layout/page-header.php'; ?>
            <section class="foundation-card">
                <p class="eyebrow">Protected Customer route</p>
                <h2>Foundation ready</h2>
                <p>This feature is intentionally reserved for a later implementation phase.</p>
            </section>
        </main>
    </div>
</div>
<?php require __DIR__ . '/components/layout/footer.php'; ?>
