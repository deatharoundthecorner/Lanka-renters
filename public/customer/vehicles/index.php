<?php

require_once dirname(__DIR__) . '/_bootstrap.php';

$controller = new CustomerController();
$viewData = $controller->vehicleCatalogue($_GET);
$pageTitle = $viewData['page_title'];
$filters = $viewData['filters'];
$filterOptions = $viewData['filter_options'];

$catalogueParams = array_filter([
    'keyword' => $filters['keyword'],
    'vehicle_type' => $filters['vehicle_type'],
    'fuel_type' => $filters['fuel_type'],
    'transmission' => $filters['transmission'],
    'service_type' => $filters['service_type'],
    'availability' => $filters['availability'] !== 'available' ? $filters['availability'] : '',
    'min_price' => $filters['min_price'],
    'max_price' => $filters['max_price'],
    'min_seats' => $filters['min_seats'],
    'start_date' => $filters['start_date'],
    'end_date' => $filters['end_date'],
    'sort' => $viewData['sort'] !== 'newest' ? $viewData['sort'] : '',
], static fn ($value): bool => $value !== '' && $value !== null);

$catalogueUrl = static function (int $page) use ($catalogueParams): string {
    $params = $catalogueParams;
    if ($page > 1) {
        $params['page'] = $page;
    }
    $query = http_build_query($params);
    return customer_url('vehicles/index.php' . ($query !== '' ? '?' . $query : ''));
};

$activeFilterLabels = [];
if ($filters['keyword'] !== '') {
    $activeFilterLabels[] = 'Search: ' . $filters['keyword'];
}
foreach ([
    'vehicle_type' => 'vehicle_types',
    'fuel_type' => 'fuel_types',
    'transmission' => 'transmissions',
    'service_type' => 'service_types',
] as $filterName => $optionGroup) {
    if ($filters[$filterName] !== '' && isset($filterOptions[$optionGroup][$filters[$filterName]])) {
        $activeFilterLabels[] = $filterOptions[$optionGroup][$filters[$filterName]];
    }
}
if ($filters['availability'] === 'all') {
    $activeFilterLabels[] = 'All approved statuses';
}
if ($filters['min_price'] !== null || $filters['max_price'] !== null) {
    $activeFilterLabels[] = 'Daily price: Rs. '
        . ($filters['min_price'] !== null ? number_format((float) $filters['min_price'], 2) : '0.00')
        . ' – '
        . ($filters['max_price'] !== null ? 'Rs. ' . number_format((float) $filters['max_price'], 2) : 'any');
}
if ($filters['min_seats'] !== null) {
    $activeFilterLabels[] = (int) $filters['min_seats'] . '+ seats';
}
if ($filters['start_date'] !== '' && $filters['end_date'] !== '') {
    $activeFilterLabels[] = $filters['start_date'] . ' to ' . $filters['end_date'];
}

