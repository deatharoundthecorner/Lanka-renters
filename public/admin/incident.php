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
    <title>Incident management - LankaRenters</title>
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
                    <h1>Incident management</h1>
                    <p>Review accident and breakdown reports.</p>
                </div>
            </header>

            <section class="panel-card filters-panel">
                <div class="filters-title">
                    <span>Filters</span>
                </div>
                <div class="filter-grid">
                    <label class="filter-group">
                        <span>District</span>
                        <select id="incidentDistrict">
                            <option value="">All</option>
                        </select>
                    </label>
                    <label class="filter-group">
                        <span>Vehicle type</span>
                        <select id="incidentVehicleType">
                            <option value="">All</option>
                        </select>
                    </label>
                    <label class="filter-group">
                        <span>Status</span>
                        <select id="incidentStatus">
                            <option value="">All</option>
                            <option value="Pending">Pending</option>
                            <option value="Disputed">Disputed</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </label>
                </div>
            </section>

            <section id="incidentList" class="incident-list">
                <!-- Incident cards render here -->
            </section>

            <section class="panel-card note-banner">
                <strong>Damage policy:</strong> Damage costs are not automatically deducted. Admin reviews evidence, inspection reports, and incident details before deduction.
            </section>
        </main>
    </div>

    <div class="modal-backdrop hidden" id="evidenceModal">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <div>
                    <h2 id="modalTitle">Incident evidence</h2>
                    <p id="modalSubTitle" class="modal-subtitle">Review the incident report and evidence details.</p>
                </div>
                <button id="modalClose" class="modal-close" aria-label="Close modal">×</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-actions">
                <button class="btn btn-primary" id="modalAction">Mark as Reviewed</button>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
