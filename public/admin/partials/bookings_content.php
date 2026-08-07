<?php
// Bookings page content (partial)
?>
<header class="page-header">
    <div>
        <h1>Booking Management</h1>
        <p>Review, monitor, and manage all vehicle rental bookings.</p>
    </div>
    <div class="admin-userbubble">
        <button class="btn btn-primary">Export Bookings</button>
    </div>
</header>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-title">Total Bookings</div>
            </div>
            <div class="stat-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 7H20" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round"/>
                    <path d="M8 3H16" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round"/>
                    <path d="M6 21H18" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round"/>
                    <path d="M7 11H17" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round"/>
                    <path d="M7 15H13" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
        <div class="stat-number">245</div>
        <div class="stat-caption">Total bookings processed</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-title">Active Bookings</div>
            </div>
            <div class="stat-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 3V21" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round"/>
                    <path d="M5 12H19" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
        <div class="stat-number">62</div>
        <div class="stat-caption">Bookings currently in progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-title">Pending Approval</div>
            </div>
            <div class="stat-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V12L15 14" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12C4 7.58172 7.58172 4 12 4C16.4183 4 20 7.58172 20 12Z" stroke="#1D4ED8" stroke-width="2"/>
                </svg>
            </div>
        </div>
        <div class="stat-number">14</div>
        <div class="stat-caption">Bookings awaiting approval</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-title">Completed</div>
            </div>
            <div class="stat-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 13L9 17L19 7" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <div class="stat-number">169</div>
        <div class="stat-caption">Bookings successfully completed</div>
    </div>
</div>

<section class="card filters-card">
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
            <p class="section-description">Refine booking results by status, vehicle type, district, or date range.</p>
        </div>
    </div>

    <div class="filter-grid">
        <label class="filter-group">
            <span>Search</span>
            <input type="search" placeholder="Search booking ID, customer, vehicle...">
        </label>
        <label class="filter-group">
            <span>Booking Status</span>
            <select>
                <option>All</option>
                <option>Pending</option>
                <option>Confirmed</option>
                <option>Active</option>
                <option>Completed</option>
                <option>Cancelled</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Vehicle Type</span>
            <select>
                <option>All</option>
                <option>Car</option>
                <option>SUV</option>
                <option>Van</option>
                <option>Luxury</option>
                <option>Pickup</option>
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
        <label class="filter-group">
            <span>Date Range</span>
            <input type="date">
        </label>
        <div class="filter-actions" style="grid-column: span 3; justify-content: flex-end;">
            <button class="btn btn-primary" type="button">Search</button>
            <button class="btn btn-secondary" type="button">Reset</button>
        </div>
    </div>
</section>

<section class="info-banner">
    <div>
        <strong>Administrators can review bookings, assign drivers, monitor booking progress, and manage cancellations from this page.</strong>
    </div>
</section>

<section class="panel-card wide-card">
    <div class="table-header bookings-table">
        <span>Booking ID</span>
        <span>Customer</span>
        <span>Vehicle</span>
        <span>Owner</span>
        <span>Driver</span>
        <span>Pickup</span>
        <span>Return</span>
        <span>Status</span>
        <span>Payment</span>
        <span>Action</span>
    </div>

    <div class="table-row bookings-table">
        <span>BK-2026-001</span>
        <span>Kasun Perera</span>
        <span>Toyota Prius</span>
        <span>Nimal Fernando</span>
        <span>Ruwan Bandara</span>
        <span>10 Aug 2026</span>
        <span>15 Aug 2026</span>
        <span><span class="status-pill status-confirmed">Confirmed</span></span>
        <span><span class="payment-pill payment-paid">Paid</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View</button>
        </span>
    </div>
    <div class="table-row bookings-table">
        <span>BK-2026-002</span>
        <span>Dilani Silva</span>
        <span>Honda Vezel</span>
        <span>Chaminda Perera</span>
        <span>Not Assigned</span>
        <span>12 Aug 2026</span>
        <span>18 Aug 2026</span>
        <span><span class="status-pill status-warning">Pending</span></span>
        <span><span class="payment-pill payment-pending">Pending</span></span>
        <span class="row-actions">
            <button class="btn btn-primary">Approve</button>
            <button class="btn btn-secondary">Reject</button>
            <button class="btn btn-outline">View</button>
        </span>
    </div>
    <div class="table-row bookings-table">
        <span>BK-2026-003</span>
        <span>Amal Fernando</span>
        <span>Toyota KDH</span>
        <span>Sunil Jayasinghe</span>
        <span>Lakshan Perera</span>
        <span>05 Aug 2026</span>
        <span>08 Aug 2026</span>
        <span><span class="status-pill status-active">Active</span></span>
        <span><span class="payment-pill payment-paid">Paid</span></span>
        <span class="row-actions">
            <button class="btn btn-primary">Track</button>
            <button class="btn btn-outline">View Details</button>
        </span>
    </div>
    <div class="table-row bookings-table">
        <span>BK-2026-004</span>
        <span>Sahan Perera</span>
        <span>Suzuki Alto</span>
        <span>Kasun Silva</span>
        <span>Dinesh Fernando</span>
        <span>28 Jul 2026</span>
        <span>30 Jul 2026</span>
        <span><span class="status-pill status-completed">Completed</span></span>
        <span><span class="payment-pill payment-paid">Paid</span></span>
        <span class="row-actions">
            <button class="btn btn-secondary">View Invoice</button>
            <button class="btn btn-outline">View Details</button>
        </span>
    </div>
    <div class="table-row bookings-table">
        <span>BK-2026-005</span>
        <span>Priyanka Jayawardena</span>
        <span>Hyundai Staria</span>
        <span>Rohitha Silva</span>
        <span>Not Assigned</span>
        <span>02 Aug 2026</span>
        <span>06 Aug 2026</span>
        <span><span class="status-pill status-cancelled">Cancelled</span></span>
        <span><span class="payment-pill payment-failed">Failed</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
        </span>
    </div>
