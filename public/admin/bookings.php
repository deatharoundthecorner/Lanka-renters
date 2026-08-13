<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminBookingMonitoring.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Booking Management";
$districts = getSriLankanDistricts();
$vehicleTypes = getVehicleTypes();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

try {
    $monitoring = new AdminBookingMonitoring();
    $bookings = $monitoring->getAllBookings(['search' => $search, 'status' => $statusFilter]);
} catch (Throwable $e) {
    error_log("Bookings Page Error: " . $e->getMessage());
    $bookings = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Booking Management</h1>
                <p class="page-subtitle">Monitor customer reservations, assigned drivers, payment status and rental approvals.</p>
            </div>

            <div class="card">
                <form method="get" action="bookings.php" class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Booking</label>
                        <input type="text" name="search" id="filterSearch" class="form-control" placeholder="Search customer, vehicle or plate..." value="<?= $escape($search) ?>">
                    </div>
                    <div class="filter-group">
                        <label for="filterStatus">Booking Status</label>
                        <select name="status" id="filterStatus" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending_payment" <?= $statusFilter === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
                            <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="ongoing" <?= $statusFilter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 8px; align-self: flex-end;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="bookings.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Total Price</th>
                                <th>Booking Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 24px;">No booking records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $bkg): ?>
                                    <tr>
                                        <td><span class="cell-secondary-text">BKG-<?= (int)$bkg['id'] ?></span></td>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-primary-text"><?= $escape($bkg['customer_name']) ?></span>
                                                <span class="cell-secondary-text"><?= $escape($bkg['customer_phone']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-primary-text"><?= $escape($bkg['make'] . ' ' . $bkg['model']) ?></span>
                                                <span class="cell-secondary-text"><?= $escape($bkg['license_plate']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="cell-primary-text"><?= $escape($bkg['driver_name'] ?: 'Self Drive') ?></span>
                                        </td>
                                        <td>
                                            <span class="cell-primary-text">Rs. <?= number_format((float)$bkg['total_price'], 2) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $bkg['status'] === 'confirmed' ? 'badge-approved' : ($bkg['status'] === 'completed' ? 'badge-blue' : 'badge-pending') ?>">
                                                <?= $escape(str_replace('_', ' ', ucfirst($bkg['status']))) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
