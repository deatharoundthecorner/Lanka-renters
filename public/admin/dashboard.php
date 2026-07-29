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
    <title>LankaRenters Admin Dashboard</title>
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
            <header class="admin-header">
                <div>
                    <h1>Admin dashboard</h1>
                    <p>Platform overview and safety controls.</p>
                </div>
                <div class="admin-userbubble">
                    <span class="icon-bell"></span>
                    <span class="user-initial">S</span>
                    <div>
                        <div>System Admin</div>
                        <span>Admin</span>
                    </div>
                </div>
            </header>
            <section class="dashboard-grid">
                <article class="stat-card">
                    <div class="stat-title">Total users</div>
                    <div class="stat-number">1,284</div>
                    <div class="stat-caption">+42 this week</div>
                </article>
                <article class="stat-card">
                    <div class="stat-title">Pending verifications</div>
                    <div class="stat-number">18</div>
                    <div class="stat-caption">Needs review</div>
                </article>
                <article class="stat-card">
                    <div class="stat-title">Open incidents</div>
                    <div class="stat-number">3</div>
                    <div class="stat-caption">Under review</div>
                </article>
                <article class="stat-card">
                    <div class="stat-title">Platform revenue</div>
                    <div class="stat-number">Rs. 486,000</div>
                    <div class="stat-caption">This month</div>
                </article>
            </section>
            <section class="dashboard-cards">
                <div class="panel-card">
                    <h2>Recent bookings</h2>
                    <div class="list-card">
                        <div class="list-item">
                            <div>
                                <strong>BK-1042 · Toyota Aqua</strong>
                                <div class="item-detail">Amaya Jayasuriya</div>
                            </div>
                            <span class="status-pill status-active">Active</span>
                        </div>
                        <div class="list-item">
                            <div>
                                <strong>BK-1051 · Suzuki Wagon R</strong>
                                <div class="item-detail">Ruwan Bandara</div>
                            </div>
                            <span class="status-pill status-warning">Pending Owner Approval</span>
                        </div>
                    </div>
                </div>
                <div class="panel-card">
                    <h2>Verification queue</h2>
                    <div class="list-card">
                        <div class="list-item">
                            <div>
                                <strong>Ruwan Bandara</strong>
                                <div class="item-detail">Customer</div>
                            </div>
                            <div class="row-actions">
                                <button class="btn btn-primary">Approve</button>
                                <button class="btn btn-secondary">Reject</button>
                            </div>
                        </div>
                        <div class="list-item">
                            <div>
                                <strong>City Rentals</strong>
                                <div class="item-detail">Owner</div>
                            </div>
                            <div class="row-actions">
                                <button class="btn btn-primary">Approve</button>
                                <button class="btn btn-secondary">Reject</button>
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
