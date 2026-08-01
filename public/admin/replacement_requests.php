<?php
$page = basename($_SERVER['PHP_SELF'], '.php');
function active($name, $page) { return $name === $page ? 'active' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Replacement Requests - LankaRenters</title>
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
                <a href="settings.php" class="nav-item <?php echo active('settings', $page);?>">Settings</a>
            </nav>
            <a href="#" class="logout-link">Log out</a>
        </aside>
        <main class="admin-content">
            <header class="page-header">
                <div>
                    <h1>Driver Replacement Requests</h1>
                    <p>Review customer requests to replace assigned drivers.</p>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-title">Pending Requests</div>
                    <div class="stat-number">12</div>
                    <div class="stat-caption">Requests waiting for review</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Approved Today</div>
                    <div class="stat-number">8</div>
                    <div class="stat-caption">Approved by admins today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Emergency Requests</div>
                    <div class="stat-number">3</div>
                    <div class="stat-caption">High priority cases</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Completed This Week</div>
                    <div class="stat-number">41</div>
                    <div class="stat-caption">Resolved replacement requests</div>
                </div>
            </div>

            <div class="panel-card">
                <div class="filters-panel">
                    <div class="filters-title">Filters</div>
                    <form class="filter-grid" method="GET" action="">
                        <div class="filter-group">
                            <span>Search</span>
                            <input type="search" name="q" placeholder="Search booking ID, customer, or driver..." style="padding:12px 14px;border-radius:16px;border:1px solid #cbd5e1;background:#fff;">
                        </div>
                        <div class="filter-group">
                            <span>Status</span>
                            <select name="status">
                                <option>All</option>
                                <option>Pending</option>
                                <option>Approved</option>
                                <option>Rejected</option>
                                <option>Completed</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <span>Priority</span>
                            <select name="priority">
                                <option>All</option>
                                <option>Emergency</option>
                                <option>Normal</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <span>Request Date</span>
                            <input type="date" name="request_date" style="padding:12px 14px;border-radius:16px;border:1px solid #cbd5e1;background:#fff;">
                        </div>
                        <div class="filter-group" style="display:flex;align-items:end;gap:8px;">
                            <button class="btn-primary" type="submit">Search</button>
                            <button class="btn-secondary" type="reset">Reset</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="panel-card wide-card" style="margin-top:18px;">
                <div class="table-header" style="grid-template-columns: 1fr 1fr 1fr 1fr 2fr 0.9fr 0.9fr 1fr;">
                    <span>Request ID</span>
                    <span>Booking</span>
                    <span>Customer</span>
                    <span>Current Driver</span>
                    <span>Reason</span>
                    <span>Priority</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>

                <div class="table-row" style="grid-template-columns: 1fr 1fr 1fr 1fr 2fr 0.9fr 0.9fr 1fr;">
                    <span>RR-1001</span>
                    <span>BK-2045</span>
                    <span>Kasun Silva</span>
                    <span>Nimal Perera</span>
                    <span>Driver arrived late</span>
                    <span><span class="priority-pill priority-emergency">Emergency</span></span>
                    <span><span class="status-pill status-warning">Pending</span></span>
                    <span class="row-actions">
                        <button class="btn btn-primary">Approve</button>
                        <button class="btn btn-outline">Reject</button>
                    </span>
                </div>

                <div class="table-row" style="grid-template-columns: 1fr 1fr 1fr 1fr 2fr 0.9fr 0.9fr 1fr;">
                    <span>RR-1002</span>
                    <span>BK-1988</span>
                    <span>Amal Fernando</span>
                    <span>Ruwan Bandara</span>
                    <span>Driver unavailable</span>
                    <span><span class="priority-pill priority-normal">Normal</span></span>
                    <span><span class="status-pill status-warning">Pending</span></span>
                    <span class="row-actions">
                        <button class="btn btn-primary">Approve</button>
                        <button class="btn btn-outline">Reject</button>
                    </span>
                </div>

                <div class="table-row" style="grid-template-columns: 1fr 1fr 1fr 1fr 2fr 0.9fr 0.9fr 1fr;">
                    <span>RR-1003</span>
                    <span>BK-1877</span>
                    <span>Tharindu Perera</span>
                    <span>Dilan Silva</span>
                    <span>Safety concern</span>
                    <span><span class="priority-pill priority-emergency">Emergency</span></span>
                    <span><span class="status-pill status-success">Approved</span></span>
                    <span>
                        <button class="btn btn-secondary view-request"
                            data-requestid="RR-1003"
                            data-booking="BK-1877"
                            data-date="2026-07-28"
                            data-reason="Safety concern"
                            data-customer="Tharindu Perera"
                            data-phone="0771234567"
                            data-email="tharindu@example.com"
                            data-driver="Dilan Silva"
                            data-rating="4.6"
                            data-trips="312"
                            data-license="B1234567"
                            data-experience="6 years"
                            data-vehicle="Toyota Prius"
                            data-pickup="Colombo Fort"
                            data-destination="Negombo"
                            data-rentaldate="2026-07-20"
                            data-returndate="2026-07-25"
                        >View</button>
                    </span>
                </div>

                <div class="table-row" style="grid-template-columns: 1fr 1fr 1fr 1fr 2fr 0.9fr 0.9fr 1fr;">
                    <span>RR-1004</span>
                    <span>BK-1766</span>
                    <span>Sahan Jayasuriya</span>
                    <span>Lakshan Perera</span>
                    <span>Vehicle breakdown</span>
                    <span><span class="priority-pill priority-normal">Normal</span></span>
                    <span><span class="status-pill status-completed">Completed</span></span>
                    <span>
                        <button class="btn btn-secondary view-request"
                            data-requestid="RR-1004"
                            data-booking="BK-1766"
                            data-date="2026-07-18"
                            data-reason="Vehicle breakdown"
                            data-customer="Sahan Jayasuriya"
                            data-phone="0717654321"
                            data-email="sahan@example.com"
                            data-driver="Lakshan Perera"
                            data-rating="4.2"
                            data-trips="198"
                            data-license="C9876543"
                            data-experience="4 years"
                            data-vehicle="Suzuki Swift"
                            data-pickup="Kandy"
                            data-destination="Nuwara Eliya"
                            data-rentaldate="2026-07-10"
                            data-returndate="2026-07-15"
                        >View Details</button>
                    </span>
                </div>

                <!-- Empty state example (commented) -->
                <!--
                <div class="empty-bookings-card">
                    <div class="empty-state">
                        <div class="empty-state-icon">🚗</div>
                        <div>
                            <h2>No driver replacement requests found.</h2>
                            <p>There are currently no replacement requests. Try refreshing the page.</p>
                            <div style="margin-top:12px;"><button class="btn-primary">Refresh</button></div>
                        </div>
                    </div>
                </div>
                -->
            </div>

        </main>
    </div>

    <!-- Request details modal -->
    <div id="requestModal" class="modal-backdrop hidden">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <h3>Request Information</h3>
                    <div class="modal-subtitle">Details for replacement request <span class="m-request-id">—</span></div>
                </div>
                <button id="closeRequestModal" class="modal-close">✕</button>
            </div>
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <strong>Request ID</strong>
                        <div class="m-request-id">—</div>
                        <strong style="margin-top:8px;display:block">Booking ID</strong>
                        <div class="m-booking-id">—</div>
                        <strong style="margin-top:8px;display:block">Requested Date</strong>
                        <div class="m-request-date">—</div>
                    </div>
                    <div>
                        <strong>Priority</strong>
                        <div class="m-priority">—</div>
                        <strong style="margin-top:8px;display:block">Current Status</strong>
                        <div class="m-status">—</div>
                    </div>
                </div>

                <div>
                    <strong>Replacement Reason</strong>
                    <div class="m-reason">—</div>
                </div>

                <div>
                    <strong>Customer Comment</strong>
                    <div class="m-customer-comment">—</div>
                </div>

                <hr>
                <h4>Customer Information</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <strong>Name</strong>
                        <div class="m-customer-name">—</div>
                    </div>
                    <div>
                        <strong>Phone</strong>
                        <div class="m-customer-phone">—</div>
                    </div>
                    <div style="grid-column:1 / -1;">
                        <strong>Email</strong>
                        <div class="m-customer-email">—</div>
                    </div>
                </div>

                <hr>
                <h4>Current Driver</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <strong>Name</strong>
                        <div class="m-driver-name">—</div>
                    </div>
                    <div>
                        <strong>Rating</strong>
                        <div class="m-driver-rating">—</div>
                    </div>
                    <div>
                        <strong>Completed Trips</strong>
                        <div class="m-driver-trips">—</div>
                    </div>
                    <div>
                        <strong>License Number</strong>
                        <div class="m-driver-license">—</div>
                    </div>
                    <div style="grid-column:1 / -1;">
                        <strong>Experience</strong>
                        <div class="m-driver-exp">—</div>
                    </div>
                </div>

                <hr>
                <h4>Booking Information</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <strong>Vehicle</strong>
                        <div class="m-vehicle">—</div>
                    </div>
                    <div>
                        <strong>Pickup</strong>
                        <div class="m-pickup">—</div>
                    </div>
                    <div>
                        <strong>Destination</strong>
                        <div class="m-destination">—</div>
                    </div>
                    <div>
                        <strong>Rental Date</strong>
                        <div class="m-rental-date">—</div>
                    </div>
                    <div>
                        <strong>Return Date</strong>
                        <div class="m-return-date">—</div>
                    </div>
                </div>

                <hr>
                <h4>Uploaded Evidence</h4>
                <div class="list-card">
                    <div class="receipt-box">No evidence uploaded.</div>
                </div>

                <hr>
                <h4>Admin Decision</h4>
                <div>
                    <textarea placeholder="Admin remarks" style="width:100%;min-height:100px;padding:12px;border-radius:12px;border:1px solid #e5e7eb;"></textarea>
                </div>
            </div>
            <div class="modal-actions">
                <button id="modalReject" class="btn btn-secondary">Reject Request</button>
                <button id="modalApprove" class="btn btn-primary">Approve Replacement</button>
                <button id="closeRequestModal" class="btn btn-outline">Close</button>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
