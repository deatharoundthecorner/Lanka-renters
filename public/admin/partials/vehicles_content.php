<?php
// Vehicles page content (partial)
?>
<header class="page-header">
    <div>
        <h1>Vehicles</h1>
        <p>Review submitted vehicles, verify documents, and manage approved vehicles.</p>
    </div>
</header>

<section class="card filters-card vehicles-filters">
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
            <p class="section-description">Filter vehicles by status, type, district, or keyword.</p>
        </div>
    </div>

    <div class="filter-grid">
        <label class="filter-group">
            <span>Search</span>
            <input type="search" placeholder="Search vehicle number, owner name, model...">
        </label>
        <label class="filter-group">
            <span>Status</span>
            <select>
                <option>All</option>
                <option>Pending Verification</option>
                <option>Approved</option>
                <option>Rejected</option>
                <option>Suspended</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Vehicle Type</span>
            <select>
                <option>All</option>
                <option>Car</option>
                <option>SUV</option>
                <option>Van</option>
                <option>Luxury Car</option>
                <option>Pickup</option>
                <option>Mini Bus</option>
            </select>
        </label>
        <label class="filter-group">
            <span>District</span>
            <select>
                <option>All Districts</option>
                <option>Colombo</option>
                <option>Gampaha</option>
                <option>Kalutara</option>
                <option>Kandy</option>
                <option>Galle</option>
                <option>Matara</option>
                <option>Kurunegala</option>
                <option>Jaffna</option>
            </select>
        </label>
        <div class="filter-actions" style="grid-column: span 4; justify-content:flex-end;">
            <button class="btn btn-primary" type="button">Search</button>
            <button class="btn btn-secondary" type="button">Reset</button>
        </div>
    </div>
</section>

<section class="panel-card wide-card" style="margin-top:24px;">
    <div class="table-header vehicles-table">
        <span>Vehicle</span>
        <span>Registration No</span>
        <span>Owner</span>
        <span>Vehicle Type</span>
        <span>District</span>
        <span>Status</span>
        <span>Actions</span>
    </div>

    <div class="table-row vehicles-table">
        <span><strong>Toyota Prius 2022</strong></span>
        <span>CAB-4587</span>
        <span>Kasun Perera</span>
        <span>Car</span>
        <span>Colombo</span>
        <span><span class="status-pill status-warning">Pending Verification</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-primary">Approve</button>
            <button class="btn btn-outline">Reject</button>
        </span>
    </div>

    <div class="table-row vehicles-table">
        <span><strong>Honda Vezel 2021</strong></span>
        <span>CAA-8645</span>
        <span>Chaminda Fernando</span>
        <span>SUV</span>
        <span>Kandy</span>
        <span><span class="status-pill status-success">Approved</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-outline">Suspend</button>
        </span>
    </div>

    <div class="table-row vehicles-table">
        <span><strong>Toyota KDH</strong></span>
        <span>NC-7854</span>
        <span>Dilani Silva</span>
        <span>Van</span>
        <span>Gampaha</span>
        <span><span class="status-pill status-suspended">Suspended</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-primary">Reactivate</button>
        </span>
    </div>

    <div class="table-row vehicles-table">
        <span><strong>Toyota Aqua 2020</strong></span>
        <span>WP-2241</span>
        <span>Ruwan Senarath</span>
        <span>Car</span>
        <span>Galle</span>
        <span><span class="status-pill status-warning">Pending Verification</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-primary">Approve</button>
            <button class="btn btn-outline">Reject</button>
        </span>
    </div>

    <div class="table-row vehicles-table">
        <span><strong>Suzuki Alto 2019</strong></span>
        <span>WP-9987</span>
        <span>Priyanka Jayawardena</span>
        <span>Car</span>
        <span>Matara</span>
        <span><span class="status-pill status-success">Approved</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-outline">Suspend</button>
        </span>
    </div>

    <div class="table-row vehicles-table">
        <span><strong>Nissan X-Trail 2023</strong></span>
        <span>KA-3365</span>
        <span>Nimal Fernando</span>
        <span>SUV</span>
        <span>Kurunegala</span>
        <span><span class="status-pill status-rejected">Rejected</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
        </span>
    </div>

    <div class="table-row vehicles-table">
        <span><strong>Toyota Axio 2018</strong></span>
        <span>WP-7654</span>
        <span>Anjali Senanayake</span>
        <span>Car</span>
        <span>Colombo</span>
        <span><span class="status-pill status-warning">Pending Verification</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-primary">Approve</button>
            <button class="btn btn-outline">Reject</button>
        </span>
    </div>

    <div class="table-row vehicles-table">
        <span><strong>Perodua Bezza 2020</strong></span>
        <span>WP-2311</span>
        <span>Sachira Perera</span>
        <span>Car</span>
        <span>Jaffna</span>
        <span><span class="status-pill status-success">Approved</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-outline">Suspend</button>
        </span>
    </div>

    <div class="table-row vehicles-table">
        <span><strong>MG ZS EV 2024</strong></span>
        <span>WP-8890</span>
        <span>Harsha Wijesinghe</span>
        <span>Luxury Car</span>
        <span>Colombo</span>
        <span><span class="status-pill status-warning">Pending Verification</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-primary">Approve</button>
            <button class="btn btn-outline">Reject</button>
        </span>
    </div>

    <div class="table-row vehicles-table">
        <span><strong>Hyundai Tucson 2022</strong></span>
        <span>WP-6784</span>
        <span>Chamara Silva</span>
        <span>SUV</span>
        <span>Gampaha</span>
        <span><span class="status-pill status-success">Approved</span></span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-outline">Suspend</button>
        </span>
    </div>
