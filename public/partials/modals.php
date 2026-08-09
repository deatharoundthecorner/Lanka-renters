<?php
// partials/modals.php
// Reusable Modals Component for Lanka Renters Admin Dashboard
?>

<!-- Document View Modal -->
<div class="modal-overlay" id="docViewerModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="docModalTitle">Document Preview</h3>
            <button class="modal-close-btn" onclick="closeModal('docViewerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="doc-preview-placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <p style="margin-top: 12px; font-weight: 500;">Official Sri Lankan Verification Document</p>
                <span style="font-size: 11px; color: var(--text-light); margin-top: 4px;" id="docModalType">PDF Format • 2.4 MB</span>
            </div>
            <div style="background: #F8FAFC; padding: 12px 16px; border-radius: 8px; font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="color: var(--text-muted);">Uploaded Date:</span>
                    <span style="font-weight: 600;">08 Aug 2026</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);">Verification Status:</span>
                    <span class="badge badge-approved">Identity Verified</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('docViewerModal')">Close</button>
        </div>
    </div>
</div>

<!-- Approve Confirmation Modal -->
<div class="modal-overlay" id="approveModal">
    <div class="modal-box" style="max-width: 440px;">
        <div class="modal-header">
            <h3>Approve Request</h3>
            <button class="modal-close-btn" onclick="closeModal('approveModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p id="approveModalText" style="font-size: 14px; color: var(--text-secondary);">Are you sure you want to approve this request?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('approveModal')">Cancel</button>
            <button class="btn btn-approve" onclick="confirmApprove()">Approve</button>
        </div>
    </div>
</div>

<!-- Reject Confirmation Modal -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal-box" style="max-width: 460px;">
        <div class="modal-header">
            <h3>Reject Request</h3>
            <button class="modal-close-btn" onclick="closeModal('rejectModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p id="rejectModalText" style="font-size: 14px; color: var(--text-secondary); margin-bottom: 14px;">Are you sure you want to reject this request?</p>
            <div class="filter-group">
                <label for="rejectReason">Rejection Reason (Optional):</label>
                <textarea id="rejectReason" class="form-control" rows="3" placeholder="Provide a reason for rejection..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
            <button class="btn btn-reject" onclick="confirmReject()">Reject</button>
        </div>
    </div>
</div>

<!-- Suspend Customer Modal -->
<div class="modal-overlay" id="suspendModal">
    <div class="modal-box" style="max-width: 440px;">
        <div class="modal-header">
            <h3>Suspend Customer</h3>
            <button class="modal-close-btn" onclick="closeModal('suspendModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p id="suspendModalText" style="font-size: 14px; color: var(--text-secondary);">Are you sure you want to suspend this customer?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('suspendModal')">Cancel</button>
            <button class="btn btn-reject" onclick="confirmSuspend()">Suspend</button>
        </div>
    </div>
</div>

<!-- Manual Email Modal -->
<div class="modal-overlay" id="manualEmailModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Send Manual Email</h3>
            <button class="modal-close-btn" onclick="closeModal('manualEmailModal')">&times;</button>
        </div>
        <form onsubmit="sendManualEmail(event)">
            <div class="modal-body">
                <div class="filter-group" style="margin-bottom: 14px;">
                    <label for="emailRecipientType">Recipient Type:</label>
                    <select id="emailRecipientType" class="form-control" required>
                        <option value="Customer">Customer</option>
                        <option value="Driver">Driver</option>
                        <option value="Owner">Owner</option>
                    </select>
                </div>
                <div class="filter-group" style="margin-bottom: 14px;">
                    <label for="emailRecipient">Recipient Name / Email:</label>
                    <input type="text" id="emailRecipient" class="form-control" placeholder="e.g. John Perera (kasun@gmail.com)" required>
                </div>
                <div class="filter-group" style="margin-bottom: 14px;">
                    <label for="emailSubject">Subject:</label>
                    <input type="text" id="emailSubject" class="form-control" placeholder="e.g. Booking Status Update" required>
                </div>
                <div class="filter-group">
                    <label for="emailMessage">Message:</label>
                    <textarea id="emailMessage" class="form-control" rows="4" placeholder="Write your email content here..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('manualEmailModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Email</button>
            </div>
        </form>
    </div>
</div>
