<?php
$page = basename($_SERVER['PHP_SELF'], '.php');
function active($name, $page) {
    return $name === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlements - LankaRenters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="brand">
                <div class="brand-logo">L</div>
                <div>
                    <div class="brand-title">LankaRenters</div>
                    <div class="brand-subtitle">Admin</div>
                </div>
            </div>
            <nav class="admin-nav">
                <a href="dashboard.php" class="nav-item <?php echo active('dashboard', $page);?>">Dashboard</a>
                <a href="users.php" class="nav-item <?php echo active('users', $page);?>">Users</a>
                <a href="owners.php" class="nav-item <?php echo active('owners', $page);?>">Owners</a>
                <a href="drivers.php" class="nav-item <?php echo active('drivers', $page);?>">Drivers</a>
                <a href="vehicles.php" class="nav-item <?php echo active('vehicles', $page);?>">Vehicles</a>
                <a href="bookings.php" class="nav-item <?php echo active('bookings', $page);?>">Bookings</a>
                <a href="payments.php" class="nav-item <?php echo active('payments', $page);?>">Payments</a>
                <a href="incident.php" class="nav-item <?php echo active('incident', $page);?>">Incidents</a>
                <a href="replacement_requests.php" class="nav-item <?php echo active('replacement_requests', $page);?>">Replacement Requests</a>
                <a href="settlements.php" class="nav-item <?php echo active('settlements', $page);?>">Settlements</a>
                <a href="reports.php" class="nav-item <?php echo active('reports', $page);?>">Reports</a>
                <a href="email_logs.php" class="nav-item <?php echo active('email_logs', $page);?>">Email Logs</a>
            </nav>
            <a href="#" class="logout-link">Log out</a>
        </aside>
        <main class="admin-content">
            <header class="page-header">
                <div>
                    <h1>Settlements</h1>
                    <p>Calculate refunds, deductions and payouts.</p>
                </div>
            </header>

            <section class="settlement-grid">
                <div class="panel-card settlement-summary">
                    <div class="settlement-card">
                        <h3>Settlement calculation · BK-1042</h3>
                        <div class="settlement-row">
                            <span>Rental amount</span>
                            <span class="settlement-value">Rs. 95,000</span>
                        </div>
                        <div class="settlement-row">
                            <span>Refund calculation</span>
                            <span class="settlement-value">Rs. 15,000</span>
                        </div>
                        <div class="settlement-row settlement-deduction">
                            <span>Damage deduction</span>
                            <span class="settlement-value settlement-negative">-Rs. 12,000</span>
                        </div>
                        <div class="settlement-row">
                            <span>Platform fee</span>
                            <span class="settlement-value settlement-negative">-Rs. 4,750</span>
                        </div>
                        <div class="settlement-row settlement-total-row">
                            <strong>Owner earnings</strong>
                            <strong id="ownerEarningsValue">Rs. 63,250</strong>
                        </div>
                    </div>
                    <button id="approveSettlement" class="btn btn-primary settlement-action">Approve Settlement</button>
                </div>
                <div class="panel-card">
                    <div class="settlement-card">
                        <h3>Recent settlements</h3>
                        <div class="recent-settlements" id="recentSettlements">
                            <div class="recent-item">
                                <div>
                                    <strong>BK-1020</strong>
                                    <small>Settled</small>
                                </div>
                                <span class="paid-pill">Rs. 120,000</span>
                            </div>
                            <div class="recent-item">
                                <div>
                                    <strong>BK-1005</strong>
                                    <small>Settled</small>
                                </div>
                                <span class="paid-pill">Rs. 112,000</span>
                            </div>
                            <div class="recent-item">
                                <div>
                                    <strong>BK-0998</strong>
                                    <small>Settled</small>
                                </div>
                                <span class="paid-pill">Rs. 104,000</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>
