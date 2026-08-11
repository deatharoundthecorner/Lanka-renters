<?php
// payments.php - Lanka Renters Payment Management Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Payment Management";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Payment Management</h1>
                <p class="page-subtitle">Verify customer bank transfer slips and approve rental payments.</p>
            </div>

            <div class="card">
                <div class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Payment</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search payment ID or booking ID...">
                    </div>
                    <div class="filter-group">
                        <label for="filterType">Payment Type</label>
                        <select id="filterType" class="form-control">
                            <option value="">All Payment Types</option>
                            <option value="Full Payment">Full Payment</option>
                            <option value="Rental Payment">Rental Payment</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Min Amount (LKR)</label>
                        <input type="number" class="form-control" placeholder="e.g. 10000">
                    </div>
                    <div class="filter-group">
                        <label>Max Amount (LKR)</label>
                        <input type="number" class="form-control" placeholder="e.g. 200000">
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
                                <th>Payment ID</th>
                                <th>Booking ID</th>
                                <th>Amount</th>
                                <th>Payment Type</th>
                                <th>Submitted Documents</th>
                                <th>Date</th>
                                <th>Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">PAY-104</span></td>
                                <td><span class="cell-secondary-text">BKG-101</span></td>
                                <td><div class="cell-primary-text">Rs. 45,000.00</div></td>
                                <td><span class="badge badge-blue">Full Payment</span></td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Bank Transfer Receipt - PAY-104', 'Bank Transfer Slip')">View Payment Documents</button>
                                </td>
                                <td>08 Aug 2026</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Payment PAY-104', 'PAY-104')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Payment PAY-104', 'PAY-104')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">PAY-105</span></td>
                                <td><span class="cell-secondary-text">BKG-102</span></td>
                                <td><div class="cell-primary-text">Rs. 75,000.00</div></td>
                                <td><span class="badge badge-active">Rental Payment</span></td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Bank Transfer Receipt - PAY-105', 'Bank Transfer Slip')">View Payment Documents</button>
                                </td>
                                <td>07 Aug 2026</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Payment PAY-105', 'PAY-105')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Payment PAY-105', 'PAY-105')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">PAY-106</span></td>
                                <td><span class="cell-secondary-text">BKG-103</span></td>
                                <td><div class="cell-primary-text">Rs. 120,000.00</div></td>
                                <td><span class="badge badge-blue">Full Payment</span></td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Bank Transfer Receipt - PAY-106', 'Bank Transfer Slip')">View Payment Documents</button>
                                </td>
                                <td>05 Aug 2026</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Payment PAY-106', 'PAY-106')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Payment PAY-106', 'PAY-106')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">PAY-107</span></td>
                                <td><span class="cell-secondary-text">BKG-104</span></td>
                                <td><div class="cell-primary-text">Rs. 35,000.00</div></td>
                                <td><span class="badge badge-active">Rental Payment</span></td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Bank Transfer Receipt - PAY-107', 'Bank Transfer Slip')">View Payment Documents</button>
                                </td>
                                <td>02 Aug 2026</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Payment PAY-107', 'PAY-107')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Payment PAY-107', 'PAY-107')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <span class="pagination-info">Showing 1 to 4 of 36 entries</span>
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
