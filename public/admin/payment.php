<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payments - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    /* Page-scoped overrides to force pill buttons and visuals when other styles interfere */
    .admin-content button,
    .admin-content .btn-primary,
    .admin-content .btn-outline,
    .admin-content .btn-secondary {
        border-radius: 28px !important;
        padding: 10px 18px !important;
        line-height: 1.1 !important;
    }
    .admin-content .btn-primary {
        background: linear-gradient(180deg,#1e66f5 0%, #1746d8 100%) !important;
        color: #ffffff !important;
        border: none !important;
        box-shadow: 0 14px 36px rgba(30,64,175,0.18), 0 4px 8px rgba(15,23,42,0.06) inset !important;
    }
    button#verifyPayment { padding: 16px 40px !important; border-radius: 28px !important; }
    </style>
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="brand">
            <div class="brand-logo">LR</div>
            <div>
                <div class="brand-title">Lanka Renters</div>
                <div class="brand-subtitle">WORKSPACE</div>
            </div>
        </div>

        <nav class="admin-nav">
            <a class="nav-item" href="dashboard.php">Overview</a>
            <a class="nav-item" href="users.php">Customers</a>
            <a class="nav-item" href="owners.php">Owners</a>
            <a class="nav-item" href="drivers.php">Drivers</a>
            <a class="nav-item" href="vehicles.php">Vehicles</a>
            <a class="nav-item" href="bookings.php">Bookings</a>
            <a class="nav-item active" href="payment.php">Payments</a>
            <a class="nav-item" href="incident.php">Incidents</a>
            <a class="nav-item" href="settlements.php">Settlements</a>
        </nav>

        <a class="logout-link" href="#">Sign out</a>
    </aside>

    <main class="admin-content">
        <header class="admin-header">
            <div>
                <h1>Payments</h1>
                <p>Review proof and verify the payment before activation.</p>
            </div>
            <div class="admin-userbubble">
                <button class="btn-outline">Open approval queue</button>
                <div class="user-initial">A</div>
            </div>
        </header>

        <div class="dashboard-cards">
            <section class="panel-card">
                <h2>Payment reviews</h2>
                <div class="list-card">
                    <div class="incident-card">
                        <div class="incident-main">
                            <div class="incident-header">
                                <div>
                                    <div class="incident-title">BK–2026–1081 · Receipt from Nimal Perera</div>
                                    <div class="incident-meta">Review evidence, apply the rule, then keep a clear record.</div>
                                </div>
                            </div>

                            <div class="incident-detail">
                                <div class="receipt-box">
                                    <strong>Receipt</strong>
                                    <p class="muted">Bank transfer - ABC Bank. Reference: TRX123456.</p>

                                    <div style="margin-top:18px;">
                                        <button id="verifyPayment" class="btn-primary large">Verify payment</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="panel-card">
                <h2>Admin essentials</h2>
                <div style="display:grid; gap:10px;">
                    <div style="color:#64748b;">The rules that matter most today.</div>
                    <ul style="padding-left:18px; margin:0; color:#0f172a;">
                        <li>Verify users, drivers, and vehicles.</li>
                        <li>Verify payment after owner confirmation.</li>
                        <li>Manage incidents and replacements.</li>
                    </ul>
                </div>

                <div style="margin-top:18px; background:#eef2ff; border-radius:12px; padding:12px; color:#2563eb;">
                    Admin payment verification is required before a booking becomes active.
                </div>
            </aside>
        </div>

    </main>
</div>

<!-- Modal -->
<div id="verifyModal" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <div>
                <strong>Verify payment</strong>
                <div class="modal-subtitle">Confirm that the uploaded receipt matches the booking.</div>
            </div>
            <button class="modal-close" id="closeVerifyModal">×</button>
        </div>
        <div class="modal-body">
            <p>Mark this payment as verified. This will activate the booking once owner confirms.</p>
        </div>
        <div class="modal-actions">
            <button id="cancelVerify" class="btn-outline">Cancel</button>
            <button id="confirmVerify" class="btn-primary">Confirm verification</button>
        </div>
    </div>
</div>

<script src="assets/js/script.js"></script>
</body>
</html>
