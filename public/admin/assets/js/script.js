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
});
