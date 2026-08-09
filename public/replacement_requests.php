<?php
// replacement_requests.php - Lanka Renters Emergency Driver Replacement Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Replacement Driver Requests";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replacement Requests - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Replacement Driver Requests</h1>
                <p class="page-subtitle">Review emergency requests to assign alternative drivers for active trips.</p>
            </div>

            <div class="card">
                <div class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Request</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search request ID, booking ID or driver...">
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
                                <th>Request ID</th>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Current Driver</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">REP-001</span></td>
                                <td><span class="cell-secondary-text">BKG-101</span></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">John Perera</span>
                                        <span class="cell-secondary-text">CUS-001</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Kamal Silva</span>
                                        <span class="cell-secondary-text">DRV-012</span>
                                    </div>
                                </td>
                                <td>Medical Emergency of Assigned Driver</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Replacement Driver Request REP-001', 'REP-001')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Replacement Driver Request REP-001', 'REP-001')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">REP-002</span></td>
                                <td><span class="cell-secondary-text">BKG-102</span></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Chamara Jayasinghe</span>
                                        <span class="cell-secondary-text">CUS-004</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Roshan Ranasinghe</span>
                                        <span class="cell-secondary-text">DRV-003</span>
                                    </div>
                                </td>
                                <td>Vehicle Owner Requested Driver Swap</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Replacement Driver Request REP-002', 'REP-002')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Replacement Driver Request REP-002', 'REP-002')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <span class="pagination-info">Showing 1 to 2 of 2 entries</span>
                    <div class="pagination-controls">
                        <button class="page-btn" disabled>Previous</button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn" disabled>Next</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
