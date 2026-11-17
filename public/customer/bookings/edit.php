<?php

require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerBookingController.php';

$controller = new CustomerBookingController();
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$viewData = $controller->editPage($_GET, $_POST, $requestMethod);
if ($viewData['redirect'] !== '') {
    header('Location: ' . customer_url($viewData['redirect']), true, 303);
    exit;
}

$pageTitle = $viewData['page_title'];
$booking = $viewData['booking'];
$vehicle = $viewData['vehicle'];
require dirname(__DIR__) . '/components/layout/header.php';
?>
<div class="customer-shell">
    <?php require dirname(__DIR__) . '/components/layout/sidebar.php'; ?>
    <div class="customer-main">
        <?php require dirname(__DIR__) . '/components/layout/navbar.php'; ?>
        <main class="customer-content" id="main-content">
            <?php require dirname(__DIR__) . '/components/layout/page-header.php'; ?>
            <p><a class="text-link" href="<?= htmlspecialchars(customer_url('bookings/details.php?id=' . (int) ($booking['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to booking details</a></p>

            <?php if ($viewData['database_error']): ?>
                <section class="empty-state"><span class="empty-state__icon" aria-hidden="true"><?= customer_icon('alert') ?></span><h2>Booking edit is temporarily unavailable</h2><p>Please try again later.</p></section>
            <?php elseif (!is_array($booking) || !is_array($vehicle)): ?>
                <section class="empty-state"><span class="empty-state__icon" aria-hidden="true"><?= customer_icon('search') ?></span><h2>Booking not found</h2><p>This booking does not exist or does not belong to your Customer account.</p><a class="button button--secondary" href="<?= htmlspecialchars(customer_url('bookings/index.php'), ENT_QUOTES, 'UTF-8') ?>">View my bookings</a></section>
            <?php elseif ($viewData['not_editable']): ?>
                <section class="empty-state"><span class="empty-state__icon" aria-hidden="true"><?= customer_icon('clock') ?></span><h2>This booking cannot be edited</h2><p>Only a future booking with pending-payment status can be updated.</p><a class="button button--secondary" href="<?= htmlspecialchars(customer_url('bookings/details.php?id=' . (int) $booking['id']), ENT_QUOTES, 'UTF-8') ?>">Return to booking details</a></section>
            <?php else: ?>
                <div class="booking-form-layout">
                    <aside><?php require dirname(__DIR__) . '/components/booking/booking-summary.php'; ?></aside>
                    <div>
                        <?php
                        $formMode = 'edit';
                        $formAction = customer_url('bookings/edit.php?id=' . (int) $booking['id']);
                        $formDisabled = $viewData['eligibility_message'] !== '';
                        require dirname(__DIR__) . '/components/booking/booking-form.php';
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php if (is_array($booking) && !$viewData['not_editable']): ?>
    <script src="<?= htmlspecialchars(customer_url('assets/js/customer-bookings.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/footer.php'; ?>
