<?php
// Incident page content (partial)
?>
<header class="page-header">
    <div>
        <h1>Incident management</h1>
        <p>Review accident and breakdown reports.</p>
    </div>
</header>

<section class="panel-card filters-panel">
    <div class="filters-title">
        <span>Filters</span>
    </div>
    <div class="filter-grid">
        <label class="filter-group">
            <span>District</span>
            <select id="incidentDistrict">
                <option value="">All</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Vehicle type</span>
            <select id="incidentVehicleType">
                <option value="">All</option>
            </select>
        </label>
        <label class="filter-group">
            <span>Status</span>
            <select id="incidentStatus">
                <option value="">All</option>
                <option value="Pending">Pending</option>
                <option value="Disputed">Disputed</option>
                <option value="Resolved">Resolved</option>
            </select>
        </label>
    </div>
</section>

<section id="incidentList" class="incident-list">
    <!-- Incident cards render here -->
</section>

<section class="panel-card note-banner">
    <strong>Damage policy:</strong> Damage costs are not automatically deducted. Admin reviews evidence, inspection reports, and incident details before deduction.
    </section>

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
