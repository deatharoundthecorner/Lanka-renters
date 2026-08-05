<?php
// Incident page content (partial)
?>
<header class="page-header">
    <div>
        <h1>Incident management</h1>
        <p>Review accident and breakdown reports.</p>
    </div>
</header>

<section class="card filters-card">
    <div class="filters-card-header">
        <div>
            <p class="section-label">Filters</p>
            <p class="section-description">Refine incident results by district, vehicle type, or claim status.</p>
        </div>
    </div>

    <div class="filters-grid">
        <label class="filter-group">
            <span>District</span>
            <select id="incidentDistrict">
                <option value="">All districts</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Vehicle type</span>
            <select id="incidentVehicleType">
                <option value="">All vehicle types</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Status</span>
            <select id="incidentStatus">
                <option value="">All</option>
                <option value="Pending">Pending</option>
                <option value="Under Review">Under Review</option>
                <option value="Disputed">Disputed</option>
                <option value="Approved">Approved</option>
                <option value="Resolved">Resolved</option>
            </select>
        </label>
    </div>
</section>

<section class="card note-banner">
    <strong>Damage policy:</strong>
    <span>Damage costs are not automatically deducted. Admin reviews evidence, inspection reports, and incident details before deduction.</span>
</section>

<section id="incidentList" class="incident-list">
    <!-- Sample incident cards (Sri Lankan data) -->
    <article class="incident-card" data-incident-id="IN-201" data-status="Disputed">
        <div class="incident-main">
            <div class="incident-header">
                <div>
                    <div class="incident-title">IN-201 • Accident</div>
                    <div class="incident-meta">
                        <span class="incident-detail">Toyota Aqua</span>
                        <span class="incident-detail">Colombo</span>
                        <span class="incident-detail">Booking: BK-451</span>
                        <span class="incident-detail">Customer: Chaminda Perera</span>
                        <span class="incident-detail">Owner: Priyantha Fernando</span>
                        <span class="incident-detail">Reported: 2026-07-22</span>
                    </div>
                </div>
                <div>
                    <div class="status-pill status-rejected">Disputed</div>
                </div>
            </div>
        </div>
        <div class="incident-actions">
            <div class="row-actions">
                <button class="btn btn-secondary view-details" data-incident-id="IN-201">View Details</button>
                <button class="btn btn-outline view-evidence" data-evidence="assets/images/evidence-in201-1.jpg,assets/docs/in201-report.pdf">View Evidence</button>
                <button class="btn btn-outline contact-owner" data-owner-phone="+94-71-234-5678">Contact Vehicle Owner</button>
                <button class="btn btn-primary assign-btn" data-incident-id="IN-201">Assign Replacement Vehicle</button>
            </div>
        </div>
    </article>

    <article class="incident-card" data-incident-id="IN-198" data-status="Pending">
        <div class="incident-main">
            <div class="incident-header">
                <div>
                    <div class="incident-title">IN-198 • Breakdown</div>
                    <div class="incident-meta">
                        <span class="incident-detail">Toyota KDH</span>
                        <span class="incident-detail">Gampaha</span>
                        <span class="incident-detail">Booking: BK-438</span>
                        <span class="incident-detail">Customer: Nadeesha Fernando</span>
                        <span class="incident-detail">Owner: Sunil Perera</span>
                        <span class="incident-detail">Reported: 2026-07-18</span>
                    </div>
                </div>
                <div>
                    <div class="status-pill status-warning">Pending</div>
                </div>
            </div>
        </div>
        <div class="incident-actions">
            <div class="row-actions">
                <button class="btn btn-secondary view-details" data-incident-id="IN-198">View Details</button>
                <button class="btn btn-outline view-evidence" data-evidence="assets/images/evidence-in198-1.jpg">View Evidence</button>
                <button class="btn btn-outline contact-owner" data-owner-phone="+94-77-987-6543">Contact Vehicle Owner</button>
                <button class="btn btn-primary assign-btn" data-incident-id="IN-198">Assign Replacement Vehicle</button>
            </div>
        </div>
    </article>

    <article class="incident-card" data-incident-id="IN-176" data-status="Under Review">
        <div class="incident-main">
            <div class="incident-header">
                <div>
                    <div class="incident-title">IN-176 • Engine Failure</div>
                    <div class="incident-meta">
                        <span class="incident-detail">Honda Vezel</span>
                        <span class="incident-detail">Kandy</span>
                        <span class="incident-detail">Booking: BK-412</span>
                        <span class="incident-detail">Customer: Ruwan Silva</span>
                        <span class="incident-detail">Owner: Kasun Jayasuriya</span>
                        <span class="incident-detail">Reported: 2026-06-30</span>
                    </div>
                </div>
                <div>
                    <div class="status-pill status-warning">Under Review</div>
                </div>
            </div>
        </div>
        <div class="incident-actions">
            <div class="row-actions">
                <button class="btn btn-secondary view-details" data-incident-id="IN-176">View Details</button>
                <button class="btn btn-outline view-evidence" data-evidence="assets/images/evidence-in176-1.jpg">View Evidence</button>
                <button class="btn btn-outline contact-owner" data-owner-phone="+94-71-555-1212">Contact Vehicle Owner</button>
                <button class="btn btn-primary assign-btn" data-incident-id="IN-176">Assign Replacement Vehicle</button>
            </div>
        </div>
    </article>

    <article class="incident-card" data-incident-id="IN-165" data-status="Pending">
        <div class="incident-main">
            <div class="incident-header">
                <div>
                    <div class="incident-title">IN-165 • Flat Tyre</div>
                    <div class="incident-meta">
                        <span class="incident-detail">Suzuki Alto</span>
                        <span class="incident-detail">Kurunegala</span>
                        <span class="incident-detail">Booking: BK-399</span>
                        <span class="incident-detail">Customer: Malsha Senanayake</span>
                        <span class="incident-detail">Owner: Dilshan Fernando</span>
                        <span class="incident-detail">Reported: 2026-06-12</span>
                    </div>
                </div>
                <div>
                    <div class="status-pill status-warning">Pending</div>
                </div>
            </div>
        </div>
        <div class="incident-actions">
            <div class="row-actions">
                <button class="btn btn-secondary view-details" data-incident-id="IN-165">View Details</button>
                <button class="btn btn-outline view-evidence" data-evidence="assets/images/evidence-in165-1.jpg">View Evidence</button>
                <button class="btn btn-outline contact-owner" data-owner-phone="+94-72-333-4444">Contact Vehicle Owner</button>
                <button class="btn btn-primary assign-btn" data-incident-id="IN-165">Assign Replacement Vehicle</button>
            </div>
        </div>
    </article>

    <article class="incident-card" data-incident-id="IN-154" data-status="Approved">
        <div class="incident-main">
            <div class="incident-header">
                <div>
                    <div class="incident-title">IN-154 • Battery Failure</div>
                    <div class="incident-meta">
                        <span class="incident-detail">Toyota Prius</span>
                        <span class="incident-detail">Matara</span>
                        <span class="incident-detail">Booking: BK-355</span>
                        <span class="incident-detail">Customer: Priyanka Jayawardena</span>
                        <span class="incident-detail">Owner: Asanka Wijesinghe</span>
                        <span class="incident-detail">Reported: 2026-05-27</span>
                    </div>
                </div>
                <div>
                    <div class="status-pill status-active">Approved</div>
                </div>
            </div>
        </div>
        <div class="incident-actions">
            <div class="row-actions">
                <button class="btn btn-secondary view-details" data-incident-id="IN-154">View Details</button>
                <button class="btn btn-outline view-evidence" data-evidence="assets/images/evidence-in154-1.jpg">View Evidence</button>
                <button class="btn btn-outline contact-owner" data-owner-phone="+94-76-222-1111">Contact Vehicle Owner</button>
            </div>
        </div>
    </article>

    <article class="incident-card" data-incident-id="IN-142" data-status="Resolved">
        <div class="incident-main">
            <div class="incident-header">
                <div>
                    <div class="incident-title">IN-142 • Brake Issue</div>
                    <div class="incident-meta">
                        <span class="incident-detail">Nissan X-Trail</span>
                        <span class="incident-detail">Negombo</span>
                        <span class="incident-detail">Booking: BK-328</span>
                        <span class="incident-detail">Customer: Kanishka Rodrigo</span>
                        <span class="incident-detail">Owner: Harsha Perera</span>
                        <span class="incident-detail">Reported: 2026-05-10</span>
                    </div>
                </div>
                <div>
                    <div class="status-pill status-success">Resolved</div>
                </div>
            </div>
        </div>
        <div class="incident-actions">
            <div class="row-actions">
                <button class="btn btn-secondary view-details" data-incident-id="IN-142">View Details</button>
                <button class="btn btn-outline view-evidence" data-evidence="assets/images/evidence-in142-1.jpg">View Evidence</button>
                <button class="btn btn-outline contact-owner" data-owner-phone="+94-70-111-2222">Contact Vehicle Owner</button>
            </div>
        </div>
    </article>
