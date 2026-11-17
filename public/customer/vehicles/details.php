<?php

require_once dirname(__DIR__) . '/_bootstrap.php';

$controller = new CustomerController();
$viewData = $controller->vehicleDetails($_GET);
$pageTitle = $viewData['page_title'];
$vehicle = $viewData['vehicle'];
$backPath = 'vehicles/index.php' . (($viewData['catalogue_query'] ?? '') !== '' ? '?' . $viewData['catalogue_query'] : '');
require dirname(__DIR__) . '/components/layout/header.php';
?>
<div class="customer-shell">
    <?php require dirname(__DIR__) . '/components/layout/sidebar.php'; ?>
    <div class="customer-main">
        <?php require dirname(__DIR__) . '/components/layout/navbar.php'; ?>
        <main class="customer-content" id="main-content">
            <?php require dirname(__DIR__) . '/components/layout/page-header.php'; ?>
            <p><a class="text-link vehicle-back-link" href="<?= htmlspecialchars(customer_url($backPath), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to catalogue</a></p>

            <?php if ($viewData['database_error']): ?>
                <section class="empty-state" aria-labelledby="vehicle-detail-error-title">
                    <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('alert') ?></span>
                    <h2 id="vehicle-detail-error-title">Vehicle details are temporarily unavailable</h2>
                    <p>We could not load this vehicle from the database. Please try again later.</p>
                </section>
            <?php elseif (!is_array($vehicle)): ?>
                <section class="empty-state" aria-labelledby="vehicle-not-found-title">
                    <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('search') ?></span>
                    <h2 id="vehicle-not-found-title">Vehicle not found</h2>
                    <p>This vehicle does not exist or is not an approved Customer catalogue listing.</p>
                    <a class="button button--secondary" href="<?= htmlspecialchars(customer_url($backPath), ENT_QUOTES, 'UTF-8') ?>">Browse approved vehicles</a>
                </section>
            <?php else: ?>
                <?php require dirname(__DIR__) . '/components/vehicle/vehicle-gallery.php'; ?>

                <div class="vehicle-detail-layout">
                    <div class="vehicle-detail-layout__main">
                        <section class="card vehicle-detail-overview" aria-labelledby="vehicle-overview-title">
                            <div class="vehicle-detail-overview__heading">
                                <div>
                                    <p class="eyebrow"><?= htmlspecialchars(ucfirst((string) $vehicle['vehicle_type']), ENT_QUOTES, 'UTF-8') ?> listing</p>
                                    <h2 id="vehicle-overview-title"><?= htmlspecialchars(trim((string) $vehicle['make'] . ' ' . (string) $vehicle['model']), ENT_QUOTES, 'UTF-8') ?></h2>
                                </div>
                                <?php $vehicleStatus = (string) $vehicle['status']; require dirname(__DIR__) . '/components/vehicle/availability-badge.php'; ?>
                            </div>
                            <div class="vehicle-detail-prices">
                                <p class="vehicle-price"><strong>LKR <?= number_format((float) $vehicle['price_per_day'], 2) ?></strong><span>/ day self-drive</span></p>
                                <?php if ($vehicle['price_with_driver_per_day'] !== null): ?>
                                    <p class="vehicle-price"><strong>LKR <?= number_format((float) $vehicle['price_with_driver_per_day'], 2) ?></strong><span>/ day with driver</span></p>
                                <?php endif; ?>
                            </div>
                            <div class="vehicle-trust-row">
                                <span class="status-badge status-badge--success"><?= customer_icon('check') ?> Approved vehicle</span>
                                <span><?= (int) $vehicle['approved_document_count'] ?> approved document<?= (int) $vehicle['approved_document_count'] === 1 ? '' : 's' ?> recorded</span>
                            </div>
                        </section>

                        <?php require dirname(__DIR__) . '/components/vehicle/vehicle-specs.php'; ?>
                        <?php require dirname(__DIR__) . '/components/vehicle/rental-usage-details.php'; ?>
                        <?php require dirname(__DIR__) . '/components/vehicle/booking-rules.php'; ?>

                        <?php if ((string) ($vehicle['status'] ?? '') === 'available'): ?>
                            <section class="card vehicle-booking-handoff" aria-labelledby="booking-handoff-title">
                                <h2 id="booking-handoff-title">Ready to book?</h2>
                                <p>Open the Booking Request Form to choose dates, service type, a Driver when needed, and accept the Booking Agreement.</p>
                                <a class="button button--primary" href="<?= htmlspecialchars(customer_url('bookings/create.php?vehicle_id=' . (int) $vehicle['id']), ENT_QUOTES, 'UTF-8') ?>">Create Booking</a>
                            </section>
                        <?php else: ?>
                            <section class="card vehicle-booking-handoff" aria-labelledby="booking-unavailable-title">
                                <h2 id="booking-unavailable-title">Booking unavailable</h2>
                                <p>This vehicle is not currently eligible for a new booking request. Check availability or return to the catalogue to choose another vehicle.</p>
                            </section>
                        <?php endif; ?>
                    </div>

                    <aside class="vehicle-detail-layout__aside" aria-label="Booking readiness">
                        <?php require dirname(__DIR__) . '/components/vehicle/owner-card.php'; ?>
                        <section class="card vehicle-booking-handoff" aria-labelledby="booking-handoff-title">
                            <div class="card__header"><h2 id="booking-handoff-title">Booking safety</h2><span class="card__header-icon" aria-hidden="true"><?= customer_icon('shield') ?></span></div>
                            <p>Vehicle availability, Customer verification, Driver eligibility, dates, and prices are rechecked securely when you send the request.</p>
                        </section>
                    </aside>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require dirname(__DIR__) . '/components/layout/footer.php'; ?>
