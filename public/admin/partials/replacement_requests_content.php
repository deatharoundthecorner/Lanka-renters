<?php
// Replacement Requests page content (partial)
?>
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

</div>
