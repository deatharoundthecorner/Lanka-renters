<?php
// Users page content (partial)
?>
<header class="page-header">
    <div>
        <h1>Users</h1>
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

    <section class="filters-card users-filters">
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
    <span>Customer ID</span>
    <span>Customer Name</span>
    <span>Contact</span>
    <span>Submitted Documents</span>
    <span>Actions</span>
</div>
    <div class="table-row users-table">

    <span>
        CUS-001
    </span>

    <span>
        Kasun Perera
    </span>

    <span class="contact-info">
        kasun@gmail.com
        <small>0771234567</small>
    </span>

    <span class="document-actions">
        <button class="btn btn-outline btn-sm">
            View NIC
        </button>

        <button class="btn btn-outline btn-sm">
            View License
        </button>
    </span>

    <span class="row-actions">
        <button class="btn btn-primary btn-sm">
            Approve
        </button>

        <button class="btn btn-secondary btn-sm">
            Reject
        </button>
    </span>

</div>


<div class="table-row users-table">

    <span>
        CUS-002
    </span>

    <span>
        Dilani Silva
    </span>

    <span class="contact-info">
        dilani@gmail.com
        <small>0719876543</small>
    </span>

    <span class="document-actions">
        <button class="btn btn-outline btn-sm">
            View NIC
        </button>

        <button class="btn btn-outline btn-sm">
            View License
        </button>
    </span>

    <span class="row-actions">

        <button class="btn btn-primary btn-sm">
            Approve
        </button>

        <button class="btn btn-secondary btn-sm">
            Reject
        </button>

    </span>

</div>