require dirname(__DIR__) . '/components/layout/header.php';
?>
<div class="customer-shell">
    <?php require dirname(__DIR__) . '/components/layout/sidebar.php'; ?>
    <div class="customer-main">
        <?php require dirname(__DIR__) . '/components/layout/navbar.php'; ?>
        <main class="customer-content" id="main-content">
            <?php require dirname(__DIR__) . '/components/layout/page-header.php'; ?>

            <section class="vehicle-filters card" aria-labelledby="vehicle-filter-title">
                <div class="vehicle-filters__heading">
                    <div>
                        <p class="eyebrow">Refine results</p>
                        <h2 id="vehicle-filter-title">Search and filters</h2>
                    </div>
                    <a class="text-link" href="<?= htmlspecialchars(customer_url('vehicles/index.php'), ENT_QUOTES, 'UTF-8') ?>">Clear all</a>
                </div>

                <?php if ($viewData['validation_errors'] !== []): ?>
                    <div class="alert alert--warning" role="alert">
                        <?= customer_icon('alert') ?>
                        <div>
                            <strong>Some filters were not applied.</strong>
                            <ul>
                                <?php foreach ($viewData['validation_errors'] as $validationError): ?>
                                    <li><?= htmlspecialchars($validationError, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="vehicle-filter-form" method="get" action="<?= htmlspecialchars(customer_url('vehicles/index.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group vehicle-filter-form__keyword">
                        <label for="vehicle-keyword">Make or model</label>
                        <input id="vehicle-keyword" name="keyword" type="search" maxlength="80" value="<?= htmlspecialchars($filters['keyword'], ENT_QUOTES, 'UTF-8') ?>" placeholder="For example, Toyota Prius">
                    </div>
                    <div class="form-group">
                        <label for="vehicle-type">Vehicle type</label>
                        <select id="vehicle-type" name="vehicle_type">
                            <option value="">All types</option>
                            <?php foreach ($filterOptions['vehicle_types'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['vehicle_type'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vehicle-fuel">Fuel type</label>
                        <select id="vehicle-fuel" name="fuel_type">
                            <option value="">All fuel types</option>
                            <?php foreach ($filterOptions['fuel_types'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['fuel_type'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vehicle-transmission">Transmission</label>
                        <select id="vehicle-transmission" name="transmission">
                            <option value="">All transmissions</option>
                            <?php foreach ($filterOptions['transmissions'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['transmission'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vehicle-service">Rental service</label>
                        <select id="vehicle-service" name="service_type">
                            <option value="">Any service</option>
                            <?php foreach ($filterOptions['service_types'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['service_type'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vehicle-availability">Listing status</label>
                        <select id="vehicle-availability" name="availability">
                            <?php foreach ($filterOptions['availability'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['availability'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vehicle-min-price">Minimum daily price (Rs.)</label>
                        <input id="vehicle-min-price" name="min_price" type="number" min="0" step="0.01" value="<?= $filters['min_price'] !== null ? htmlspecialchars((string) $filters['min_price'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="vehicle-max-price">Maximum daily price (Rs.)</label>
                        <input id="vehicle-max-price" name="max_price" type="number" min="0" step="0.01" value="<?= $filters['max_price'] !== null ? htmlspecialchars((string) $filters['max_price'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="vehicle-seats">Minimum seats</label>
                        <input id="vehicle-seats" name="min_seats" type="number" min="1" max="100" step="1" value="<?= $filters['min_seats'] !== null ? (int) $filters['min_seats'] : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="vehicle-start-date">Start date</label>
                        <input id="vehicle-start-date" name="start_date" type="date" value="<?= htmlspecialchars($filters['start_date'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label for="vehicle-end-date">End date</label>
                        <input id="vehicle-end-date" name="end_date" type="date" value="<?= htmlspecialchars($filters['end_date'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label for="vehicle-sort">Sort by</label>
                        <select id="vehicle-sort" name="sort">
                            <?php foreach ($viewData['sort_options'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $viewData['sort'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="vehicle-filter-form__actions">
                        <button class="button button--primary" type="submit"><?= customer_icon('search') ?> Apply filters</button>
                    </div>
                </form>
                <?php if ($activeFilterLabels !== []): ?>
                    <div class="vehicle-active-filters" aria-label="Active filters">
                        <strong>Active filters:</strong>
                        <?php foreach ($activeFilterLabels as $activeFilterLabel): ?>
                            <span><?= htmlspecialchars($activeFilterLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($viewData['database_error']): ?>
                <section class="empty-state" aria-labelledby="vehicle-service-error-title">
                    <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('alert') ?></span>
                    <h2 id="vehicle-service-error-title">Vehicle catalogue is temporarily unavailable</h2>
                    <p>We could not load vehicles from the database. Please try again after the database service is available.</p>
                </section>
            <?php else: ?>
                <section aria-labelledby="vehicle-results-title">
                    <div class="vehicle-results-heading">
                        <div>
                            <p class="eyebrow">Approved listings</p>
                            <h2 id="vehicle-results-title"><?= number_format((int) $viewData['total_results']) ?> vehicle<?= (int) $viewData['total_results'] === 1 ? '' : 's' ?> found</h2>
                        </div>
                        <p>Page <?= (int) $viewData['current_page'] ?> of <?= (int) $viewData['total_pages'] ?></p>
                    </div>

                    <?php if ($viewData['vehicles'] === []): ?>
                        <div class="empty-state">
                            <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('search') ?></span>
                            <h3>No matching vehicles</h3>
                            <p>Try widening the price, date, or specification filters.</p>
                            <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('vehicles/index.php'), ENT_QUOTES, 'UTF-8') ?>">Clear filters</a>
                        </div>
                    <?php else: ?>
                        <div class="vehicle-grid">
                            <?php foreach ($viewData['vehicles'] as $vehicle): ?>
                                <?php require dirname(__DIR__) . '/components/vehicle/vehicle-card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ((int) $viewData['total_pages'] > 1): ?>
                        <nav class="catalogue-pagination" aria-label="Vehicle catalogue pages">
                            <?php $currentPage = (int) $viewData['current_page']; $totalPages = (int) $viewData['total_pages']; ?>
                            <?php if ($currentPage > 1): ?>
                                <a href="<?= htmlspecialchars($catalogueUrl($currentPage - 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="Previous page">Previous</a>
                            <?php else: ?>
                                <span aria-disabled="true">Previous</span>
                            <?php endif; ?>
                            <?php for ($pageNumber = max(1, $currentPage - 2); $pageNumber <= min($totalPages, $currentPage + 2); $pageNumber++): ?>
                                <a href="<?= htmlspecialchars($catalogueUrl($pageNumber), ENT_QUOTES, 'UTF-8') ?>" <?= $pageNumber === $currentPage ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                            <?php endfor; ?>
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="<?= htmlspecialchars($catalogueUrl($currentPage + 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="Next page">Next</a>
                            <?php else: ?>
                                <span aria-disabled="true">Next</span>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require dirname(__DIR__) . '/components/layout/footer.php'; ?>