</section>

<section class="panel-card empty-bookings-card" style="display:none; margin-top:24px;">
    <div class="empty-state">
        <div class="empty-state-icon">🚗</div>
        <div>
            <h2>No vehicles found.</h2>
            <p>Adjust filters or refresh the page to load the latest vehicle submissions.</p>
            <button class="btn btn-primary" type="button">Refresh</button>
        </div>
    </div>
</section>

<div class="modal-backdrop hidden" id="vehicleDetailsModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="vehicleDetailsTitle">
        <div class="modal-header">
            <div>
                <h2 id="vehicleDetailsTitle">Vehicle details</h2>
                <p class="modal-subtitle">Vehicle profile, ownership details, and registration information.</p>
            </div>
            <button class="modal-close" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body">
            <section>
                <h3>Vehicle information</h3>
                <div class="modal-grid">
                    <div><strong>Vehicle Model</strong><p>Toyota Prius 2022</p></div>
                    <div><strong>Registration Number</strong><p>CAB-4587</p></div>
                    <div><strong>Chassis Number</strong><p>JTDK1234567890123</p></div>
                    <div><strong>Engine Number</strong><p>2ZR-FXE123456</p></div>
                    <div><strong>Vehicle Type</strong><p>Car</p></div>
                    <div><strong>Brand</strong><p>Toyota</p></div>
                    <div><strong>Year</strong><p>2022</p></div>
                    <div><strong>Color</strong><p>Silver</p></div>
                    <div><strong>Transmission</strong><p>Automatic</p></div>
                    <div><strong>Fuel Type</strong><p>Hybrid</p></div>
                    <div><strong>Seating Capacity</strong><p>5</p></div>
                    <div><strong>Daily Rental Price</strong><p>Rs. 8,500</p></div>
                </div>
            </section>
            <section>
                <h3>Vehicle owner</h3>
                <div class="modal-grid">
                    <div><strong>Owner Name</strong><p>Kasun Perera</p></div>
                    <div><strong>NIC Number</strong><p>982345678V</p></div>
                    <div><strong>Phone Number</strong><p>077 123 4567</p></div>
                    <div><strong>Email</strong><p>kasun.perera@example.com</p></div>
                </div>
            </section>
            <section>
                <h3>Insurance information</h3>
                <div class="modal-grid">
                    <div><strong>Insurance Company</strong><p>Union Assurance</p></div>
                    <div><strong>Policy Number</strong><p>UA-221145</p></div>
                    <div><strong>Expiry Date</strong><p>15 Sep 2026</p></div>
                </div>
            </section>
            <section>
                <h3>Revenue license</h3>
                <div class="modal-grid">
                    <div><strong>License Number</strong><p>RL-99871</p></div>
                    <div><strong>Expiry Date</strong><p>31 Dec 2026</p></div>
                </div>
            </section>
            <section>
                <h3>Vehicle status</h3>
                <div class="modal-grid">
                    <div><strong>Status</strong><p>Pending Verification</p></div>
                </div>
            </section>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" type="button">Close</button>
            <button class="btn btn-primary" type="button">Approve</button>
            <button class="btn btn-outline" type="button">Reject</button>
        </div>
    </div>
</div>

<div class="modal-backdrop hidden" id="vehicleDocumentsModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="vehicleDocumentsTitle">
        <div class="modal-header">
            <div>
                <h2 id="vehicleDocumentsTitle">Vehicle documents</h2>
                <p class="modal-subtitle">Review uploaded registration and vehicle documents.</p>
            </div>
            <button class="modal-close" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body">
            <section>
                <h3>Registration documents</h3>
                <div class="modal-grid">
                    <div><strong>Vehicle Registration Book</strong><p>Uploaded</p></div>
                    <div><strong>Revenue License</strong><p>Uploaded</p></div>
                    <div><strong>Insurance Certificate</strong><p>Uploaded</p></div>
                    <div><strong>Emission Test Certificate</strong><p>Uploaded</p></div>
                </div>
            </section>
            <section>
                <h3>Vehicle images</h3>
                <div class="vehicle-image-grid">
                    <div class="vehicle-image-card">Front View</div>
                    <div class="vehicle-image-card">Rear View</div>
                    <div class="vehicle-image-card">Left Side</div>
                    <div class="vehicle-image-card">Right Side</div>
                    <div class="vehicle-image-card">Interior</div>
                    <div class="vehicle-image-card">Dashboard</div>
                    <div class="vehicle-image-card">Engine Bay</div>
                </div>
            </section>
        </div>
        <div class="modal-actions">
            <button class="btn btn-primary" type="button">Approve Documents</button>
            <button class="btn btn-outline" type="button">Reject Documents</button>
            <button class="btn btn-secondary" type="button">Download All</button>
            <button class="btn btn-secondary" type="button">Close</button>
        </div>
    </div>
</div>
