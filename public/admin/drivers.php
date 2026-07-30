<?php
$page = basename($_SERVER['PHP_SELF'], '.php');
function active($name, $page) { return $name === $page ? 'active' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Drivers - LankaRenters</title>
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
                    <h1>Drivers</h1>
                    <p>Review driver submissions and confirm identity before dispatch.</p>
                </div>
                <div class="admin-userbubble">
                    <button class="btn-outline">Open approval queue</button>
                    <div class="user-initial">D</div>
                </div>
            </header>
            <div class="dashboard-cards">
                <section class="panel-card">
                    <h2>Driver approvals</h2>
                    <div class="list-card">
                        <div class="incident-card">
                            <div class="incident-main">
                                <div class="incident-header">
                                    <div>
                                        <div class="incident-title">Ruwan Bandara · Pending license review</div>
                                        <div class="incident-meta">Review evidence, apply the rule, then keep a clear record.</div>
                                    </div>
                                </div>
                                <div class="incident-detail">
                                    <strong>Submitted documents</strong>
                                    <p class="muted">Driver license, ID proof, and vehicle registration received.</p>
                                </div>
                            </div>
                            <div class="incident-actions">
                                <button class="btn-primary">Approve</button>
                                <button class="btn-secondary">Reject</button>
                            </div>
                        </div>
                        <div class="incident-card">
                            <div class="incident-main">
                                <div class="incident-header">
                                    <div>
                                        <div class="incident-title">Dilani Weerasinghe · Pending background check</div>
                                        <div class="incident-meta">Confirm customer reviews and vehicle familiarity.</div>
                                    </div>
                                </div>
                                <div class="incident-detail">
                                    <strong>Submitted documents</strong>
                                    <p class="muted">Background report and driving history attached.</p>
                                </div>
                            </div>
                            <div class="incident-actions">
                                <button class="btn-primary">Approve</button>
                                <button class="btn-secondary">Reject</button>
                            </div>
                        </div>
                    </div>
                </section>
                <aside class="panel-card">
                    <h2>Admin essentials</h2>
                    <div style="display:grid; gap:10px;">
                        <div style="color:#64748b;">The rules that matter most today.</div>
                        <ul style="padding-left:18px; margin:0; color:#0f172a;">
                            <li>Verify drivers before assigning rentals.</li>
                            <li>Check ID proof and license validity.</li>
                            <li>Approve only after owning or admin confirmation.</li>
                        </ul>
                    </div>
                    <div style="margin-top:18px; background:#eef2ff; border-radius:12px; padding:12px; color:#2563eb;">
                        Driver verification is required before covering vehicles under insurance.
                    </div>
                </aside>
            </div>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>
