<?php
// Owners page content (partial)
?>
<header class="page-header">
    <div>
        <h1>Vehicle Owners</h1>
        <p>Review vehicle owner registrations, verify submitted documents, and manage approved owners.</p>
    </div>
</header>

<section class="card filters-card owners-filters">
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
            <p class="section-description">Search and filter owner registrations by status, district, and vehicle type.</p>
        </div>
    </div>

    <div class="filter-grid">
        <label class="filter-group">
            <span>Search</span>
            <input type="search" placeholder="Search owner name, NIC, vehicle number...">
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
        <div class="filter-actions" style="grid-column: span 5; justify-content: flex-end;">
            <button class="btn btn-primary" type="button">Search</button>
            <button class="btn btn-secondary" type="button">Reset</button>
        </div>
    </div>
</section>

<section class="panel-card wide-card" style="margin-top: 24px;">
    <div class="table-header owners-table">
        <span>Owner</span>
        <span>Contact</span>
        <span>Vehicle</span>
        <span>District</span>
        <span>Status</span>
        <span>Registered Date</span>
        <span>Actions</span>
    </div>

    <div class="table-row owners-table">
        <span><strong>Kasun Perera</strong></span>
        <span>077 123 4567</span>
        <span>Toyota Prius</span>
        <span>Colombo</span>
        <span><span class="status-pill status-warning">Pending Verification</span></span>
        <span>31 Jul 2026</span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-primary">Approve</button>
            <button class="btn btn-outline">Reject</button>
        </span>
    </div>

    <div class="table-row owners-table">
        <span><strong>Chaminda Fernando</strong></span>
        <span>071 987 6543</span>
        <span>Honda Vezel</span>
        <span>Kandy</span>
        <span><span class="status-pill status-success">Approved</span></span>
        <span>25 Jul 2026</span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-outline">Suspend</button>
        </span>
    </div>

    <div class="table-row owners-table">
        <span><strong>Dilani Silva</strong></span>
        <span>075 444 4444</span>
        <span>Toyota KDH</span>
        <span>Gampaha</span>
        <span><span class="status-pill status-rejected">Rejected</span></span>
        <span>22 Jul 2026</span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
        </span>
    </div>

    <div class="table-row owners-table">
        <span><strong>Anjali Senanayake</strong></span>
        <span>070 234 5567</span>
        <span>Suzuki Alto</span>
        <span>Matara</span>
        <span><span class="status-pill status-success">Approved</span></span>
        <span>18 Jul 2026</span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-outline">Suspend</button>
        </span>
    </div>

    <div class="table-row owners-table">
        <span><strong>Nimal Fernando</strong></span>
        <span>076 345 7890</span>
        <span>Nissan X-Trail</span>
        <span>Kurunegala</span>
        <span><span class="status-pill status-suspended">Suspended</span></span>
        <span>08 Jul 2026</span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-primary">Reactivate</button>
        </span>
    </div>

    <div class="table-row owners-table">
        <span><strong>Priyanka Jayawardena</strong></span>
        <span>072 556 1234</span>
        <span>Toyota Axio</span>
        <span>Galle</span>
        <span><span class="status-pill status-warning">Pending Verification</span></span>
        <span>04 Jul 2026</span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-primary">Approve</button>
            <button class="btn btn-outline">Reject</button>
        </span>
    </div>

    <div class="table-row owners-table">
        <span><strong>Ruwan Senarath</strong></span>
        <span>070 987 2233</span>
        <span>Perodua Bezza</span>
        <span>Colombo</span>
        <span><span class="status-pill status-success">Approved</span></span>
        <span>29 Jun 2026</span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-outline">Suspend</button>
        </span>
    </div>

    <div class="table-row owners-table">
        <span><strong>Sachira Perera</strong></span>
        <span>071 444 1122</span>
        <span>MG ZS EV</span>
        <span>Colombo</span>
        <span><span class="status-pill status-warning">Pending Verification</span></span>
        <span>15 Jun 2026</span>
        <span class="row-actions">
            <button class="btn btn-outline">View Details</button>
            <button class="btn btn-outline">View Documents</button>
            <button class="btn btn-primary">Approve</button>
            <button class="btn btn-outline">Reject</button>
        </span>
    </div>
</section>

