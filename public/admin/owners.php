<?php
$page = basename($_SERVER['PHP_SELF'], '.php');
function active($name, $page) { return $name === $page ? 'active' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Owners - LankaRenters</title>
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
                <a href="incidents.php" class="nav-item <?php echo active('incidents', $page);?>">Incidents</a>
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
                    <h1>Owners</h1>
                    <p>Approve or reject verification requests.</p>
                </div>
            </header>
            <div class="panel-card wide-card">
                <div class="table-header">
                    <span>Name</span>
                    <span>Detail</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>
                <div class="table-row">
                    <span>Ruwan Bandara</span>
                    <span>ruwan@email.com</span>
                    <span class="status-pill status-warning">Pending</span>
                    <span class="row-actions">
                        <button class="btn btn-primary">Approve</button>
                        <button class="btn btn-secondary">Reject</button>
                    </span>
                </div>
                <div class="table-row">
                    <span>Dilani Weerasinghe</span>
                    <span>dilani@email.com</span>
                    <span class="status-pill status-warning">Pending</span>
                    <span class="row-actions">
                        <button class="btn btn-primary">Approve</button>
                        <button class="btn btn-secondary">Reject</button>
                    </span>
                </div>
                <div class="table-row">
                    <span>Tharindu Alwis</span>
                    <span>tharindu@email.com</span>
                    <span class="status-pill status-success">Approved</span>
                    <span>—</span>
                </div>
            </div>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>
