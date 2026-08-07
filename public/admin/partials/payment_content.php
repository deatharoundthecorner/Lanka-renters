<?php
// Payment page content (partial)
?>
<header class="page-header">
    <div>
        <h1>Payment Verification</h1>
        <p>Review customer payment receipts and verify rental payments before confirming bookings.</p>
    </div>
</header>

<section class="card filters-card payments-filters">
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
            <p class="section-description">Filter payments by status, type, and date.</p>
        </div>
    </div>

    <div class="filter-grid">
        <label class="filter-group">
            <span>Search</span>
            <input type="search" placeholder="Search booking ID, customer, receipt number...">
        </label>
        <label class="filter-group">
            <span>Payment Status</span>
            <select>
                <option>All</option>
                <option>Pending Verification</option>
                <option>Verified</option>
                <option>Rejected</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Payment Type</span>
            <select>
                <option>All</option>
                <option>Security Deposit</option>
                <option>Rental Payment</option>
                <option>Full Payment</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Date</span>
            <input type="date">
        </label>
        <div class="filter-actions" style="grid-column: span 4; justify-content:flex-end;">
            <button class="btn btn-primary" type="button">Search</button>
            <button class="btn btn-secondary" type="button">Reset</button>
        </div>
    </div>
</section>

<section class="panel-card wide-card" style="margin-top:24px;">
    <div class="table-header payments-table">
        <span>Booking ID</span>
        <span>Customer</span>
        <span>Amount</span>
        <span>Payment Type</span>
        <span>Uploaded Date</span>
        <span>Status</span>
        <span>Actions</span>
    </div>

    <div class="table-row payments-table">
        <span><strong>BK-2026-001</strong></span>
        <span>Kasun Perera</span>
        <span>LKR 25,000</span>
        <span>Security Deposit</span>
        <span>31 Jul 2026</span>
        <span><span class="status-pill status-warning">Pending Verification</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Receipt</button>
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-primary">Verify</button>
            <button class="btn btn-outline">Reject</button>
        </span>
    </div>

    <div class="table-row payments-table">
        <span><strong>BK-2026-002</strong></span>
        <span>Nimal Silva</span>
        <span>LKR 48,500</span>
        <span>Full Payment</span>
        <span>30 Jul 2026</span>
        <span><span class="status-pill status-success">Verified</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Receipt</button>
            <button class="btn btn-outline">View Details</button>
        </span>
    </div>

    <div class="table-row payments-table">
        <span><strong>BK-2026-003</strong></span>
        <span>Dilani Fernando</span>
        <span>LKR 15,000</span>
        <span>Rental Payment</span>
        <span>29 Jul 2026</span>
        <span><span class="status-pill status-rejected">Rejected</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Receipt</button>
            <button class="btn btn-outline">View Details</button>
        </span>
    </div>

    <div class="table-row payments-table">
        <span><strong>BK-2026-004</strong></span>
        <span>Dilani Weerasinghe</span>
        <span>LKR 32,000</span>
        <span>Full Payment</span>
        <span>28 Jul 2026</span>
        <span><span class="status-pill status-warning">Pending Verification</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Receipt</button>
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-primary">Verify</button>
            <button class="btn btn-outline">Reject</button>
        </span>
    </div>
</section>

<section class="panel-card empty-bookings-card" style="display:none; margin-top:24px;">
    <div class="empty-state">
        <div class="empty-state-icon">💳</div>
        <div>
            <h2>No payment records found.</h2>
            <p>Adjust your filters or refresh to load the latest payment submissions.</p>
            <button class="btn btn-primary" type="button">Refresh</button>
        </div>
    </div>
</section>

<div class="modal-backdrop hidden" id="receiptModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="receiptTitle">
        <div class="modal-header">
            <div>
                <h2 id="receiptTitle">Receipt preview</h2>
                <p class="modal-subtitle">Review the uploaded bank slip or payment receipt.</p>
            </div>
            <button class="modal-close" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body">
            <div class="receipt-preview">
                <div class="receipt-preview-box">Bank Slip Preview</div>
            </div>
            <section>
                <h3>Payment information</h3>
                <div class="modal-grid">
                    <div><strong>Booking ID</strong><p>BK-2026-001</p></div>
                    <div><strong>Customer Name</strong><p>Kasun Perera</p></div>
                    <div><strong>Vehicle</strong><p>Toyota Prius</p></div>
                    <div><strong>Vehicle Owner</strong><p>Nimal Fernando</p></div>
                    <div><strong>Payment Type</strong><p>Security Deposit</p></div>
                    <div><strong>Amount</strong><p>LKR 25,000</p></div>
                    <div><strong>Bank Name</strong><p>Commercial Bank</p></div>
                    <div><strong>Reference Number</strong><p>TRX-778654</p></div>
                    <div><strong>Upload Date</strong><p>31 Jul 2026</p></div>
                    <div><strong>Uploaded By</strong><p>Kasun Perera</p></div>
                </div>
            </section>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" type="button">Close</button>
            <button class="btn btn-primary" type="button">Verify Payment</button>
            <button class="btn btn-outline" type="button">Reject Payment</button>
        </div>
    </div>
</div>

<div class="modal-backdrop hidden" id="paymentDetailsModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="paymentDetailsTitle">
        <div class="modal-header">
            <div>
                <h2 id="paymentDetailsTitle">Payment details</h2>
                <p class="modal-subtitle">Booking and payment information for admin review.</p>
            </div>
            <button class="modal-close" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body">
            <section>
                <h3>Booking information</h3>
                <div class="modal-grid">
                    <div><strong>Booking ID</strong><p>BK-2026-001</p></div>
                    <div><strong>Vehicle</strong><p>Toyota Prius</p></div>
                    <div><strong>Rental Period</strong><p>10 Aug 2026 – 15 Aug 2026</p></div>
                    <div><strong>Customer</strong><p>Kasun Perera</p></div>
                    <div><strong>Vehicle Owner</strong><p>Nimal Fernando</p></div>
                </div>
            </section>
            <section>
                <h3>Payment details</h3>
                <div class="modal-grid">
                    <div><strong>Payment Amount</strong><p>LKR 25,000</p></div>
                    <div><strong>Payment Method</strong><p>Bank Transfer</p></div>
                    <div><strong>Payment Type</strong><p>Security Deposit</p></div>
                    <div><strong>Transaction Reference</strong><p>TRX-778654</p></div>
                    <div><strong>Upload Date</strong><p>31 Jul 2026</p></div>
                    <div><strong>Current Status</strong><p>Pending Verification</p></div>
                </div>
            </section>
            <section>
                <h3>Admin notes</h3>
                <textarea rows="4" readonly>Awaiting payment verification from admin.</textarea>
            </section>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" type="button">Close</button>
        </div>
    </div>
</div>
