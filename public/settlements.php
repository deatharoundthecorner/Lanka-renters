<?php
// settlements.php - Lanka Renters Owner Settlements Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Settlements";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlements - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Settlements</h1>
                <p class="page-subtitle">Calculate owner settlements after deducting Lanka Renters commissions.</p>
            </div>

            <!-- PROMINENT SETTLEMENT CALCULATION CARD -->
            <div class="settlement-calc-box">
                <h3 class="card-title-text" style="margin-bottom: 16px;">Settlement Calculator</h3>
                <div class="calc-grid">
                    <div class="filter-group">
                        <label for="calcGrossAmount">Booking Amount (LKR)</label>
                        <input type="number" id="calcGrossAmount" class="form-control" value="50000" placeholder="e.g. 50000">
                    </div>
                    <div class="filter-group">
                        <label for="calcCommissionRate">Commission Rate (%)</label>
                        <input type="number" id="calcCommissionRate" class="form-control" value="10" placeholder="e.g. 10">
                    </div>
                    <div class="calc-result-box">
                        <div class="calc-result-label">Commission Amount</div>
                        <div class="calc-result-value" id="calcCommissionAmount">Rs. 5,000.00</div>
                    </div>
                    <div class="calc-result-box" style="background: #ECFDF3; border-color: #A7F3D0;">
                        <div class="calc-result-label" style="color: var(--success);">Owner Settlement</div>
                        <div class="calc-result-value" id="calcOwnerAmount" style="color: var(--success);">Rs. 45,000.00</div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div class="filter-group" style="max-width: 220px;">
                        <label for="calcDate">Settlement Date</label>
                        <input type="date" id="calcDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button class="btn btn-primary" id="calcCalculateBtn" style="align-self: flex-end;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <span>Calculate Settlement</span>
                    </button>
                </div>
            </div>

            <!-- RECENT SETTLEMENTS TABLE -->
            <div class="card">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Recent Settlements</h3>
                </div>

                <div class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Settlements</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search by owner, settlement ID or booking...">
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
                                <th>Settlement ID</th>
                                <th>Owner</th>
                                <th>Booking ID</th>
                                <th>Gross Amount</th>
                                <th>Commission</th>
                                <th>Net Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">SET-001</span></td>
                                <td><div class="cell-primary-text">Sunil Wickramasinghe</div></td>
                                <td><span class="cell-secondary-text">BKG-101</span></td>
                                <td>Rs. 50,000.00</td>
                                <td>Rs. 5,000.00</td>
                                <td><strong style="color: var(--success);">Rs. 45,000.00</strong></td>
                                <td>08 Aug 2026</td>
                                <td><span class="badge badge-pending">Pending</span></td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Settlement SET-001 Statement', 'Owner Settlement Calculation Voucher')">View Details</button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">SET-002</span></td>
                                <td><div class="cell-primary-text">Rohan Jayawardena</div></td>
                                <td><span class="cell-secondary-text">BKG-103</span></td>
                                <td>Rs. 120,000.00</td>
                                <td>Rs. 12,000.00</td>
                                <td><strong style="color: var(--success);">Rs. 108,000.00</strong></td>
                                <td>06 Aug 2026</td>
                                <td><span class="badge badge-completed">Completed</span></td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Settlement SET-002 Statement', 'Owner Settlement Calculation Voucher')">View Details</button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">SET-003</span></td>
                                <td><div class="cell-primary-text">Mahesh Samarawickrama</div></td>
                                <td><span class="cell-secondary-text">BKG-104</span></td>
                                <td>Rs. 35,000.00</td>
                                <td>Rs. 3,500.00</td>
                                <td><strong style="color: var(--success);">Rs. 31,500.00</strong></td>
                                <td>03 Aug 2026</td>
                                <td><span class="badge badge-completed">Completed</span></td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Settlement SET-003 Statement', 'Owner Settlement Calculation Voucher')">View Details</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <span class="pagination-info">Showing 1 to 3 of 15 entries</span>
                    <div class="pagination-controls">
                        <button class="page-btn" disabled>Previous</button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn">Next</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
