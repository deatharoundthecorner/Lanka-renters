<?php

require_once __DIR__ . '/_bootstrap.php';

if (!isset($customerPageTitle) || !is_string($customerPageTitle)) {
    customer_error_response(404, 'Page not found', 'The requested Customer page does not exist.');
}

$customerPageDescription = isset($customerPageDescription) && is_string($customerPageDescription)
    ? $customerPageDescription
    : 'This route is protected and ready for its feature phase.';
$customerFeaturePhase = isset($customerFeaturePhase) && is_string($customerFeaturePhase)
    ? $customerFeaturePhase
    : 'a later Customer phase';

$requestPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$activeNavRules = [
    '/verification/' => 'verification',
    '/vehicles/' => 'vehicles',
    '/bookings/' => 'bookings',
    '/inspection/' => 'inspection',
    '/payments/' => 'payments',
    '/payment.php' => 'payments',
    '/chat/' => 'chat',
    '/incidents/' => 'incidents',
    '/driver-change/' => 'driver-change',
    '/return/' => 'return',
    '/reviews/' => 'reviews',
    '/reviews.php' => 'reviews',
    '/profile/' => 'profile',
    '/notifications.php' => 'notifications',
];
$activeNav = '';
foreach ($activeNavRules as $routePart => $navKey) {
    if (str_contains($requestPath, $routePart)) {
        $activeNav = $navKey;
        break;
    }
}

$controller = new CustomerController();
$viewData = $controller->foundationPage($customerPageTitle, $customerPageDescription, $activeNav);
$pageTitle = $viewData['page_title'];

require __DIR__ . '/components/layout/header.php';
?>
<div class="customer-shell">
    <?php require __DIR__ . '/components/layout/sidebar.php'; ?>
    <div class="customer-main">
        <?php require __DIR__ . '/components/layout/navbar.php'; ?>
        <main class="customer-content" id="main-content">
            <?php require __DIR__ . '/components/layout/page-header.php'; ?>
            <section class="empty-state" aria-labelledby="feature-placeholder-title">
                <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('clock') ?></span>
                <p class="eyebrow">Protected Customer route</p>
                <h2 id="feature-placeholder-title"><?= htmlspecialchars($customerPageTitle, ENT_QUOTES, 'UTF-8') ?> is ready for its feature phase</h2>
                <p>This destination uses the shared Customer shell. Its business logic will be implemented in <?= htmlspecialchars($customerFeaturePhase, ENT_QUOTES, 'UTF-8') ?>.</p>
                <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">Return to dashboard</a>
            </section>
        </main>
    </div>
</div>
<?php require __DIR__ . '/components/layout/footer.php'; ?>
