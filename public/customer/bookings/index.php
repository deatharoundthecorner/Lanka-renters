<?php

require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerBookingController.php';

$controller = new CustomerBookingController();
$viewData = $controller->historyPage($_GET);
$viewData['page_action'] = ['label' => 'Search Vehicles', 'path' => 'vehicles/index.php'];
$pageTitle = $viewData['page_title'];

$bookingPageUrl = static function (int $page) use ($viewData): string {
    $parameters = [];
    if ($viewData['status_filter'] !== '') {
        $parameters['status'] = $viewData['status_filter'];
    }
    if ($page > 1) {
        $parameters['page'] = $page;
    }
    $query = http_build_query($parameters);
    return customer_url('bookings/index.php' . ($query !== '' ? '?' . $query : ''));
};

require dirname(__DIR__) . '/components/layout/header.php';
?>
<div class="customer-shell">
    <?php require dirname(__DIR__) . '/components/layout/sidebar.php'; ?>
    <div class="customer-main">
        <?php require dirname(__DIR__) . '/components/layout/navbar.php'; ?>
        <main class="customer-content" id="main-content">
            <?php require dirname(__DIR__) . '/components/layout/page-header.php'; ?>

            <?php if (is_array($viewData['flash'])): ?>
                <?php $flashTone = $viewData['flash']['tone'] === 'success' ? 'success' : 'error'; ?>
                <div class="alert alert--<?= $flashTone ?>" role="status">
                    <?= customer_icon($flashTone === 'success' ? 'check' : 'alert') ?>
                    <p><?= htmlspecialchars((string) $viewData['flash']['message'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endif; ?>

            <section class="card booking-history-toolbar" aria-labelledby="booking-history-filter-title">
                <div>
                    <p class="eyebrow">Booking history</p>
                    <h2 id="booking-history-filter-title">Filter your bookings</h2>
                </div>
                <form method="get" action="<?= htmlspecialchars(customer_url('bookings/index.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group">
                        <label for="booking-status-filter">Booking status</label>
                        <select id="booking-status-filter" name="status">
                            <?php foreach ($viewData['status_options'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $viewData['status_filter'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="button button--primary" type="submit">Apply filter</button>
                    <a class="text-link" href="<?= htmlspecialchars(customer_url('bookings/index.php'), ENT_QUOTES, 'UTF-8') ?>">Clear</a>
                </form>
            </section>

            <?php if ($viewData['database_error']): ?>
                <section class="empty-state" aria-labelledby="booking-history-error-title">
                    <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('alert') ?></span>
                    <h2 id="booking-history-error-title">Bookings are temporarily unavailable</h2>
                    <p>We could not load your booking records. Please try again later.</p>
                </section>
            <?php else: ?>
                <section aria-labelledby="booking-results-title">
                    <div class="booking-results-heading">
                        <div>
                            <p class="eyebrow">Your records</p>
                            <h2 id="booking-results-title"><?= number_format((int) $viewData['total_results']) ?> booking<?= (int) $viewData['total_results'] === 1 ? '' : 's' ?></h2>
                        </div>
                        <p>Page <?= (int) $viewData['current_page'] ?> of <?= (int) $viewData['total_pages'] ?></p>
                    </div>

                    <?php if ($viewData['bookings'] === []): ?>
                        <div class="empty-state">
                            <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('calendar') ?></span>
                            <h3>No bookings found</h3>
                            <p>Your real booking records will appear here after you book an approved vehicle.</p>
                            <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('vehicles/index.php'), ENT_QUOTES, 'UTF-8') ?>">Search vehicles</a>
                        </div>
                    <?php else: ?>
                        <div class="booking-list">
                            <?php foreach ($viewData['bookings'] as $booking): ?>
                                <?php require dirname(__DIR__) . '/components/booking/booking-card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ((int) $viewData['total_pages'] > 1): ?>
                        <?php $currentPage = (int) $viewData['current_page']; $totalPages = (int) $viewData['total_pages']; ?>
                        <nav class="catalogue-pagination" aria-label="Booking history pages">
                            <?= $currentPage > 1
                                ? '<a href="' . htmlspecialchars($bookingPageUrl($currentPage - 1), ENT_QUOTES, 'UTF-8') . '" aria-label="Previous booking page">Previous</a>'
                                : '<span aria-disabled="true">Previous</span>' ?>
                            <?php for ($pageNumber = max(1, $currentPage - 2); $pageNumber <= min($totalPages, $currentPage + 2); $pageNumber++): ?>
                                <a href="<?= htmlspecialchars($bookingPageUrl($pageNumber), ENT_QUOTES, 'UTF-8') ?>" <?= $pageNumber === $currentPage ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                            <?php endfor; ?>
                            <?= $currentPage < $totalPages
                                ? '<a href="' . htmlspecialchars($bookingPageUrl($currentPage + 1), ENT_QUOTES, 'UTF-8') . '" aria-label="Next booking page">Next</a>'
                                : '<span aria-disabled="true">Next</span>' ?>
                        </nav>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require dirname(__DIR__) . '/components/layout/footer.php'; ?>
