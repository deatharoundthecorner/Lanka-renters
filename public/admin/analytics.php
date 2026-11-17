<?php
require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/controllers/AdminDashboardController.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Platform Analytics";
$selectedYear = filter_var($_GET['year'] ?? date('Y'), FILTER_VALIDATE_INT) ?: (int)date('Y');

$controller = new AdminDashboardController();
$analytics = $controller->liveAnalytics($selectedYear);

$stats = $analytics['stats'] ?? [
    'users' => 0,
    'owners' => 0,
    'drivers' => 0,
    'vehicles' => 0,
    'bookings' => 0,
    'pending_payments' => 0,
];
$years = $analytics['available_years'] ?? [$selectedYear];
$monthlyPayments = $analytics['monthly_payments'] ?? [];
$bookingStatuses = $analytics['booking_status'] ?? [];

$totalAnnualRevenue = 0.0;
$totalAnnualTransactions = 0;
foreach ($monthlyPayments as $m) {
    $totalAnnualRevenue += (float)($m['total'] ?? 0);
    $totalAnnualTransactions += (int)($m['count'] ?? 0);
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Analytics - Lanka Renters Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-canonical.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
                <div class="page-header-box" style="margin-bottom: 0;">
                    <h1 class="page-title">Platform Analytics</h1>
                    <p class="page-subtitle">Real-time aggregated database telemetry across users, fleet, bookings, and revenue.</p>
                </div>

                <form method="GET" action="analytics.php" style="display: flex; align-items: center; gap: 10px; background: #ffffff; padding: 6px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm);">
                    <label for="yearSelect" style="font-size: 13px; font-weight: 600; color: var(--text-muted);">Reporting Year:</label>
                    <select name="year" id="yearSelect" onchange="this.form.submit()" style="padding: 4px 8px; font-size: 13px; border: 1px solid var(--border); border-radius: 4px; background: #ffffff; color: var(--text-main); font-weight: 600; cursor: pointer;">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= (int)$y ?>" <?= $y === $selectedYear ? 'selected' : '' ?>><?= (int)$y ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Overview Statistics Cards -->
            <div class="grid-3" style="margin-bottom: 24px;">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Registered Customers</span>
                        <div class="stat-icon-box">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format((int)$stats['users']) ?></div>
                    <div class="stat-comparison" style="color: var(--text-muted);">Active platform renters</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Fleet Owners</span>
                        <div class="stat-icon-box">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format((int)$stats['owners']) ?></div>
                    <div class="stat-comparison" style="color: var(--text-muted);">Registered vehicle partners</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Professional Drivers</span>
                        <div class="stat-icon-box">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format((int)$stats['drivers']) ?></div>
                    <div class="stat-comparison" style="color: var(--text-muted);">Verified active chauffeurs</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Listed Vehicles</span>
                        <div class="stat-icon-box">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="7" rx="2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M5 11l2-5h10l2 5"/></svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format((int)$stats['vehicles']) ?></div>
                    <div class="stat-comparison" style="color: var(--text-muted);">Total vehicles on platform</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Lifetime Bookings</span>
                        <div class="stat-icon-box">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format((int)$stats['bookings']) ?></div>
                    <div class="stat-comparison" style="color: var(--text-muted);">All-time rental requests</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Pending Payments</span>
                        <div class="stat-icon-box" style="background: <?= (int)$stats['pending_payments'] > 0 ? '#FEF3C7' : '#EFF6FF' ?>; color: <?= (int)$stats['pending_payments'] > 0 ? '#B45309' : '#2563EB' ?>;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                    </div>
                    <div class="stat-value" style="color: <?= (int)$stats['pending_payments'] > 0 ? '#B45309' : 'inherit' ?>;"><?= number_format((int)$stats['pending_payments']) ?></div>
                    <div class="stat-comparison">
                        <?php if ((int)$stats['pending_payments'] > 0): ?>
                            <a href="payments.php" style="color: #B45309; text-decoration: underline; font-weight: 600;">Requires verification &rarr;</a>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">All payments verified</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout: Monthly Revenue & Booking Distribution -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
                <!-- Monthly Payments Table -->
                <div class="card" style="padding: 20px; background: #ffffff;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div>
                            <h2 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin: 0 0 4px;">Completed Payments (<?= (int)$selectedYear ?>)</h2>
                            <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Monthly revenue ledger for verified transactions</p>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block;">Annual Total</span>
                            <span style="font-size: 15px; font-weight: 800; color: var(--primary);">LKR <?= number_format($totalAnnualRevenue, 2) ?></span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border); text-align: left; font-size: 12px; color: var(--text-muted);">
                                    <th style="padding: 10px 12px;">Month</th>
                                    <th style="padding: 10px 12px;">Transactions</th>
                                    <th style="padding: 10px 12px; text-align: right;">Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthlyPayments as $item): ?>
                                    <tr style="border-bottom: 1px solid var(--border-light); font-size: 13px;">
                                        <td style="padding: 10px 12px; font-weight: 600; color: var(--text-main);"><?= $escape($item['label']) ?></td>
                                        <td style="padding: 10px 12px; color: var(--text-secondary);"><?= (int)$item['count'] ?> transaction<?= (int)$item['count'] === 1 ? '' : 's' ?></td>
                                        <td style="padding: 10px 12px; text-align: right; font-weight: 600; color: <?= (float)$item['total'] > 0 ? 'var(--text-main)' : 'var(--text-muted)' ?>;">
                                            LKR <?= number_format((float)$item['total'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Booking Status Distribution -->
                <div class="card" style="padding: 20px; background: #ffffff;">
                    <div style="margin-bottom: 16px;">
                        <h2 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin: 0 0 4px;">Bookings by Status</h2>
                        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Distribution for <?= (int)$selectedYear ?></p>
                    </div>

                    <?php if (empty($bookingStatuses)): ?>
                        <p style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 24px 0;">No booking records found for <?= (int)$selectedYear ?>.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($bookingStatuses as $bs): ?>
                                <?php
                                $statusLabel = ucfirst(str_replace('_', ' ', (string)($bs['status'] ?? 'unknown')));
                                $count = (int)($bs['count'] ?? 0);
                                ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--bg-main); border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
                                    <span style="font-size: 13px; font-weight: 600; color: var(--text-main);"><?= $escape($statusLabel) ?></span>
                                    <span class="badge badge-blue" style="font-weight: 700; font-size: 12px;"><?= $count ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
                        <a href="reports.php" class="btn btn-secondary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            <span>Open Detailed Reports</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/partials/footer.php'; ?>
    </div>
</div>

</body>
</html>
