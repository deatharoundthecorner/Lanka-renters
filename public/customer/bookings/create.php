<?php

require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerBookingController.php';

$controller = new CustomerBookingController();
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$viewData = $controller->createPage($_GET, $_POST, $requestMethod);
if ($viewData['redirect'] !== '') {
    header('Location: ' . customer_url($viewData['redirect']), true, 303);
    exit;
}

$pageTitle = $viewData['page_title'];
$vehicle = $viewData['vehicle'];
require dirname(__DIR__) . '/components/layout/header.php';
?>
<div class="customer-shell">
    <?php require dirname(__DIR__) . '/components/layout/sidebar.php'; ?>
    <div class="customer-main">
        <?php require dirname(__DIR__) . '/components/layout/navbar.php'; ?>
        <main class="customer-content" id="main-content">
            <?php require dirname(__DIR__) . '/components/layout/page-header.php'; ?>

            <?php if ($viewData['database_error']): ?>
                <section class="empty-state" aria-labelledby="booking-create-error-title">
                    <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('alert') ?></span>
                    <h2 id="booking-create-error-title">Booking service is temporarily unavailable</h2>
                    <p>We could not load the required booking records. Please try again later.</p>
                </section>
            <?php elseif ($viewData['invalid_vehicle'] || !is_array($vehicle)): ?>
                <section class="empty-state" aria-labelledby="booking-vehicle-not-found-title">
                    <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('car') ?></span>
                    <h2 id="booking-vehicle-not-found-title">Vehicle unavailable</h2>
                    <p>The selected vehicle does not exist or is not currently eligible for Customer booking.</p>
                    <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('vehicles/index.php'), ENT_QUOTES, 'UTF-8') ?>">Search approved vehicles</a>
                </section>
            <?php else: ?>
                <p><a class="text-link" href="<?= htmlspecialchars(customer_url('vehicles/details.php?id=' . (int) $vehicle['id']), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to vehicle details</a></p>
                <div class="booking-form-layout">
                    <aside>
                        <?php require dirname(__DIR__) . '/components/booking/booking-summary.php'; ?>
                        <div class="alert alert--info booking-rule-note">
                            <?= customer_icon('info') ?>
                            <p>Bookings must be at least 28 days and no longer than six calendar months. Date ranges use an exclusive end date.</p>
                        </div>
                    </aside>
                    <div>
                        <?php
                        $formMode = 'create';
                        $formAction = customer_url('bookings/create.php?vehicle_id=' . (int) $vehicle['id']);
                        $formDisabled = $viewData['eligibility_message'] !== '';
                        require dirname(__DIR__) . '/components/booking/booking-form.php';
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php if (is_array($vehicle)): ?>
    <script src="<?= htmlspecialchars(customer_url('assets/js/customer-bookings.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/footer.php'; ?>
