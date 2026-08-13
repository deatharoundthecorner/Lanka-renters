(function () {
    'use strict';

    var forms = document.querySelectorAll('[data-booking-form]');

    function calendarDays(startValue, endValue) {
        if (!startValue || !endValue) {
            return 0;
        }

        var startParts = startValue.split('-').map(Number);
        var endParts = endValue.split('-').map(Number);
        if (startParts.length !== 3 || endParts.length !== 3) {
            return 0;
        }

        var start = Date.UTC(startParts[0], startParts[1] - 1, startParts[2]);
        var end = Date.UTC(endParts[0], endParts[1] - 1, endParts[2]);
        return Math.max(0, Math.round((end - start) / 86400000));
    }

    function formatRupees(value) {
        return 'Rs. ' + value.toLocaleString('en-LK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    forms.forEach(function (form) {
        var bookingTypes = form.querySelectorAll('input[name="booking_type"]');
        var startInput = form.querySelector('[data-booking-start]');
        var endInput = form.querySelector('[data-booking-end]');
        var driverField = form.querySelector('[data-driver-field]');
        var driverSelect = form.querySelector('[data-driver-select]');
        var daysOutput = form.querySelector('[data-rental-days]');
        var totalOutput = form.querySelector('[data-estimated-total]');

        function selectedType() {
            var selected = form.querySelector('input[name="booking_type"]:checked');
            return selected ? selected.value : 'self_drive';
        }

        function updateDriverField() {
            var needsDriver = selectedType() === 'with_driver';
            if (driverField) {
                driverField.hidden = !needsDriver;
            }
            if (driverSelect) {
                driverSelect.required = needsDriver;
                if (!needsDriver) {
                    driverSelect.value = '';
                }
            }
        }

        function updateEstimate() {
            var days = calendarDays(startInput ? startInput.value : '', endInput ? endInput.value : '');
            var rateValue = selectedType() === 'with_driver'
                ? form.getAttribute('data-with-driver-rate')
                : form.getAttribute('data-self-drive-rate');
            var rate = Number(rateValue);

            if (daysOutput) {
                daysOutput.textContent = days > 0 ? days + ' days' : 'Choose valid dates';
            }
            if (totalOutput) {
                totalOutput.textContent = days > 0 && Number.isFinite(rate) && rate > 0
                    ? formatRupees(rate * days)
                    : 'Calculated from database price';
            }
        }

        bookingTypes.forEach(function (input) {
            input.addEventListener('change', function () {
                updateDriverField();
                updateEstimate();
            });
        });
        if (startInput) {
            startInput.addEventListener('change', updateEstimate);
        }
        if (endInput) {
            endInput.addEventListener('change', updateEstimate);
        }

        updateDriverField();
        updateEstimate();
    });
}());
