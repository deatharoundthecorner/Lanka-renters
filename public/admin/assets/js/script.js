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
});
