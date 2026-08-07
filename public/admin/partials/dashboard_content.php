<?php
// Dashboard page content (partial)
?>
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
        <div class="stat-number">1,780</div>
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
