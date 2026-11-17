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
            <h3>Suspend Record</h3>
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

<!-- Create / Edit Announcement Modal -->
<div class="modal-overlay" id="announcementModal">
    <div class="modal-box" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="annModalHeaderTitle">Create Announcement</h3>
            <button class="modal-close-btn" onclick="closeModal('announcementModal')">&times;</button>
        </div>
        <form id="announcementForm" onsubmit="saveAnnouncement(event)">
            <input type="hidden" id="annFormId" value="">
            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                <div class="filter-group" style="margin-bottom: 14px;">
                    <label for="annTitle">Announcement Title</label>
                    <input type="text" id="annTitle" class="form-control" placeholder="e.g. Scheduled System Maintenance" required oninput="updateLivePreview()">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div class="filter-group">
                        <label for="annType">Announcement Type</label>
                        <select id="annType" class="form-control" required onchange="updateLivePreview()">
                            <option value="Maintenance">Maintenance</option>
                            <option value="System Update">System Update</option>
                            <option value="Important Notice">Important Notice</option>
                            <option value="Payment Notice">Payment Notice</option>
                            <option value="Booking Notice">Booking Notice</option>
                            <option value="Emergency">Emergency</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="annTarget">Target Audience</label>
                        <select id="annTarget" class="form-control" required onchange="updateLivePreview()">
                            <option value="All Users">All Users</option>
                            <option value="Customers">Customers</option>
                            <option value="Vehicle Owners">Vehicle Owners</option>
                            <option value="Drivers">Drivers</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div class="filter-group">
                        <label for="annPriority">Priority</label>
                        <select id="annPriority" class="form-control" required onchange="updateLivePreview()">
                            <option value="Normal">Normal</option>
                            <option value="Important">Important</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="annStatus">Status</label>
                        <select id="annStatus" class="form-control" required>
                            <option value="Draft">Draft</option>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Published">Published</option>
                        </select>
                    </div>
                </div>

                <div class="filter-group" style="margin-bottom: 14px;">
                    <label for="annMessage">Message Content</label>
                    <textarea id="annMessage" class="form-control" rows="4" placeholder="Write announcement message..." required oninput="updateLivePreview()"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div class="filter-group">
                        <label for="annPublishDate">Publish Date</label>
                        <input type="date" id="annPublishDate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="filter-group">
                        <label for="annPublishTime">Publish Time</label>
                        <input type="time" id="annPublishTime" class="form-control" value="10:00" required>
                    </div>
                    <div class="filter-group">
                        <label for="annExpiryDate">Expiry Date</label>
                        <input type="date" id="annExpiryDate" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('announcementModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="annFormSubmitBtn">Create Announcement</button>
            </div>
        </form>
    </div>
</div>

<!-- View Announcement Details Modal -->
<div class="modal-overlay" id="viewAnnouncementModal">
    <div class="modal-box" style="max-width: 580px;">
        <div class="modal-header">
            <h3>Announcement Details</h3>
            <button class="modal-close-btn" onclick="closeModal('viewAnnouncementModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <span class="incident-id" id="viewAnnId">ANN-001</span>
                <div style="display: flex; gap: 6px;" id="viewAnnBadges">
                    <span class="badge badge-approved">Published</span>
                </div>
            </div>

            <h2 style="font-size: 18px; font-weight: 700; color: var(--text-main); margin-bottom: 12px;" id="viewAnnTitle">Scheduled System Maintenance</h2>

            <div style="background: #F8FAFC; border: 1px solid var(--border-light); border-radius: 8px; padding: 14px; font-size: 13px; margin-bottom: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div><strong style="color: var(--text-muted);">Type:</strong> <span id="viewAnnType">Maintenance</span></div>
                    <div><strong style="color: var(--text-muted);">Audience:</strong> <span id="viewAnnAudience">All Users</span></div>
                    <div><strong style="color: var(--text-muted);">Published:</strong> <span id="viewAnnPublish">10 Aug 2026, 10:00 PM</span></div>
                    <div><strong style="color: var(--text-muted);">Expires:</strong> <span id="viewAnnExpiry">11 Aug 2026, 12:00 AM</span></div>
                    <div><strong style="color: var(--text-muted);">Created By:</strong> <span id="viewAnnAuthor">Admin</span></div>
                </div>
            </div>

            <div style="font-size: 14px; color: var(--text-main); line-height: 1.6; background: #FFFFFF; border: 1px solid var(--border); padding: 16px; border-radius: 8px;" id="viewAnnMessage">
                Lanka Renters will be temporarily unavailable on Sunday from 10:00 PM to 12:00 AM due to scheduled system maintenance. Please complete any important bookings before the maintenance period.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('viewAnnouncementModal')">Close</button>
        </div>
    </div>
</div>

<!-- Delete Announcement Confirmation Modal -->
<div class="modal-overlay" id="deleteAnnouncementModal">
    <div class="modal-box" style="max-width: 440px;">
        <div class="modal-header">
            <h3>Delete Announcement?</h3>
            <button class="modal-close-btn" onclick="closeModal('deleteAnnouncementModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 8px;">Are you sure you want to delete this announcement?</p>
            <p style="font-size: 13px; font-weight: 600; color: var(--text-main); background: var(--bg-main); padding: 10px; border-radius: 6px;" id="deleteAnnTitleText">Scheduled System Maintenance</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('deleteAnnouncementModal')">Cancel</button>
            <button class="btn btn-reject" onclick="confirmDeleteAnnouncement()">Delete Announcement</button>
        </div>
    </div>
</div>
