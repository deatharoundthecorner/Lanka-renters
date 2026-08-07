document.addEventListener('DOMContentLoaded', function () {
    const approveButton = document.getElementById('approveSettlement');
    const ownerEarningsValue = document.getElementById('ownerEarningsValue');

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    if (approveButton) {
        approveButton.addEventListener('click', function () {
            if (approveButton.disabled) return;

            approveButton.disabled = true;
            approveButton.textContent = 'Approved';
            approveButton.classList.remove('btn-primary');
            approveButton.classList.add('btn-secondary');

            if (ownerEarningsValue) {
                ownerEarningsValue.textContent = 'Rs. 63,250';
            }

            showToast('Settlement approved and payout queued.');
        });
    }

    // Payment verification modal + flow
    const verifyButton = document.getElementById('verifyPayment');
    const verifyModal = document.getElementById('verifyModal');
    const closeVerifyModal = document.getElementById('closeVerifyModal');
    const cancelVerify = document.getElementById('cancelVerify');
    const confirmVerify = document.getElementById('confirmVerify');

    function openVerifyModal() {
        if (verifyModal) verifyModal.classList.remove('hidden');
    }
    function closeVerifyModalFn() {
        if (verifyModal) verifyModal.classList.add('hidden');
    }

    if (verifyButton) {
        verifyButton.addEventListener('click', function () {
            openVerifyModal();
        });
    }
    if (closeVerifyModal) closeVerifyModal.addEventListener('click', closeVerifyModalFn);
    if (cancelVerify) cancelVerify.addEventListener('click', closeVerifyModalFn);
    if (confirmVerify) {
        confirmVerify.addEventListener('click', function () {
            closeVerifyModalFn();
            if (verifyButton) {
                verifyButton.disabled = true;
                verifyButton.textContent = 'Verified';
                verifyButton.classList.remove('btn-primary');
                verifyButton.classList.add('btn-secondary');
            }
            showToast('Payment verified and booking will be activated.');
        });
    }

    // Replacement requests: view modal and actions
    const requestModal = document.getElementById('requestModal');
    const closeRequestModal = document.getElementById('closeRequestModal');
    const modalApprove = document.getElementById('modalApprove');
    const modalReject = document.getElementById('modalReject');

    function openRequestModal() { if (requestModal) requestModal.classList.remove('hidden'); }
    function closeRequestModalFn() { if (requestModal) requestModal.classList.add('hidden'); }

    // Open modal when clicking any .view-request buttons
    document.querySelectorAll('.view-request').forEach(btn => {
        btn.addEventListener('click', function () {
            const data = btn.dataset; // contains data-requestid, data-booking, etc.
            if (!requestModal) return;
            // populate modal fields if present
            const set = (selector, value) => {
                const el = requestModal.querySelector(selector);
                if (el) el.textContent = value || '—';
            }
            set('.m-request-id', data.requestid);
            set('.m-booking-id', data.booking);
            set('.m-request-date', data.date);
            set('.m-reason', data.reason);
            set('.m-customer-name', data.customer);
            set('.m-customer-phone', data.phone);
            set('.m-customer-email', data.email);
            set('.m-driver-name', data.driver);
            set('.m-driver-rating', data.rating);
            set('.m-driver-trips', data.trips);
            set('.m-driver-license', data.license);
            set('.m-driver-exp', data.experience);
            set('.m-vehicle', data.vehicle);
            set('.m-pickup', data.pickup);
            set('.m-destination', data.destination);
            set('.m-rental-date', data.rentaldate);
            set('.m-return-date', data.returndate);
            openRequestModal();
        });
    });

    if (closeRequestModal) closeRequestModal.addEventListener('click', closeRequestModalFn);

    if (modalApprove) {
        modalApprove.addEventListener('click', function () {
            closeRequestModalFn();
            showToast('Replacement request approved.');
        });
    }
    if (modalReject) {
        modalReject.addEventListener('click', function () {
            closeRequestModalFn();
            showToast('Replacement request rejected.');
        });
    }

    // Incident list interactions: view details, view evidence, contact owner, assign replacement
    const evidenceModal = document.getElementById('evidenceModal');
    const evidenceModalBody = document.getElementById('modalBody');
    const evidenceModalClose = document.getElementById('modalClose');
    const evidenceModalAction = document.getElementById('modalAction');

    const detailsModal = document.getElementById('detailsModal');
    const detailsClose = document.getElementById('detailsClose');
    const btnCloseDetails = document.getElementById('btnCloseDetails');
    const btnApprove = document.getElementById('btnApprove');
    const btnReject = document.getElementById('btnReject');
    const btnAssignReplacement = document.getElementById('btnAssignReplacement');

    function openEvidenceModal(html) {
        if (!evidenceModal) return;
        if (evidenceModalBody) evidenceModalBody.innerHTML = html;
        evidenceModal.classList.remove('hidden');
    }
    function closeEvidenceModal() {
        if (!evidenceModal) return;
        evidenceModal.classList.add('hidden');
        if (evidenceModalBody) evidenceModalBody.innerHTML = '';
    }

    function openDetailsModal() { if (detailsModal) detailsModal.classList.remove('hidden'); }
    function closeDetailsModal() { if (detailsModal) detailsModal.classList.add('hidden'); }

    // Show/hide assign buttons based on status
    document.querySelectorAll('.incident-card').forEach(card => {
        const status = card.dataset.status || '';
        const assign = card.querySelector('.assign-btn');
        if (!assign) return;
        if (status === 'Pending' || status === 'Under Review') {
            assign.style.display = '';
        } else {
            assign.style.display = 'none';
        }
    });

    // View Evidence buttons
    document.querySelectorAll('.view-evidence').forEach(btn => {
        btn.addEventListener('click', function () {
            const raw = btn.dataset.evidence || '';
            const parts = raw.split(',').map(s => s.trim()).filter(Boolean);
            let html = '';
            parts.forEach(p => {
                if (p.match(/\.pdf$/i)) {
                    html += `<div style="margin-bottom:12px"><a href="${p}" target="_blank">Open PDF: ${p.split('/').pop()}</a></div>`;
                } else {
                    html += `<img src="${p}" alt="evidence" style="max-width:100%;border-radius:12px;margin-bottom:12px;border:1px solid #e6eef8">`;
                }
            });
            if (!html) html = '<div>No evidence uploaded.</div>';
            openEvidenceModal(html);
        });
    });

    if (evidenceModalClose) evidenceModalClose.addEventListener('click', closeEvidenceModal);
    if (evidenceModalAction) evidenceModalAction.addEventListener('click', function () { closeEvidenceModal(); showToast('Evidence marked as reviewed.'); });

    // Contact owner
    document.querySelectorAll('.contact-owner').forEach(btn => {
        btn.addEventListener('click', function () {
            const phone = btn.dataset.ownerPhone || '';
            if (phone) {
                // try to open phone dialer; fallback to copy
                window.location.href = `tel:${phone}`;
            } else {
                showToast('Owner phone not available.');
            }
        });
    });

    // Assign replacement from card
    document.querySelectorAll('.assign-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = btn.dataset.incidentId || '—';
            btn.disabled = true;
            btn.textContent = 'Assigned';
            showToast(`Replacement vehicle assigned for ${id}.`);
        });
    });

    // View Details - populate modal from card content
    document.querySelectorAll('.view-details').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = btn.dataset.incidentId;
            const card = document.querySelector(`.incident-card[data-incident-id="${id}"]`);
            if (!card) return;

            const title = card.querySelector('.incident-title')?.textContent || '';
            const meta = Array.from(card.querySelectorAll('.incident-meta .incident-detail')).map(n => n.textContent || '');
            // Map meta: [model, district, booking, customer, owner, reported]
            document.getElementById('d-incident-id').textContent = title;
            document.getElementById('d-booking-id').textContent = meta[2] || '';
            document.getElementById('d-incident-type').textContent = title.split('•')[1]?.trim() || '';
            document.getElementById('d-date-time').textContent = meta[5] || '';
            document.getElementById('d-location').textContent = meta[1] || '';
            document.getElementById('d-current-status').textContent = card.dataset.status || '';

            document.getElementById('d-vehicle-number').textContent = 'Vehicle Number: —';
            document.getElementById('d-vehicle-model').textContent = `Model: ${meta[0] || ''}`;
            document.getElementById('d-vehicle-owner').textContent = `Owner: ${meta[4] || ''}`;
            document.getElementById('d-vehicle-type').textContent = 'Type: —';

            document.getElementById('d-customer-name').textContent = `Customer: ${meta[3] || ''}`;
            document.getElementById('d-customer-contact').textContent = 'Contact: —';

            document.getElementById('d-driver-name').textContent = 'Driver: —';
            document.getElementById('d-driver-contact').textContent = 'Driver Contact: —';

            document.getElementById('d-incident-description').textContent = 'No extended description available for this sample record.';

            const evidenceBtn = card.querySelector('.view-evidence');
            const evRaw = evidenceBtn ? evidenceBtn.dataset.evidence || '' : '';
            const evParts = evRaw.split(',').map(s => s.trim()).filter(Boolean);
            const evList = document.getElementById('d-evidence-list');
            if (evList) {
                evList.innerHTML = '';
                if (evParts.length === 0) evList.textContent = 'No evidence uploaded.';
                evParts.forEach(p => {
                    const el = document.createElement('div');
                    el.style.marginBottom = '10px';
                    if (p.match(/\.pdf$/i)) {
                        const a = document.createElement('a');
                        a.href = p; a.target = '_blank';
                        a.textContent = `Open PDF: ${p.split('/').pop()}`;
                        el.appendChild(a);
                    } else {
                        const img = document.createElement('img');
                        img.src = p; img.alt = 'evidence';
                        img.style.maxWidth = '100%'; img.style.borderRadius = '12px'; img.style.border = '1px solid #e6eef8';
                        el.appendChild(img);
                    }
                    evList.appendChild(el);
                });
            }

            // Admin action buttons: show/hide assign replacement depending on current status
            const status = card.dataset.status || '';
            if (btnAssignReplacement) {
                if (status === 'Pending' || status === 'Under Review') {
                    btnAssignReplacement.style.display = '';
                } else {
                    btnAssignReplacement.style.display = 'none';
                }
            }

            openDetailsModal();
        });
    });

    if (detailsClose) detailsClose.addEventListener('click', closeDetailsModal);
    if (btnCloseDetails) btnCloseDetails.addEventListener('click', closeDetailsModal);

    if (btnApprove) btnApprove.addEventListener('click', function () { closeDetailsModal(); showToast('Incident report approved.'); });
    if (btnReject) btnReject.addEventListener('click', function () { closeDetailsModal(); showToast('Incident report rejected.'); });
    if (btnAssignReplacement) btnAssignReplacement.addEventListener('click', function () { closeDetailsModal(); showToast('Replacement vehicle assigned (from details).'); });
});
