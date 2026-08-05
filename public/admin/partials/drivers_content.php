<?php
// Drivers page content (partial)
?>
<header class="page-header">
    <div>
        <h1>Drivers Management</h1>
        <p>Review driver registrations, verify documents, and manage approved drivers.</p>
    </div>
    <div class="admin-userbubble">
        <button class="btn btn-primary btn-sm">Add Driver</button>
    </div>
</header>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-title">Total Drivers</div>
        <div class="stat-number">125</div>
        <div class="stat-caption">Registered drivers on the platform</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Pending Verification</div>
        <div class="stat-number">12</div>
        <div class="stat-caption">Drivers awaiting approval</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Approved Drivers</div>
        <div class="stat-number">105</div>
        <div class="stat-caption">Verified and active drivers</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Suspended Drivers</div>
        <div class="stat-number">8</div>
        <div class="stat-caption">Drivers paused from assignments</div>
    </div>
</div>

<section class="filters-card">
    <div class="filters-card-header">
        <div class="filter-icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 5h18" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round"/>
                <path d="M6 12h12" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round"/>
                <path d="M10 19h4" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div>
            <p class="section-label">Filters</p>
            <p class="section-description">Search drivers by name, documents, district, or type.</p>
        </div>
    </div>

    <form class="filter-grid" method="GET" action="">
        <label class="filter-group">
            <span>Search</span>
            <input type="search" name="q" placeholder="Search driver name, NIC, license number..." />
        </label>
        <label class="filter-group">
            <span>Status</span>
            <select name="status">
                <option>All</option>
                <option>Pending Verification</option>
                <option>Approved</option>
                <option>Rejected</option>
                <option>Suspended</option>
            </select>
        </label>
        <label class="filter-group">
            <span>District</span>
            <select name="district">
                <option>All Districts</option>
                <option>Colombo</option>
                <option>Gampaha</option>
                <option>Kandy</option>
                <option>Galle</option>
                <option>Matara</option>
                <option>Kurunegala</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Driver Type</span>
            <select name="driver_type">
                <option>All</option>
                <option>Individual Driver</option>
                <option>Vehicle Owner Driver</option>
            </select>
        </label>
        <div class="filter-actions" style="grid-column: span 2;">
            <button class="btn btn-primary btn-sm" type="submit">Search</button>
            <button class="btn btn-secondary btn-sm" type="reset">Reset</button>
        </div>
    </form>
</section>

<section class="panel-card wide-card" style="margin-top: 24px;">
    <div class="table-header" style="grid-template-columns: 1.5fr 1fr 1fr 1fr 1.25fr 1.2fr;">
        <span>Driver</span>
        <span>Contact</span>
        <span>License No</span>
        <span>District</span>
        <span>Registration Date</span>
        <span>Action</span>
    </div>

    <div class="table-row" style="grid-template-columns: 1.5fr 1fr 1fr 1fr 1.25fr 1.2fr;">
        <span>Ruwan Bandara</span>
        <span>0771234567</span>
        <span>B1234567</span>
        <span>Colombo</span>
        <span>31 Jul 2026</span>
        <span class="row-actions">
            <button class="btn btn-primary btn-sm">Approve</button>
            <button class="btn btn-outline btn-sm">Reject</button>
            <button class="btn btn-outline btn-sm">View Documents</button>
        </span>
    </div>

    <div class="table-row" style="grid-template-columns: 1.5fr 1fr 1fr 1fr 1.25fr 1.2fr;">
        <span>Dilani Weerasinghe</span>
        <span>0719876543</span>
        <span>C4567890</span>
        <span>Kandy</span>
        <span>29 Jul 2026</span>
        <span class="row-actions">
            <button class="btn btn-primary btn-sm">Approve</button>
            <button class="btn btn-outline btn-sm">Reject</button>
            <button class="btn btn-outline btn-sm">View Documents</button>
        </span>
    </div>

    <div class="table-row" style="grid-template-columns: 1.5fr 1fr 1fr 1fr 1.25fr 1.2fr;">
        <span>Tharindu Alwis</span>
        <span>0755555555</span>
        <span>D9876543</span>
        <span>Galle</span>
        <span>20 Jul 2026</span>
        <span class="row-actions">
            <button class="btn btn-outline btn-sm">View Profile</button>
            <button class="btn btn-secondary btn-sm">Suspend</button>
        </span>
    </div>

    <div class="table-row" style="grid-template-columns: 1.5fr 1fr 1fr 1fr 1.25fr 1.2fr;">
        <span>Kasun Perera</span>
        <span>0764321987</span>
        <span>E2345678</span>
        <span>Gampaha</span>
        <span>18 Jul 2026</span>
        <span class="row-actions">
            <button class="btn btn-primary btn-sm">Reactivate</button>
        </span>
    </div>
</section>
