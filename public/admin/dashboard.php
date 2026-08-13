<?php
// dashboard.php - Lanka Renters Admin Dashboard Page
require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Overview";

// Fetch live database counts & metrics
$db = getDBConnection();

$stats = [
    'users' => 0,
    'owners' => 0,
    'drivers' => 0,
    'vehicles' => 0,
    'pending_customers' => 0,
    'pending_vehicles' => 0,
    'pending_payments' => 0,
    'active_incidents' => 0,
    'total_bookings' => 0,
    'bookings_today' => 0,
    'commission_earned' => 0.00,
    'pending_payments_amount' => 0.00
];

if ($db) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
        $stats['users'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM vehicle_owners");
        $stats['owners'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM drivers");
        $stats['drivers'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM vehicles");
        $stats['vehicles'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM customers WHERE verification_status = 'pending'");
        $stats['pending_customers'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM vehicles WHERE verification_status = 'pending'");
        $stats['pending_vehicles'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'");
        $stats['pending_payments'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM incidents WHERE status IN ('reported', 'investigating')");
        $stats['active_incidents'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM bookings");
        $stats['total_bookings'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()");
        $stats['bookings_today'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT SUM(total_price * 0.10) FROM bookings WHERE status = 'completed'");
        $val = $stmt->fetchColumn();
        $stats['commission_earned'] = $val ? (float)$val : 0.00;
    } catch (Throwable $e) {}

    try {
        $stmt = $db->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'pending'");
        $val = $stmt->fetchColumn();
        $stats['pending_payments_amount'] = $val ? (float)$val : 0.00;
    } catch (Throwable $e) {}
}

$totalReviewItems = $stats['pending_customers'] + $stats['pending_vehicles'] + $stats['pending_payments'] + $stats['active_incidents'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <!-- Header Badges & Titles -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="badge badge-blue">Admin</span>
                    <span class="badge badge-pending"><?= $totalReviewItems ?> item<?= $totalReviewItems === 1 ? '' : 's' ?> need review</span>
                </div>

                <!-- System Status Indicator Card -->
                <div style="display: flex; align-items: center; gap: 8px; background: #FFFFFF; border: 1px solid var(--border); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                    <span class="status-indicator-dot green"></span>
                    <span style="color: var(--text-main);">System Status:</span>
                    <span style="color: var(--success);">All systems operational</span>
                </div>
            </div>

            <div class="page-header-box">
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-subtitle">Keep approvals, payments, incidents, and settlements moving clearly.</p>
            </div>

            <!-- "Do this next" Callout Card -->
            <div class="do-this-next-card">
                <div class="do-next-left">
                    <div class="do-next-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <div class="do-next-content">
                        <span class="do-next-tag">Do this next</span>
                        <h3><?= $stats['pending_customers'] ?> customer approval<?= $stats['pending_customers'] === 1 ? '' : 's' ?> waiting for review.</h3>
                        <p>Clear identity checks to keep safe customers moving.</p>
                    </div>
                </div>
                <a href="users.php" class="btn btn-primary">
                    <span>Review approvals</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>

            <!-- Quick Actions Bar -->
            <div class="card" style="padding: 14px 20px; margin-bottom: 24px; background: #FFFFFF;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div style="font-size: 13px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        <span>Quick Actions</span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <a href="announcements.php?action=create" class="btn btn-primary btn-sm">+ Add Announcement</a>
                        <a href="users.php" class="btn btn-secondary btn-sm">Review Users</a>
                        <a href="vehicles.php" class="btn btn-secondary btn-sm">Review Vehicles</a>
                        <a href="payments.php" class="btn btn-secondary btn-sm">Review Payments</a>
                        <a href="incidents.php" class="btn btn-secondary btn-sm">View Incidents</a>
                    </div>
                </div>
            </div>

            <!-- CLICKABLE Top Registration Statistics Cards -->
            <div class="grid-4">
                <div class="stat-card clickable-card" onclick="window.location.href='users.php?view=registered'" title="Click to view Registered Customers">
                    <div class="stat-header">
                        <span class="stat-label">Registered Users</span>
                        <div class="stat-icon-box">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['users']) ?></div>
                    <div class="stat-comparison">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                        <span>View Registered →</span>
                    </div>
                </div>

                <div class="stat-card clickable-card" onclick="window.location.href='vehicle_owners.php?view=registered'" title="Click to view Registered Vehicle Owners">
                    <div class="stat-header">
                        <span class="stat-label">Registered Owners</span>
                        <div class="stat-icon-box green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['owners']) ?></div>
                    <div class="stat-comparison">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                        <span>View Registered →</span>
                    </div>
                </div>

                <div class="stat-card clickable-card" onclick="window.location.href='drivers.php?view=registered'" title="Click to view Registered Drivers">
                    <div class="stat-header">
                        <span class="stat-label">Registered Drivers</span>
                        <div class="stat-icon-box amber">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['drivers']) ?></div>
                    <div class="stat-comparison">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                        <span>View Registered →</span>
                    </div>
                </div>

                <div class="stat-card clickable-card" onclick="window.location.href='vehicles.php?view=registered'" title="Click to view Registered Vehicles">
                    <div class="stat-header">
                        <span class="stat-label">Registered Vehicles</span>
                        <div class="stat-icon-box purple">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="7" rx="2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M5 11l2-5h10l2 5"/></svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['vehicles']) ?></div>
                    <div class="stat-comparison">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                        <span>View Registered →</span>
                    </div>
                </div>
            </div>

            <!-- Financial Statistics Cards -->
            <div class="grid-4">
                <div class="stat-card" style="border-left: 4px solid var(--primary);">
                    <div class="stat-header">
                        <span class="stat-label">Total Commission Earned</span>
                    </div>
                    <div class="stat-value" style="font-size: 22px;">Rs. <?= number_format($stats['commission_earned'], 2) ?></div>
                    <div class="stat-comparison">Platform Commission</div>
                </div>

                <div class="stat-card" style="border-left: 4px solid var(--success);">
                    <div class="stat-header">
                        <span class="stat-label">Total Bookings</span>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_bookings']) ?></div>
                    <div class="stat-comparison">All Completed Trips</div>
                </div>

                <div class="stat-card" style="border-left: 4px solid var(--info);">
                    <div class="stat-header">
                        <span class="stat-label">Bookings Today</span>
                    </div>
                    <div class="stat-value"><?= number_format($stats['bookings_today']) ?></div>
                    <div class="stat-comparison">Active Reservations</div>
                </div>

                <div class="stat-card" style="border-left: 4px solid var(--warning);">
                    <div class="stat-header">
                        <span class="stat-label">Payments Pending</span>
                    </div>
                    <div class="stat-value" style="font-size: 22px;">Rs. <?= number_format($stats['pending_payments_amount'], 2) ?></div>
                    <div class="stat-comparison neutral">Awaiting Verification</div>
                </div>
            </div>

            <!-- Review Queue Cards -->
            <div class="grid-2">
                <a href="users.php" class="queue-card">
                    <div class="queue-left">
                        <div class="queue-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div class="queue-info">
                            <h4>Customer approvals</h4>
                            <p>Review queue</p>
                        </div>
                    </div>
                    <div class="queue-right">
                        <span class="badge badge-pending"><?= $stats['pending_customers'] ?> waiting</span>
                    </div>
                </a>

                <a href="vehicles.php" class="queue-card">
                    <div class="queue-left">
                        <div class="queue-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="7" rx="2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
                        </div>
                        <div class="queue-info">
                            <h4>Vehicle approvals</h4>
                            <p>Document review</p>
                        </div>
                    </div>
                    <div class="queue-right">
                        <span class="badge badge-pending"><?= $stats['pending_vehicles'] ?> waiting</span>
                    </div>
                </a>

                <a href="payments.php" class="queue-card">
                    <div class="queue-left">
                        <div class="queue-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        </div>
                        <div class="queue-info">
                            <h4>Payment reviews</h4>
                            <p>Confirm evidence</p>
                        </div>
                    </div>
                    <div class="queue-right">
                        <span class="badge badge-blue"><?= $stats['pending_payments'] ?> waiting</span>
                    </div>
                </a>

                <a href="incidents.php" class="queue-card">
                    <div class="queue-left">
                        <div class="queue-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </div>
                        <div class="queue-info">
                            <h4>Incidents</h4>
                            <p>Decision needed</p>
                        </div>
                    </div>
                    <div class="queue-right">
                        <span class="badge badge-rejected"><?= $stats['active_incidents'] ?> active</span>
                    </div>
                </a>
            </div>

            <!-- System Services Operational Status Grid Card -->
            <div class="card">
                <div class="card-header-clean">
                    <h3 class="card-title-text">System Services Operational Status</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; font-size: 13px;">
                    <div style="background: var(--bg-main); padding: 12px 16px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                        <span>Booking System</span>
                        <span class="badge badge-approved">Operational</span>
                    </div>
                    <div style="background: var(--bg-main); padding: 12px 16px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                        <span>Payment System</span>
                        <span class="badge badge-approved">Operational</span>
                    </div>
                    <div style="background: var(--bg-main); padding: 12px 16px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                        <span>Email Notifications</span>
                        <span class="badge badge-approved">Operational</span>
                    </div>
                </div>
            </div>

            <!-- Admin Essentials Card & Guidance -->
            <div class="grid-2">
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div style="width: 36px; height: 36px; background: #EFF6FF; color: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="card-title-text">Admin essentials</h3>
                            <p class="card-subtitle-text">The rules that matter most today.</p>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; color: var(--text-secondary);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" color="#059669"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Verify users, drivers, and vehicles.</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" color="#059669"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Verify payment after owner confirmation.</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" color="#059669"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Manage incidents and replacements.</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Platform Overview Summary</h3>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 14px; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                            <div>
                                <strong style="color: var(--text-main);">Customer registrations</strong>
                                <p style="color: var(--text-muted);"><?= number_format($stats['users']) ?> total registered customers</p>
                            </div>
                            <span class="badge badge-approved"><?= number_format($stats['pending_customers']) ?> pending</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                            <div>
                                <strong style="color: var(--text-main);">Fleet vehicles</strong>
                                <p style="color: var(--text-muted);"><?= number_format($stats['vehicles']) ?> total registered vehicles</p>
                            </div>
                            <span class="badge badge-approved"><?= number_format($stats['pending_vehicles']) ?> pending</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                            <div>
                                <strong style="color: var(--text-main);">Payment queue</strong>
                                <p style="color: var(--text-muted);"><?= number_format($stats['pending_payments']) ?> payments awaiting review</p>
                            </div>
                            <span class="badge badge-blue">Rs. <?= number_format($stats['pending_payments_amount'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Info Banner -->
            <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: var(--radius-md); padding: 14px 20px; display: flex; align-items: center; gap: 12px; font-size: 13px; color: #1E40AF;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Keep every payment trail and decision note clear for disputes.</span>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
