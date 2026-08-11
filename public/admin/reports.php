<?php
// reports.php - Lanka Renters Reports & Analytics Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Reports & Analytics";
$districts = getSriLankanDistricts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Reports & Analytics</h1>
                <p class="page-subtitle">Generate custom analytical summaries and export system performance metrics.</p>
            </div>

            <!-- REPORT CARDS GRID (6 CARDS) -->
            <div class="report-grid">
                <div class="report-card">
                    <div class="report-card-top">
                        <div class="report-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div class="report-info">
                            <h3>Monthly Revenue Report</h3>
                            <p>View revenue and commission performance by month.</p>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="downloadCSVReport('Monthly Revenue Report')">Download Report</button>
                </div>

                <div class="report-card">
                    <div class="report-card-top">
                        <div class="report-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <div class="report-info">
                            <h3>Booking Summary</h3>
                            <p>View booking activity and completion statistics.</p>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="downloadCSVReport('Booking Summary Report')">Download Report</button>
                </div>

                <div class="report-card">
                    <div class="report-card-top">
                        <div class="report-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <div class="report-info">
                            <h3>User Registration Report</h3>
                            <p>Customer, driver and owner registration metrics.</p>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="downloadCSVReport('User Registration Report')">Download Report</button>
                </div>

                <div class="report-card">
                    <div class="report-card-top">
                        <div class="report-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="7" rx="2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
                        </div>
                        <div class="report-info">
                            <h3>Vehicle Performance Report</h3>
                            <p>Fleet utilization, trip counts and owner payouts.</p>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="downloadCSVReport('Vehicle Performance Report')">Download Report</button>
                </div>

                <div class="report-card">
                    <div class="report-card-top">
                        <div class="report-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        </div>
                        <div class="report-info">
                            <h3>Payment Report</h3>
                            <p>Bank transfer verification trails and pending claims.</p>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="downloadCSVReport('Payment Report')">Download Report</button>
                </div>

                <div class="report-card">
                    <div class="report-card-top">
                        <div class="report-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </div>
                        <div class="report-info">
                            <h3>Incident Report</h3>
                            <p>Accident, damage and emergency driver swap logs.</p>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="downloadCSVReport('Incident Report')">Download Report</button>
                </div>
            </div>

            <!-- REPORT GENERATOR & PREVIEW -->
            <div class="card">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Custom Report Generator</h3>
                </div>

                <div class="filter-card">
                    <div class="filter-group">
                        <label for="reportTypeSelect">Report Type</label>
                        <select id="reportTypeSelect" class="form-control">
                            <option value="Monthly Revenue">Monthly Revenue</option>
                            <option value="Booking Summary">Booking Summary</option>
                            <option value="User Registration">User Registration</option>
                            <option value="Vehicle Performance">Vehicle Performance</option>
                            <option value="Payment Report">Payment Report</option>
                            <option value="Incident Log">Incident Log</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Date From</label>
                        <input type="date" class="form-control" value="2026-08-01">
                    </div>
                    <div class="filter-group">
                        <label>Date To</label>
                        <input type="date" class="form-control" value="2026-08-08">
                    </div>
                    <div class="filter-group">
                        <label>District</label>
                        <select class="form-control">
                            <option value="">All Districts</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: 8px; align-self: flex-end;">
                        <button class="btn btn-primary" onclick="showToast('Report generated successfully!', 'success')">Generate Report</button>
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Sample Report Preview</h4>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Period / Date</th>
                                    <th>Total Bookings</th>
                                    <th>Gross Revenue (LKR)</th>
                                    <th>Platform Commission (LKR)</th>
                                    <th>Net Owner Payout (LKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>August 2026</td>
                                    <td>248</td>
                                    <td>Rs. 12,458,000.00</td>
                                    <td>Rs. 1,245,800.00</td>
                                    <td>Rs. 11,212,200.00</td>
                                </tr>
                                <tr>
                                    <td>July 2026</td>
                                    <td>210</td>
                                    <td>Rs. 10,500,000.00</td>
                                    <td>Rs. 1,050,000.00</td>
                                    <td>Rs. 9,450,000.00</td>
                                </tr>
                                <tr>
                                    <td>June 2026</td>
                                    <td>195</td>
                                    <td>Rs. 9,750,000.00</td>
                                    <td>Rs. 975,000.00</td>
                                    <td>Rs. 8,775,000.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
