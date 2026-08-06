<?php
// Users page content (partial)
?>
<header class="page-header">
    <div>
        <h1>Customers</h1>
        <p>Manage registered customers and review account activity.</p>
    </div>
</header>

<section class="card filters-card users-filters">
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
            <p class="section-description">Filter customer accounts by status, district, or account type.</p>
        </div>
    </div>

    <div class="filter-grid">
        <label class="filter-group">
            <span>Search</span>
            <input type="search" placeholder="Search customer name or email...">
        </label>
        <label class="filter-group">
            <span>Status</span>
            <select>
                <option>All</option>
                <option>Pending</option>
                <option>Active</option>
                <option>Suspended</option>
            </select>
        </label>
        <label class="filter-group">
            <span>District</span>
            <select>
                <option>All Districts</option>
                <option>Colombo</option>
                <option>Gampaha</option>
                <option>Kandy</option>
                <option>Galle</option>
                <option>Matara</option>
                <option>Kurunegala</option>
            </select>
        </label>
        <div class="filter-actions" style="justify-content:flex-end;">
            <button class="btn btn-primary" type="button">Search</button>
            <button class="btn btn-secondary" type="button">Reset</button>
        </div>
    </div>
</section>

<div class="panel-card wide-card" style="margin-top:24px;">
    <div class="table-header users-table">
        <span>Customer</span>
        <span>Contact</span>
        <span>District</span>
        <span>Status</span>
        <span>Actions</span>
    </div>
    <div class="table-row users-table">
        <span class="customer-info">
            <strong>Ruwan Bandara</strong>
            <small>Member since 12 Jul 2026</small>
        </span>
        <span>ruwan@email.com<br>0771234567</span>
        <span>Colombo</span>
        <span class="status-pill status-warning">Pending</span>
        <span class="row-actions">
            <button class="btn btn-outline">View</button>
            <button class="btn btn-primary">Approve</button>
            <button class="btn btn-secondary">Reject</button>
        </span>
    </div>
    <div class="table-row users-table">
        <span class="customer-info">
            <strong>Dilani Weerasinghe</strong>
            <small>Member since 09 Jul 2026</small>
        </span>
        <span>dilani@email.com<br>0719876543</span>
        <span>Gampaha</span>
        <span class="status-pill status-success">Active</span>
        <span class="row-actions">
            <button class="btn btn-outline">View</button>
            <button class="btn btn-outline">Suspend</button>
        </span>
    </div>
    <div class="table-row users-table">
        <span class="customer-info">
            <strong>Tharindu Alwis</strong>
            <small>Member since 03 Jul 2026</small>
        </span>
        <span>tharindu@email.com<br>0754444444</span>
        <span>Kandy</span>
        <span class="status-pill status-suspended">Suspended</span>
        <span class="row-actions">
            <button class="btn btn-outline">View</button>
            <button class="btn btn-primary">Reactivate</button>
        </span>
    </div>
</div>
