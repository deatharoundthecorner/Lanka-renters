<?php
// bookings.php - Lanka Renters Booking Management Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Booking Management";
$districts = getSriLankanDistricts();
$vehicleTypes = getVehicleTypes();
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

            <!-- Top Statistic Cards -->
            <div class="grid-4">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Current Bookings</span>
                        <div class="stat-icon-box">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">2,486</div>
                    <div class="stat-comparison neutral">All time total bookings</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Active Bookings</span>
                        <div class="stat-icon-box green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">142</div>
                    <div class="stat-comparison">Currently on trip</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Pending Approval</span>
                        <div class="stat-icon-box amber">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">18</div>
                    <div class="stat-comparison neutral">Requires review</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Finished Bookings</span>
                        <div class="stat-icon-box purple">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">2,326</div>
                    <div class="stat-comparison">Successfully closed</div>
                </div>
            </div>

            <div class="card">
                <div class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Booking</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search customer, vehicle or IDs...">
                    </div>
                    <div class="filter-group">
                        <label for="filterDistrict">District</label>
                        <select id="filterDistrict" class="form-control">
                            <option value="">All Districts</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filterType">Vehicle Type</label>
                        <select id="filterType" class="form-control">
                            <option value="">All Vehicle Types</option>
                            <?php foreach ($vehicleTypes as $vt): ?>
                                <option value="<?php echo htmlspecialchars($vt); ?>"><?php echo htmlspecialchars($vt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filterDate">Date Range</label>
                        <input type="date" id="filterDate" class="form-control">
                    </div>
                    <div style="display: flex; gap: 8px; align-self: flex-end;">
                        <button class="btn btn-primary" onclick="initTableFilters()">Search</button>
                        <button class="btn btn-secondary" id="filterResetBtn">Reset</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Payment</th>
                                <th>Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">BKG-101</span></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">John Perera</span>
                                        <span class="cell-secondary-text">Customer ID: CUS-001</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Toyota Prius</span>
                                        <span class="cell-secondary-text">Vehicle ID: VEH-023</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Kamal Silva</span>
                                        <span class="cell-secondary-text">Driver ID: DRV-012</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Rs. 45,000</span>
                                        <span class="cell-secondary-text">Payment ID: PAY-104</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('John Perera (BKG-101)', 'BKG-101')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('John Perera (BKG-101)', 'BKG-101')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">BKG-102</span></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Chamara Jayasinghe</span>
                                        <span class="cell-secondary-text">Customer ID: CUS-004</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Honda Vezel</span>
                                        <span class="cell-secondary-text">Vehicle ID: VEH-002</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Roshan Ranasinghe</span>
                                        <span class="cell-secondary-text">Driver ID: DRV-003</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Rs. 75,000</span>
                                        <span class="cell-secondary-text">Payment ID: PAY-105</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Chamara Jayasinghe (BKG-102)', 'BKG-102')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Chamara Jayasinghe (BKG-102)', 'BKG-102')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">BKG-103</span></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Sanduni Perera</span>
                                        <span class="cell-secondary-text">Customer ID: CUS-005</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Toyota KDH Super GL</span>
                                        <span class="cell-secondary-text">Vehicle ID: VEH-003</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Nuwan Bandara</span>
                                        <span class="cell-secondary-text">Driver ID: DRV-002</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Rs. 120,000</span>
                                        <span class="cell-secondary-text">Payment ID: PAY-106</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Sanduni Perera (BKG-103)', 'BKG-103')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Sanduni Perera (BKG-103)', 'BKG-103')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <span class="pagination-info">Showing 1 to 3 of 42 entries</span>
                    <div class="pagination-controls">
                        <button class="page-btn" disabled>Previous</button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn">2</button>
                        <button class="page-btn">Next</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
