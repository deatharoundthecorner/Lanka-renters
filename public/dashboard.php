<?php
// dashboard.php - Lanka Renters Admin Dashboard Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Overview";
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
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span class="badge badge-blue">Admin</span>
                <span class="badge badge-pending">11 items need review</span>
            </div>

            <div class="page-header-box">
                <h1 class="page-title">Admin dashboard</h1>
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
                        <h3>Three customer approvals are waiting for review.</h3>
                        <p>Clear identity checks to keep safe customers moving.</p>
                    </div>
                </div>
                <a href="users.php" class="btn btn-primary">
                    <span>Review approvals</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>

            <!-- Top Registration Statistics Cards -->
            <div class="grid-4">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Registered Users</span>
                        <div class="stat-icon-box">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">1,248</div>
                    <div class="stat-comparison">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                        <span>+12% this month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Registered Owners</span>
                        <div class="stat-icon-box green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">186</div>
                    <div class="stat-comparison">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                        <span>+5% this month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Registered Drivers</span>
                        <div class="stat-icon-box amber">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">324</div>
                    <div class="stat-comparison">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                        <span>+8% this month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Registered Vehicles</span>
                        <div class="stat-icon-box purple">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="7" rx="2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M5 11l2-5h10l2 5"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">512</div>
                    <div class="stat-comparison">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                        <span>+15% this month</span>
                    </div>
                </div>
            </div>

            <!-- Financial Statistics Cards -->
            <div class="grid-4">
                <div class="stat-card" style="border-left: 4px solid var(--primary);">
                    <div class="stat-header">
                        <span class="stat-label">Total Commission Earned</span>
                    </div>
                    <div class="stat-value" style="font-size: 22px;">Rs. 1,245,800.00</div>
                    <div class="stat-comparison">Platform Commission</div>
                </div>

                <div class="stat-card" style="border-left: 4px solid var(--success);">
                    <div class="stat-header">
                        <span class="stat-label">Total Bookings</span>
                    </div>
                    <div class="stat-value">2,486</div>
                    <div class="stat-comparison">All Completed Trips</div>
                </div>

                <div class="stat-card" style="border-left: 4px solid var(--info);">
                    <div class="stat-header">
                        <span class="stat-label">Bookings Today</span>
                    </div>
                    <div class="stat-value">38</div>
                    <div class="stat-comparison">Active Reservations</div>
                </div>

                <div class="stat-card" style="border-left: 4px solid var(--warning);">
                    <div class="stat-header">
                        <span class="stat-label">Payments Pending</span>
                    </div>
                    <div class="stat-value" style="font-size: 22px;">Rs. 285,400.00</div>
                    <div class="stat-comparison neutral">Awaiting Verification</div>
                </div>
            </div>

            <!-- Review Queue Cards (Grid Matching Screenshot) -->
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
                        <span class="badge badge-pending">5 waiting</span>
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
                        <span class="badge badge-pending">3 waiting</span>
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
                        <span class="badge badge-blue">2 waiting</span>
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
                        <span class="badge badge-rejected">1 active</span>
                    </div>
                </a>
            </div>

            <!-- Charts Section -->
            <div class="grid-3">
                <div class="card">
                    <div class="card-header-clean">
                        <div>
                            <h3 class="card-title-text">Monthly Revenue (LKR)</h3>
                            <p class="card-subtitle-text">Total rental revenue by month</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChartCanvas"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header-clean">
                        <div>
                            <h3 class="card-title-text">Booking Overview</h3>
                            <p class="card-subtitle-text">Distribution of rental bookings</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="bookingChartCanvas"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header-clean">
                        <div>
                            <h3 class="card-title-text">Registration Overview</h3>
                            <p class="card-subtitle-text">Entities registered on platform</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="registrationChartCanvas"></canvas>
                    </div>
                </div>
            </div>

            <!-- Admin Essentials Card & Activity -->
            <div class="grid-2">
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div style="width: 36px; height: 36px; background: #EAF3FF; color: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="card-title-text">Admin essentials</h3>
                            <p class="card-subtitle-text">The rules that matter most today.</p>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; color: var(--text-secondary);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" color="#16A34A"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Verify users, drivers, and vehicles.</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" color="#16A34A"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Verify payment after owner confirmation.</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" color="#16A34A"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Manage incidents and replacements.</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Recent Activity</h3>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 14px; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                            <div>
                                <strong style="color: var(--text-main);">New customer registration</strong>
                                <p style="color: var(--text-muted);">Kasun Perera submitted NIC for verification.</p>
                            </div>
                            <span style="font-size: 11px; color: var(--text-light); white-space: nowrap;">5 minutes ago</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                            <div>
                                <strong style="color: var(--text-main);">Vehicle owner submitted documents</strong>
                                <p style="color: var(--text-muted);">Sunil Wickramasinghe uploaded vehicle book.</p>
                            </div>
                            <span style="font-size: 11px; color: var(--text-light); white-space: nowrap;">1 hour ago</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                            <div>
                                <strong style="color: var(--text-main);">Payment received</strong>
                                <p style="color: var(--text-muted);">Rs. 45,000.00 confirmed for booking BKG-101.</p>
                            </div>
                            <span style="font-size: 11px; color: var(--text-light); white-space: nowrap;">2 hours ago</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <strong style="color: var(--text-main);">Incident reported</strong>
                                <p style="color: var(--text-muted);">INC-001 filed for Toyota Prius (Colombo).</p>
                            </div>
                            <span style="font-size: 11px; color: var(--text-light); white-space: nowrap;">3 hours ago</span>
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
