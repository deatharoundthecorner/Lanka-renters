<?php
// email_logs.php - Lanka Renters Email Logs & Monitoring Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Email Logs";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Logs - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

            <!-- TOP STATISTICS (2 CARDS) -->
            <div class="grid-2">
                <div class="stat-card" style="border-left: 4px solid var(--success);">
                    <div class="stat-header">
                        <span class="stat-label">Emails Sent Today</span>
                        <div class="stat-icon-box green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">126</div>
                    <div class="stat-comparison">Automated system emails</div>
                </div>

                <div class="stat-card" style="border-left: 4px solid var(--danger);">
                    <div class="stat-header">
                        <span class="stat-label">Failed Attempts</span>
                        <div class="stat-icon-box" style="background: var(--danger-bg); color: var(--danger);">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">7</div>
                    <div class="stat-comparison" style="color: var(--danger);">Requires manual retry</div>
                </div>
            </div>

            <div class="card">
                <div class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Logs</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search recipient, subject or booking ID...">
                    </div>
                    <div class="filter-group">
                        <label for="filterType">Recipient Type</label>
                        <select id="filterType" class="form-control">
                            <option value="">All Recipient Types</option>
                            <option value="Customer">Customer</option>
                            <option value="Driver">Driver</option>
                            <option value="Owner">Owner</option>
                        </select>
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
                                <th>Recipient Type</th>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Booking ID</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge badge-blue">Customer</span></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">John Perera</span>
                                        <span class="cell-secondary-text">CUS-001</span>
                                    </div>
                                </td>
                                <td>Booking Confirmation - BKG-101</td>
                                <td><span class="cell-secondary-text">BKG-101</span></td>
                                <td><span class="badge badge-sent">Sent</span></td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Email Content Preview - EML-101', 'Booking Confirmation Template')">View</button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-blue">Owner</span></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Sunil Wickramasinghe</span>
                                        <span class="cell-secondary-text">OWN-001</span>
                                    </div>
                                </td>
                                <td>New Rental Request Assigned</td>
                                <td><span class="cell-secondary-text">BKG-101</span></td>
                                <td><span class="badge badge-sent">Sent</span></td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Email Content Preview - EML-102', 'Owner Rental Notice Template')">View</button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-blue">Driver</span></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Kamal Silva</span>
                                        <span class="cell-secondary-text">DRV-012</span>
                                    </div>
                                </td>
                                <td>Trip Schedule Update - BKG-101</td>
                                <td><span class="cell-secondary-text">BKG-101</span></td>
                                <td><span class="badge badge-failed">Failed</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="retryEmail('EML-103', 'Kamal Silva (DRV-012)')">Send Again</button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-blue">Customer</span></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Chamara Jayasinghe</span>
                                        <span class="cell-secondary-text">CUS-004</span>
                                    </div>
                                </td>
                                <td>Payment Received Receipt</td>
                                <td><span class="cell-secondary-text">BKG-102</span></td>
                                <td><span class="badge badge-sent">Sent</span></td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Email Content Preview - EML-104', 'Payment Receipt Template')">View</button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-blue">Driver</span></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-primary-text">Nuwan Bandara</span>
                                        <span class="cell-secondary-text">DRV-002</span>
                                    </div>
                                </td>
                                <td>Replacement Driver Request Approved</td>
                                <td><span class="cell-secondary-text">BKG-101</span></td>
                                <td><span class="badge badge-failed">Failed</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="retryEmail('EML-105', 'Nuwan Bandara (DRV-002)')">Send Again</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <span class="pagination-info">Showing 1 to 5 of 133 entries</span>
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