<section class="panel-card empty-bookings-card" style="display:none; margin-top: 24px;">
    <div class="empty-state">
        <div class="empty-state-icon">🚗</div>
        <div>
            <h2>No vehicle owners found.</h2>
            <p>Adjust filters or refresh the page to load the latest owner registrations.</p>
            <button class="btn btn-primary" type="button">Refresh</button>
        </div>
    </div>
</section>

<div class="modal-backdrop hidden" id="ownerDetailsModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="ownerDetailsTitle">
        <div class="modal-header">
            <div>
                <h2 id="ownerDetailsTitle">Owner details</h2>
                <p class="modal-subtitle">Full owner profile, vehicle registrations, and banking data.</p>
            </div>
            <button id="ownerDetailsClose" class="modal-close" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body">
            <section>
                <h3>Owner information</h3>
                <div class="modal-grid">
                    <div><strong>Full Name</strong><p>Kasun Perera</p></div>
                    <div><strong>NIC Number</strong><p>982345678V</p></div>
                    <div><strong>Date of Birth</strong><p>12 Jan 1988</p></div>
                    <div><strong>Gender</strong><p>Male</p></div>
                    <div><strong>Phone Number</strong><p>077 123 4567</p></div>
                    <div><strong>Email</strong><p>kasun.perera@example.com</p></div>
                    <div><strong>Address</strong><p>12/3, Galle Road, Wellawatta, Colombo</p></div>
                    <div><strong>District</strong><p>Colombo</p></div>
                </div>
            </section>
            <section>
                <h3>Vehicle information</h3>
                <div class="modal-grid">
                    <div><strong>Vehicle 1</strong><p>Toyota Prius</p></div>
                    <div><strong>Registration Number</strong><p>CAB-4587</p></div>
                    <div><strong>Year</strong><p>2022</p></div>
                    <div><strong>Transmission</strong><p>Automatic</p></div>
                    <div><strong>Fuel</strong><p>Hybrid</p></div>
                    <div><strong>Vehicle Status</strong><p>Verified</p></div>
                </div>
                <div class="modal-grid" style="margin-top: 16px;">
                    <div><strong>Vehicle 2</strong><p>Honda Vezel</p></div>
                    <div><strong>Registration Number</strong><p>CAA-9865</p></div>
                    <div><strong>Year</strong><p>2021</p></div>
                    <div><strong>Transmission</strong><p>Automatic</p></div>
                    <div><strong>Fuel</strong><p>Petrol</p></div>
                    <div><strong>Vehicle Status</strong><p>Pending Verification</p></div>
                </div>
            </section>
            <section>
                <h3>Banking information</h3>
                <div class="modal-grid">
                    <div><strong>Bank Name</strong><p>Seylan Bank</p></div>
                    <div><strong>Account Holder</strong><p>Kasun Perera</p></div>
                    <div><strong>Account Number</strong><p>**** **** **** 1234</p></div>
                </div>
            </section>
            <section>
                <h3>Registration details</h3>
                <div class="modal-grid">
                    <div><strong>Registration Date</strong><p>31 Jul 2026</p></div>
                    <div><strong>Last Login</strong><p>03 Aug 2026</p></div>
                    <div><strong>Account Status</strong><p>Pending Verification</p></div>
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

<div class="modal-backdrop hidden" id="ownerDocumentsModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="ownerDocumentsTitle">
        <div class="modal-header">
            <div>
                <h2 id="ownerDocumentsTitle">Verification documents</h2>
                <p class="modal-subtitle">Review uploaded owner and vehicle verification documents.</p>
            </div>
            <button id="ownerDocumentsClose" class="modal-close" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body">
            <section>
                <h3>Personal documents</h3>
                <div class="modal-grid">
                    <div><strong>NIC Front</strong><p>Uploaded</p></div>
                    <div><strong>NIC Back</strong><p>Uploaded</p></div>
                    <div><strong>Profile Photo</strong><p>Uploaded</p></div>
                </div>
            </section>
            <section>
                <h3>Vehicle documents</h3>
                <div class="modal-grid">
                    <div><strong>Registration Book</strong><p>Uploaded</p></div>
                    <div><strong>Revenue License</strong><p>Uploaded</p></div>
                    <div><strong>Insurance Certificate</strong><p>Uploaded</p></div>
                    <div><strong>Vehicle Images</strong><p>Front, Rear, Side, Interior</p></div>
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
