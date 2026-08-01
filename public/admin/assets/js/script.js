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
});
