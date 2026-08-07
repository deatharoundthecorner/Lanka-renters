<?php
// Email logs partial content
?>
<header class="page-header">
    <div>
        <h1>Email Logs</h1>
        <p>Review system emails sent to customers, vehicle owners, and drivers.</p>
    </div>
</header>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-title">Total Emails</div>
        <div class="stat-number">10</div>
        <div class="stat-caption">All messages sent from the platform</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Sent Today</div>
        <div class="stat-number">6</div>
        <div class="stat-caption">Delivered successfully in the last 24 hours</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Failed Emails</div>
        <div class="stat-number">1</div>
        <div class="stat-caption">Require manual resend</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Pending Queue</div>
        <div class="stat-number">1</div>
        <div class="stat-caption">Awaiting dispatch or retry</div>
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
            <p class="section-description">Refine email logs by recipient, status, date and booking.</p>
        </div>
    </div>

    <form class="filter-grid" method="GET" action="">
        <label class="filter-group">
            <span>Search</span>
            <input type="search" name="q" placeholder="Search by recipient, subject or booking ID..." />
        </label>
        <label class="filter-group">
            <span>Recipient Type</span>
            <select name="recipient_type">
                <option>All</option>
                <option>Customer</option>
                <option>Vehicle Owner</option>
                <option>Driver</option>
                <option>Admin</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Status</span>
            <select name="status">
                <option>All</option>
                <option>Sent</option>
                <option>Pending</option>
                <option>Failed</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Date</span>
            <input type="date" name="date" />
        </label>
        <div class="filter-group" style="display:flex; align-items:flex-end; gap:12px; grid-column: span 2;">
            <button class="btn btn-primary" type="submit">Search</button>
            <button class="btn btn-secondary" type="reset">Reset</button>
        </div>
    </form>
</section>

<section class="info-banner">
    <strong>Email logs are retained for audit purposes.</strong>
    <span>Failed emails can be resent manually by the administrator.</span>
</section>

<section class="panel-card wide-card" style="margin-top: 24px;">
    <div class="table-header email-table-header">
        <span>Time</span>
        <span>Recipient</span>
        <span>Email Type</span>
        <span>Subject</span>
        <span>Booking</span>
        <span>Status</span>
        <span>Action</span>
    </div>

    <div class="table-row email-table-row">
        <span>31 Jul 2026 10:22</span>
        <span>Nimal Perera</span>
        <span>Customer</span>
        <span>Booking Confirmation</span>
        <span>BK-451</span>
        <span><span class="status-pill status-sent">Sent</span></span>
        <span class="row-actions">
            <button class="btn btn-outline view-email" type="button" data-recipient="Nimal Perera" data-type="Customer" data-email="nimal@example.com" data-time="31 Jul 2026 10:22" data-status="Sent" data-subject="Booking Confirmation" data-body="Dear Nimal, your booking has been confirmed. Please review your pickup details and contact support if needed." data-booking="BK-451" data-customer="Nimal Perera" data-vehicle="Toyota Aqua">View</button>
        </span>
    </div>

    <div class="table-row email-table-row">
        <span>30 Jul 2026 08:12</span>
        <span>Sara Fernando</span>
        <span>Vehicle Owner</span>
        <span>Payment Verified</span>
        <span>BK-388</span>
        <span><span class="status-pill status-sent">Sent</span></span>
        <span class="row-actions">
            <button class="btn btn-outline view-email" type="button" data-recipient="Sara Fernando" data-type="Vehicle Owner" data-email="sara@example.com" data-time="30 Jul 2026 08:12" data-status="Sent" data-subject="Payment Verified" data-body="Hello Sara, the payment for booking BK-388 has been verified. The amount will be transferred shortly." data-booking="BK-388" data-customer="Sara Fernando" data-vehicle="Toyota KDH Van">View</button>
        </span>
    </div>

    <div class="table-row email-table-row">
        <span>29 Jul 2026 03:55</span>
        <span>Chaminda Silva</span>
        <span>Driver</span>
        <span>Driver Assignment</span>
        <span>BK-219</span>
        <span><span class="status-pill status-pending">Pending</span></span>
        <span class="row-actions">
            <button class="btn btn-outline view-email" type="button" data-recipient="Chaminda Silva" data-type="Driver" data-email="chaminda@example.com" data-time="29 Jul 2026 03:55" data-status="Pending" data-subject="Driver Assignment" data-body="Chaminda, a new assignment has been scheduled for booking BK-219. Please confirm availability." data-booking="BK-219" data-customer="Chaminda Silva" data-vehicle="Toyota Premio">View</button>
        </span>
    </div>

    <div class="table-row email-table-row">
        <span>28 Jul 2026 11:45</span>
        <span>Kasun Perera</span>
        <span>Customer</span>
        <span>Replacement Approved</span>
        <span>BK-172</span>
        <span><span class="status-pill status-failed">Failed</span></span>
        <span class="row-actions">
            <button class="btn btn-primary retry-email" type="button" data-recipient="Kasun Perera" data-type="Customer" data-email="kasun@example.com" data-time="28 Jul 2026 11:45" data-status="Failed" data-subject="Replacement Approved" data-body="Dear Kasun, your replacement request has been approved. We attempted to send a confirmation email but it failed." data-booking="BK-172" data-customer="Kasun Perera" data-vehicle="Suzuki Wagon R">Retry</button>
        </span>
    </div>
</section>

<div class="modal-backdrop hidden" id="emailModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="emailModalTitle">
        <div class="modal-header">
            <div>
                <h2 id="emailModalTitle">Email details</h2>
                <p class="modal-subtitle">Review the sent message and related booking information.</p>
            </div>
            <button id="closeEmailModal" class="modal-close" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body">
            <div class="list-card">
                <div class="list-item">
                    <div>
                        <div class="stat-title">Recipient</div>
                        <div id="emailRecipient">—</div>
                    </div>
                    <div>
                        <div class="stat-title">Recipient Type</div>
                        <div id="emailRecipientType">—</div>
                    </div>
                </div>
                <div class="list-item">
                    <div>
                        <div class="stat-title">Email Address</div>
                        <div id="emailAddress">—</div>
                    </div>
                    <div>
                        <div class="stat-title">Sent Time</div>
                        <div id="emailSentTime">—</div>
                    </div>
                </div>
                <div class="list-item">
                    <div>
                        <div class="stat-title">Status</div>
                        <div id="emailStatus">—</div>
                    </div>
                </div>
            </div>
            <div>
                <div class="stat-title">Subject</div>
                <div id="emailSubject">—</div>
            </div>
            <div class="receipt-box">
                <div class="stat-title">Email Content</div>
                <p id="emailBody" style="white-space: pre-line; color: #334155; line-height:1.75; margin-top:12px;">—</p>
            </div>
            <div class="receipt-box">
                <div class="stat-title">Related Booking</div>
                <div class="item-detail" style="margin-top:12px;"><strong>Booking ID:</strong> <span id="emailBookingId">—</span></div>
                <div class="item-detail"><strong>Customer:</strong> <span id="emailBookingCustomer">—</span></div>
                <div class="item-detail"><strong>Vehicle:</strong> <span id="emailBookingVehicle">—</span></div>
            </div>
        </div>
        <div class="modal-actions">
            <button id="closeEmailModalFooter" class="btn btn-secondary" type="button">Close</button>
            <button id="resendEmailButton" class="btn btn-primary" type="button">Resend Email</button>
        </div>
    </div>
</div>
