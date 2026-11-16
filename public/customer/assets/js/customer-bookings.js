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
        return 'LKR ' + value.toLocaleString('en-LK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    forms.forEach(function (form) {
        var bookingTypes = form.querySelectorAll('input[name="booking_type"]');
        var startInput = form.querySelector('[data-booking-start]');
        var endInput = form.querySelector('[data-booking-end]');
        var driverField = form.querySelector('[data-driver-field]');
        var pickupField = form.querySelector('[data-pickup-location-field]');
        var pickupInput = form.querySelector('[data-pickup-location]');
        var selfDriveCollection = form.querySelector('[data-self-drive-collection]');
        var driverInputs = form.querySelectorAll('[data-driver-select]');
        var daysOutput = form.querySelector('[data-rental-days]');
        var totalOutput = form.querySelector('[data-estimated-total]');
        var dueOutput = form.querySelector('[data-estimated-due]');
        var serviceOutput = form.querySelector('[data-service-summary]');
        var driverOutput = form.querySelector('[data-driver-summary]');
        var locationOutput = form.querySelector('[data-location-summary]');
        var locationLabel = form.querySelector('[data-location-label]');
        var rulesAccepted = form.querySelector('[data-rules-accepted]');
        var bookingTerms = form.querySelector('[data-booking-terms]');
        var agreementMessage = form.querySelector('[data-agreement-incomplete-message]');
        var sendButton = form.querySelector('[data-send-booking]');
        var serviceRequirements = document.querySelectorAll('[data-service-requirements] [data-requirement-service]');

        function selectedType() {
            var selected = form.querySelector('input[name="booking_type"]:checked');
            return selected ? selected.value : 'self_drive';
        }

        function updateRequirementState(key, state, description) {
            var item = document.querySelector('[data-service-requirements] [data-requirement-key="' + key + '"]');
            if (!item) { return; }

            item.classList.remove('booking-requirements__item--complete', 'booking-requirements__item--failed', 'booking-requirements__item--pending');
            item.classList.add('booking-requirements__item--' + state);
            item.querySelectorAll('[data-requirement-icon-state]').forEach(function (icon) {
                icon.hidden = icon.getAttribute('data-requirement-icon-state') !== state;
            });
            var descriptionElement = item.querySelector('[data-requirement-description]');
            if (descriptionElement && description) {
                descriptionElement.textContent = description;
            }
        }

        function updateServiceRequirements() {
            var needsDriver = selectedType() === 'with_driver';
            serviceRequirements.forEach(function (item) {
                var appliesTo = item.getAttribute('data-requirement-service');
                item.hidden = appliesTo !== 'all' && appliesTo !== selectedType();
            });

            if (!needsDriver) { return; }

            var days = calendarDays(startInput ? startInput.value : '', endInput ? endInput.value : '');
            var datesReady = driverField && driverField.getAttribute('data-driver-dates-ready') === '1'
                && form.getAttribute('data-server-selection-ready') === '1';
            var hasEligibleDrivers = driverInputs.length > 0;
            var hasSelectedDriver = form.querySelector('[data-driver-select]:checked') !== null;
            var pickupValue = pickupInput ? pickupInput.value.replace(/\s+/g, ' ').trim() : '';

            if (days < 28 || !datesReady) {
                updateRequirementState('driver-availability', 'pending', 'Choose valid dates, then check availability to view eligible Drivers.');
                updateRequirementState('driver-selection', 'pending', 'Select valid dates before Drivers can be checked.');
            } else if (!hasEligibleDrivers) {
                updateRequirementState('driver-availability', 'failed', 'No verified drivers linked to this vehicle are available for your selected dates.');
                updateRequirementState('driver-selection', 'pending', 'Change dates or select Self Drive if this vehicle supports it.');
            } else {
                updateRequirementState('driver-availability', 'complete', 'Verified Drivers are available for the complete rental period.');
                updateRequirementState(
                    'driver-selection',
                    hasSelectedDriver ? 'complete' : 'pending',
                    hasSelectedDriver ? 'Selected Driver is available for the complete rental period.' : 'Select an eligible Driver.'
                );
            }

            updateRequirementState(
                'pickup-location',
                pickupValue.length >= 5 && pickupValue.length <= 255 ? 'complete' : 'pending',
                pickupValue.length >= 5 && pickupValue.length <= 255
                    ? 'A pickup address is ready for the selected Driver.'
                    : 'Enter a clear pickup address for the selected Driver.'
            );
        }

        function updateDriverField() {
            var needsDriver = selectedType() === 'with_driver';
            if (driverField) {
                driverField.hidden = !needsDriver;
            }
            if (pickupField) {
                pickupField.hidden = !needsDriver;
            }
            if (selfDriveCollection) {
                selfDriveCollection.hidden = needsDriver;
            }
            driverInputs.forEach(function (input) {
                input.required = needsDriver;
                if (!needsDriver) { input.checked = false; }
            });
            if (pickupInput) {
                pickupInput.required = needsDriver;
                if (!needsDriver) { pickupInput.value = ''; }
            }
            if (serviceOutput) { serviceOutput.textContent = needsDriver ? 'With Driver' : 'Self Drive'; }
            updateDriverSummary();
            updateLocationSummary();
            updateServiceRequirements();
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
            if (dueOutput) {
                dueOutput.textContent = days > 0 && Number.isFinite(rate) && rate > 0
                    ? formatRupees(rate * days)
                    : 'Calculated from database price';
            }
            updateSendButton();
        }

        function updateDriverSummary() {
            if (!driverOutput) { return; }
            if (selectedType() !== 'with_driver') { driverOutput.textContent = 'Not required'; return; }
            var selectedDriver = form.querySelector('[data-driver-select]:checked');
            if (!selectedDriver) { driverOutput.textContent = 'Select an eligible Driver'; return; }
            var card = selectedDriver.closest('.booking-driver-card');
            var name = card ? card.querySelector('strong') : null;
            driverOutput.textContent = name ? name.textContent : 'Selected Driver';
        }

        function updateLocationSummary() {
            var needsDriver = selectedType() === 'with_driver';
            if (locationLabel) {
                locationLabel.textContent = needsDriver ? 'Pickup Location' : 'Collection Location';
            }
            if (!locationOutput) { return; }
            if (needsDriver) {
                var pickup = pickupInput ? pickupInput.value.replace(/\s+/g, ' ').trim() : '';
                locationOutput.textContent = pickup || 'Enter pickup location';
                return;
            }
            var collectionValue = selfDriveCollection ? selfDriveCollection.querySelector('[data-collection-location]') : null;
            locationOutput.textContent = collectionValue ? collectionValue.textContent : 'Not specified';
        }

        function updateSendButton() {
            if (!sendButton) { return; }
            var days = calendarDays(startInput ? startInput.value : '', endInput ? endInput.value : '');
            var needsDriver = selectedType() === 'with_driver';
            var hasDriver = form.querySelector('[data-driver-select]:checked') !== null;
            var hasRules = !rulesAccepted || rulesAccepted.checked;
            var pickupValue = pickupInput ? pickupInput.value.replace(/\s+/g, ' ').trim() : '';
            var hasPickup = !needsDriver || (pickupValue.length >= 5 && pickupValue.length <= 255);
            var allAgreementsAccepted = !bookingTerms || bookingTerms.checked;
            var serverReady = form.getAttribute('data-server-can-send') === '1'
                || (form.getAttribute('data-server-selection-ready') === '1' && (!needsDriver || hasDriver));
            var ready = serverReady && days >= 28 && hasRules && hasPickup && allAgreementsAccepted && (!needsDriver || hasDriver);
            sendButton.disabled = !ready;
            sendButton.setAttribute('aria-disabled', ready ? 'false' : 'true');
            if (agreementMessage) {
                agreementMessage.hidden = ready;
            }
        }

        bookingTypes.forEach(function (input) {
            input.addEventListener('change', function () {
                updateDriverField();
                updateEstimate();
            });
        });
        function datesChanged() {
            driverInputs.forEach(function (input) { input.checked = false; });
            form.setAttribute('data-server-can-send', '0');
            form.setAttribute('data-server-selection-ready', '0');
            updateDriverSummary();
            updateEstimate();
            updateServiceRequirements();
        }
        if (startInput) { startInput.addEventListener('change', datesChanged); }
        if (endInput) { endInput.addEventListener('change', datesChanged); }
        driverInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var selected = form.querySelector('[data-driver-select]:checked') !== null;
                driverInputs.forEach(function (choice) {
                    var choiceLabel = choice.closest('.booking-driver-card');
                    var choiceText = choiceLabel ? choiceLabel.querySelector('[data-driver-choice]') : null;
                    if (choiceText) {
                        choiceText.textContent = choice.checked ? 'Selected Driver' : (selected ? 'Change Driver' : 'Select Driver');
                    }
                });
                updateDriverSummary();
                updateServiceRequirements();
                updateSendButton();
            });
        });
        if (pickupInput) { pickupInput.addEventListener('input', function () { updateLocationSummary(); updateServiceRequirements(); updateSendButton(); }); }
        if (rulesAccepted) { rulesAccepted.addEventListener('change', updateSendButton); }
        if (bookingTerms) { bookingTerms.addEventListener('change', updateSendButton); }

        updateDriverField();
        updateEstimate();
        updateServiceRequirements();
    });
}());

(function () {
    'use strict';

    document.querySelectorAll('[data-booking-agreement-form]').forEach(function (form) {
        if (form.hasAttribute('data-booking-form')) {
            return;
        }
        var bookingTerms = form.querySelector('[data-booking-terms]');
        var createButton = form.querySelector('[data-create-booking-request]');
        var incompleteMessage = form.querySelector('[data-agreement-incomplete-message]');

        function updateCreateState() {
            var allAccepted = bookingTerms !== null && bookingTerms.checked;
            var serverReady = form.getAttribute('data-server-can-create') === '1';
            var enabled = allAccepted && serverReady;

            if (createButton) {
                createButton.disabled = !enabled;
                createButton.setAttribute('aria-disabled', enabled ? 'false' : 'true');
            }
            if (incompleteMessage) {
                incompleteMessage.hidden = enabled;
            }
        }

        if (bookingTerms) { bookingTerms.addEventListener('change', updateCreateState); }
        updateCreateState();
    });
}());
