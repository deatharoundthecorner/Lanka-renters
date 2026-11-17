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
                            <option value="pending_approval" <?= $statusFilter === 'pending_approval' ? 'selected' : '' ?>>Pending Approval</option>
                            <option value="pending_payment" <?= $statusFilter === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
                            <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="ongoing" <?= $statusFilter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="disputed" <?= $statusFilter === 'disputed' ? 'selected' : '' ?>>Disputed</option>
                            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 8px; align-self: flex-end;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="bookings.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>

                <div class="lr-table-wrap table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Total Price</th>
                                <th>Booking Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="lr-empty-state">
                                            <div class="lr-empty-icon">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                            </div>
                                            <h3>No booking records found</h3>
                                            <p>No customer reservations match your search or filter criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $bkg): ?>
                                    <?php
                                    $bkgBadge = match($bkg['status']) {
                                        'confirmed', 'completed' => 'badge-approved',
                                        'ongoing' => 'badge-active',
                                        'disputed' => 'badge-rejected',
                                        'pending_approval', 'pending_payment' => 'badge-pending',
                                        'cancelled' => 'badge-rejected',
                                        default => 'badge-pending'
                                    };
                                    $modalData = [
                                        'id' => (int)$bkg['id'],
                                        'customer_name' => $bkg['customer_name'] ?? 'N/A',
                                        'customer_email' => $bkg['customer_email'] ?? 'N/A',
                                        'customer_phone' => $bkg['customer_phone'] ?? 'N/A',
                                        'vehicle' => trim(($bkg['make'] ?? '') . ' ' . ($bkg['model'] ?? '')),
                                        'license_plate' => $bkg['license_plate'] ?? 'N/A',
                                        'vehicle_type' => ucfirst($bkg['vehicle_type'] ?? 'N/A'),
                                        'booking_type' => ucfirst(str_replace('_', ' ', $bkg['booking_type'] ?? 'self_drive')),
                                        'start_date' => !empty($bkg['start_date']) ? date('Y-m-d H:i', strtotime($bkg['start_date'])) : 'N/A',
                                        'end_date' => !empty($bkg['end_date']) ? date('Y-m-d H:i', strtotime($bkg['end_date'])) : 'N/A',
                                        'total_price' => 'Rs. ' . number_format((float)($bkg['total_price'] ?? 0), 2),
                                        'status' => ucfirst(str_replace('_', ' ', $bkg['status'] ?? 'pending')),
                                        'pickup_status' => ucfirst(str_replace('_', ' ', $bkg['pickup_status'] ?? 'pending')),
                                        'driver_name' => !empty($bkg['driver_name']) ? $bkg['driver_name'] : 'Self Drive',
                                        'driver_phone' => !empty($bkg['driver_phone']) ? $bkg['driver_phone'] : 'N/A',
                                        'created_at' => !empty($bkg['created_at']) ? date('Y-m-d H:i', strtotime($bkg['created_at'])) : 'N/A',
                                    ];
                                    ?>
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
                                            <span class="badge <?= $bkgBadge ?>">
                                                <?= $escape(str_replace('_', ' ', ucfirst($bkg['status']))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="viewBookingDetails(<?= htmlspecialchars(json_encode($modalData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                                                View
                                            </button>
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

<!-- Booking Details Modal -->
<div class="modal-overlay" id="bookingDetailsModal">
    <div class="modal-box" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="bkgModalTitle">Booking Details</h3>
            <button class="modal-close-btn" type="button" onclick="closeModal('bookingDetailsModal')">&times;</button>
        </div>
        <div class="modal-body" style="display: flex; flex-direction: column; gap: 16px;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; background: #F8FAFC; padding: 16px; border-radius: 8px; border: 1px solid var(--border);">
                <div>
                    <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Booking ID</span>
                    <p id="bkgModalId" style="font-weight: 600; margin: 2px 0 0; color: var(--text-main);">-</p>
                </div>
                <div>
                    <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Booking Status</span>
                    <p id="bkgModalStatus" style="font-weight: 600; margin: 2px 0 0;">-</p>
                </div>
                <div>
                    <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Booking Type</span>
                    <p id="bkgModalType" style="margin: 2px 0 0; color: var(--text-secondary);">-</p>
                </div>
                <div>
                    <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Created Date</span>
                    <p id="bkgModalCreated" style="margin: 2px 0 0; color: var(--text-secondary);">-</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                <div style="border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                    <h4 style="font-size: 13px; font-weight: 700; margin: 0 0 10px; color: var(--text-main);">Customer Information</h4>
                    <p style="font-size: 13px; margin: 0 0 4px;"><strong>Name:</strong> <span id="bkgModalCustomer">-</span></p>
                    <p style="font-size: 13px; margin: 0 0 4px;"><strong>Email:</strong> <span id="bkgModalEmail" class="cell-secondary-text">-</span></p>
                    <p style="font-size: 13px; margin: 0;"><strong>Phone:</strong> <span id="bkgModalPhone" class="cell-secondary-text">-</span></p>
                </div>
                <div style="border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                    <h4 style="font-size: 13px; font-weight: 700; margin: 0 0 10px; color: var(--text-main);">Vehicle & Driver</h4>
                    <p style="font-size: 13px; margin: 0 0 4px;"><strong>Vehicle:</strong> <span id="bkgModalVehicle">-</span></p>
                    <p style="font-size: 13px; margin: 0 0 4px;"><strong>Plate:</strong> <span id="bkgModalPlate" class="badge badge-blue" style="font-size: 11px;">-</span></p>
                    <p style="font-size: 13px; margin: 0;"><strong>Driver:</strong> <span id="bkgModalDriver">-</span></p>
                </div>
            </div>

            <div style="border: 1px solid var(--border); border-radius: 8px; padding: 14px; background: #ffffff;">
                <h4 style="font-size: 13px; font-weight: 700; margin: 0 0 10px; color: var(--text-main);">Rental Schedule & Billing</h4>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; font-size: 13px;">
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Start Date:</span>
                        <p id="bkgModalStart" style="font-weight: 500; margin: 2px 0 0;">-</p>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">End Date:</span>
                        <p id="bkgModalEnd" style="font-weight: 500; margin: 2px 0 0;">-</p>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Pickup Status:</span>
                        <p id="bkgModalPickup" style="font-weight: 500; margin: 2px 0 0;">-</p>
                    </div>
                </div>
                <div style="border-top: 1px solid var(--border-light); margin-top: 12px; padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600; font-size: 14px;">Total Price:</span>
                    <span id="bkgModalPrice" style="font-size: 16px; font-weight: 700; color: var(--primary);">-</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" type="button" onclick="closeModal('bookingDetailsModal')">Close</button>
        </div>
    </div>
</div>

<script>
function viewBookingDetails(data) {
    if (!data) return;
    document.getElementById('bkgModalTitle').innerText = 'Booking #' + data.id + ' Details';
    document.getElementById('bkgModalId').innerText = 'BKG-' + data.id;
    document.getElementById('bkgModalStatus').innerText = data.status;
    document.getElementById('bkgModalType').innerText = data.booking_type;
    document.getElementById('bkgModalCreated').innerText = data.created_at;
    document.getElementById('bkgModalCustomer').innerText = data.customer_name;
    document.getElementById('bkgModalEmail').innerText = data.customer_email;
    document.getElementById('bkgModalPhone').innerText = data.customer_phone;
    document.getElementById('bkgModalVehicle').innerText = data.vehicle;
    document.getElementById('bkgModalPlate').innerText = data.license_plate;
    document.getElementById('bkgModalDriver').innerText = data.driver_name + (data.driver_phone !== 'N/A' ? ' (' + data.driver_phone + ')' : '');
    document.getElementById('bkgModalStart').innerText = data.start_date;
    document.getElementById('bkgModalEnd').innerText = data.end_date;
    document.getElementById('bkgModalPickup').innerText = data.pickup_status;
    document.getElementById('bkgModalPrice').innerText = data.total_price;
    openModal('bookingDetailsModal');
}
</script>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