</section>

<!-- View Details Modal -->
<div class="modal-backdrop hidden" id="detailsModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="detailsTitle">
        <div class="modal-header">
            <div>
                <h2 id="detailsTitle">Incident Details</h2>
                <p class="modal-subtitle">Full incident information and admin actions.</p>
            </div>
            <button id="detailsClose" class="modal-close" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body" id="detailsBody">
            <section>
                <h3>Incident Information</h3>
                <div id="d-incident-id"></div>
                <div id="d-booking-id"></div>
                <div id="d-incident-type"></div>
                <div id="d-date-time"></div>
                <div id="d-location"></div>
                <div id="d-current-status"></div>
            </section>
            <section>
                <h3>Vehicle Information</h3>
                <div id="d-vehicle-number"></div>
                <div id="d-vehicle-model"></div>
                <div id="d-vehicle-owner"></div>
                <div id="d-vehicle-type"></div>
            </section>
            <section>
                <h3>Customer Information</h3>
                <div id="d-customer-name"></div>
                <div id="d-customer-contact"></div>
            </section>
            <section>
                <h3>Driver Information</h3>
                <div id="d-driver-name"></div>
                <div id="d-driver-contact"></div>
            </section>
            <section>
                <h3>Incident Description</h3>
                <div class="receipt-box" id="d-incident-description" style="white-space:pre-wrap"></div>
            </section>
            <section>
                <h3>Uploaded Evidence</h3>
                <div id="d-evidence-list"></div>
            </section>
            <section>
                <h3>Admin Actions</h3>
                <textarea id="adminRemarks" rows="4" style="width:100%;padding:12px;border-radius:12px;border:1px solid #e6eef8"></textarea>
            </section>
        </div>
        <div class="modal-actions">
            <button id="btnApprove" class="btn btn-primary">Approve Report</button>
            <button id="btnReject" class="btn btn-secondary">Reject Report</button>
            <button id="btnAssignReplacement" class="btn btn-primary">Assign Replacement Vehicle</button>
            <button id="btnCloseDetails" class="btn btn-secondary">Close</button>
        </div>
    </div>
</div>

<div class="modal-backdrop hidden" id="evidenceModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalTitle">Incident evidence</h2>
                <p id="modalSubTitle" class="modal-subtitle">Review the incident report and evidence details.</p>
            </div>
            <button id="modalClose" class="modal-close" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body" id="modalBody"></div>
        <div class="modal-actions">
            <button class="btn btn-primary" id="modalAction">Mark as Reviewed</button>
        </div>
    </div>
</div>