</section>

<div class="modal-backdrop hidden" id="bookingDetailsModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="bookingDetailsTitle">
        <div class="modal-header">
            <div>
                <h2 id="bookingDetailsTitle">Booking details</h2>
                <p class="modal-subtitle">Booking summary, customer details, vehicle assignment, and charges.</p>
            </div>
            <button id="bookingDetailsClose" class="modal-close" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body">
            <section>
                <h3>Booking information</h3>
                <div class="modal-grid">
                    <div><strong>Booking ID</strong><p>BK-2026-002</p></div>
                    <div><strong>Booking Date</strong><p>28 Jul 2026</p></div>
                    <div><strong>Rental Period</strong><p>12 Aug 2026 – 18 Aug 2026</p></div>
                    <div><strong>Status</strong><p><span class="status-pill status-warning">Pending</span></p></div>
                    <div><strong>Payment Status</strong><p><span class="payment-pill payment-pending">Pending</span></p></div>
                </div>
            </section>
            <section>
                <h3>Customer information</h3>
                <div class="modal-grid">
                    <div><strong>Name</strong><p>Dilani Silva</p></div>
                    <div><strong>Phone</strong><p>+94 77 345 6789</p></div>
                    <div><strong>Email</strong><p>dilani.silva@example.com</p></div>
                    <div><strong>NIC</strong><p>982345678V</p></div>
                </div>
            </section>
            <section>
                <h3>Vehicle information</h3>
                <div class="modal-grid">
                    <div><strong>Vehicle</strong><p>Honda Vezel</p></div>
                    <div><strong>Registration</strong><p>WP KA-1234</p></div>
                    <div><strong>Vehicle Owner</strong><p>Chaminda Perera</p></div>
                </div>
            </section>
            <section>
                <h3>Driver information</h3>
                <div class="modal-grid">
                    <div><strong>Driver Name</strong><p>Not Assigned</p></div>
                    <div><strong>Phone</strong><p>—</p></div>
                    <div><strong>License Number</strong><p>—</p></div>
                </div>
                <button class="btn btn-primary" type="button">Assign Driver</button>
            </section>
            <section>
                <h3>Pickup information</h3>
                <div class="modal-grid">
                    <div><strong>Pickup Date</strong><p>12 Aug 2026</p></div>
                    <div><strong>Pickup Time</strong><p>09:30 AM</p></div>
                    <div><strong>Pickup Location</strong><p>Colombo Fort</p></div>
                </div>
            </section>
            <section>
                <h3>Return information</h3>
                <div class="modal-grid">
                    <div><strong>Return Date</strong><p>18 Aug 2026</p></div>
                    <div><strong>Return Time</strong><p>04:00 PM</p></div>
                    <div><strong>Return Location</strong><p>Colombo Fort</p></div>
                </div>
            </section>
            <section>
                <h3>Rental charges</h3>
                <div class="modal-grid">
                    <div><strong>Rental Fee</strong><p>Rs. 46,500</p></div>
                    <div><strong>Driver Fee</strong><p>Rs. 8,500</p></div>
                    <div><strong>Security Deposit</strong><p>Rs. 12,000</p></div>
                    <div><strong>Additional Charges</strong><p>Rs. 2,200</p></div>
                    <div><strong>Total Amount</strong><p>Rs. 69,200</p></div>
                </div>
            </section>
            <section>
                <h3>Admin notes</h3>
                <textarea rows="4" readonly>No driver assigned yet. Confirm availability before approving the booking.</textarea>
            </section>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" type="button">Close</button>
            <button class="btn btn-outline" type="button">Cancel Booking</button>
            <button class="btn btn-primary" type="button">Print Booking</button>
        </div>
    </div>
</div>
