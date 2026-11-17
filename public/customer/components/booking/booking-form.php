<?php
$isHandoff = $formMode === 'handoff';
$formTitle = $formMode === 'edit'
    ? 'Update rental details'
    : ($isHandoff ? 'Choose rental details' : 'Booking details');
$submitLabel = $formMode === 'edit'
    ? 'Save booking changes'
    : ($isHandoff ? 'Continue to Booking Request' : 'Create Booking Request');
$fieldError = static fn (string $field): string => (string) ($viewData['field_errors'][$field] ?? '');
$agreements = is_array($viewData['agreements'] ?? null) ? $viewData['agreements'] : [];
$agreementError = $fieldError('booking_agreements');
$selfDrivePrice = (float) ($vehicle['price_per_day'] ?? 0);
$withDriverPrice = ($vehicle['price_with_driver_per_day'] ?? null) !== null ? (float) $vehicle['price_with_driver_per_day'] : null;
$canSend = $formMode === 'edit' || (bool) ($viewData['can_handoff'] ?? false);
$agreementsAccepted = !empty($agreements['accepted']);
$canSubmit = $canSend && ($formMode !== 'create' || $agreementsAccepted);
$withDriverSelected = $viewData['form']['booking_type'] === 'with_driver';
$vehicleCollectionLocation = trim((string) ($vehicle['location'] ?? ''));
$summaryLocation = $withDriverSelected
    ? (trim((string) $viewData['form']['pickup_location']) !== '' ? trim((string) $viewData['form']['pickup_location']) : 'Enter pickup location')
    : ($vehicleCollectionLocation !== '' ? $vehicleCollectionLocation : 'Not specified');
?>
<form class="card booking-form" method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" data-booking-form data-self-drive-rate="<?= htmlspecialchars((string) $selfDrivePrice, ENT_QUOTES, 'UTF-8') ?>" data-with-driver-rate="<?= $withDriverPrice !== null ? htmlspecialchars((string) $withDriverPrice, ENT_QUOTES, 'UTF-8') : '' ?>" data-server-can-send="<?= $canSend ? '1' : '0' ?>" data-server-selection-ready="<?= !empty($viewData['can_complete_selection']) ? '1' : '0' ?>">
    <?= CustomerCsrf::field() ?>
    <div class="card__header"><div><p class="eyebrow">Booking request</p><h2><?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></h2></div><span class="card__header-icon" aria-hidden="true"><?= customer_icon('calendar') ?></span></div>
    <?php if ($fieldError('form') !== ''): ?><div class="alert alert--error" role="alert"><?= customer_icon('alert') ?><p><?= htmlspecialchars($fieldError('form'), ENT_QUOTES, 'UTF-8') ?></p></div><?php endif; ?>
    <?php if ($viewData['eligibility_message'] !== ''): ?><div class="alert alert--warning" role="alert"><?= customer_icon('shield') ?><p><?= htmlspecialchars($viewData['eligibility_message'], ENT_QUOTES, 'UTF-8') ?> <a href="<?= htmlspecialchars(customer_url('verification/index.php'), ENT_QUOTES, 'UTF-8') ?>">Complete verification</a>.</p></div><?php endif; ?>

    <fieldset>
        <legend id="service-type">Choose service type</legend>
        <div class="booking-type-options">
            <label class="booking-type-option"><input type="radio" name="booking_type" value="self_drive" <?= $viewData['form']['booking_type'] === 'self_drive' ? 'checked' : '' ?>><span><strong>Self Drive</strong><small>LKR <?= number_format($selfDrivePrice, 2) ?> per day</small></span></label>
            <?php if ($withDriverPrice !== null): ?><label class="booking-type-option"><input type="radio" name="booking_type" value="with_driver" <?= $viewData['form']['booking_type'] === 'with_driver' ? 'checked' : '' ?>><span><strong>With Driver</strong><small>LKR <?= number_format($withDriverPrice, 2) ?> per day</small></span></label><?php endif; ?>
        </div>
        <?php if ($fieldError('booking_type') !== ''): ?><p class="form-error"><?= htmlspecialchars($fieldError('booking_type'), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    </fieldset>

    <div id="booking-dates" class="booking-form__grid">
        <div class="form-group"><label for="booking-start-date">Pickup date</label><input id="booking-start-date" name="start_date" type="date" min="<?= htmlspecialchars($viewData['minimum_date'], ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($viewData['form']['start_date'], ENT_QUOTES, 'UTF-8') ?>" required data-booking-start><?php if ($fieldError('start_date') !== ''): ?><p class="form-error"><?= htmlspecialchars($fieldError('start_date'), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?></div>
        <div class="form-group"><label for="booking-end-date">Return date</label><input id="booking-end-date" name="end_date" type="date" min="<?= htmlspecialchars($viewData['minimum_date'], ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($viewData['form']['end_date'], ENT_QUOTES, 'UTF-8') ?>" required data-booking-end><?php if ($fieldError('end_date') !== ''): ?><p class="form-error"><?= htmlspecialchars($fieldError('end_date'), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?></div>
    </div>
    <button class="button button--secondary button--small" type="submit" name="booking_action" value="preview">Check availability and Drivers</button>

    <section id="driver-selection" class="booking-driver-selection" data-driver-field data-driver-dates-ready="<?= !empty($viewData['driver_dates_ready']) ? '1' : '0' ?>" <?= $withDriverSelected ? '' : 'hidden' ?>>
        <div class="card__header"><div><p class="eyebrow">With Driver only</p><h2>Select a driver</h2></div><span class="card__header-icon" aria-hidden="true"><?= customer_icon('user') ?></span></div>
        <?php if (!($viewData['driver_dates_ready'] ?? false)): ?>
            <p class="form-hint">Choose valid pickup and return dates, then check availability to view eligible Drivers.</p>
        <?php elseif ($viewData['drivers'] === []): ?>
            <p class="form-hint">No verified drivers linked to this vehicle are available for your selected dates. Change rental dates or select Self Drive if this vehicle supports it.</p>
        <?php else: ?>
            <div class="booking-driver-grid">
                <?php foreach ($viewData['drivers'] as $driver): ?>
                    <?php $driverIsSelected = (string) $viewData['form']['driver_id'] === (string) $driver['id']; ?>
                    <label class="booking-driver-card"><input type="radio" name="driver_id" value="<?= (int) $driver['id'] ?>" <?= $driverIsSelected ? 'checked' : '' ?> data-driver-select><span><strong><?= htmlspecialchars((string) $driver['name'], ENT_QUOTES, 'UTF-8') ?></strong><small>Admin verified &middot; Available &middot; Rating <?= number_format((float) $driver['rating_avg'], 2) ?></small><em class="booking-driver-card__action" data-driver-choice><?= $driverIsSelected ? 'Selected Driver' : 'Select Driver' ?></em></span></label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($fieldError('driver_id') !== ''): ?><p class="form-error"><?= htmlspecialchars($fieldError('driver_id'), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    </section>

    <section class="booking-collection-location" data-self-drive-collection <?= $withDriverSelected ? 'hidden' : '' ?> aria-live="polite">
        <h2>Collection Location</h2>
        <p data-collection-location><?= htmlspecialchars($vehicleCollectionLocation !== '' ? $vehicleCollectionLocation : 'Not specified', ENT_QUOTES, 'UTF-8') ?></p>
        <p>You must collect the vehicle from the listed vehicle location.</p>
    </section>

    <div id="pickup-location" class="form-group" data-pickup-location-field <?= $withDriverSelected ? '' : 'hidden' ?>><label for="booking-pickup-location">Pickup Location</label><textarea id="booking-pickup-location" name="pickup_location" maxlength="255" minlength="5" placeholder="Enter the address where the driver should pick you up" <?= $withDriverSelected ? 'required' : '' ?> data-pickup-location><?= htmlspecialchars($viewData['form']['pickup_location'], ENT_QUOTES, 'UTF-8') ?></textarea><small>Provide a clear and complete pickup address for the selected driver.</small><?php if ($fieldError('pickup_location') !== ''): ?><p class="form-error"><?= htmlspecialchars($fieldError('pickup_location'), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?></div>

    <section class="booking-price-summary" aria-live="polite"><h2>Booking summary</h2><div><span>Vehicle</span><strong><?= htmlspecialchars(trim((string) $vehicle['make'] . ' ' . (string) $vehicle['model']), ENT_QUOTES, 'UTF-8') ?></strong></div><div><span data-location-label><?= $withDriverSelected ? 'Pickup Location' : 'Collection Location' ?></span><strong data-location-summary><?= htmlspecialchars($summaryLocation, ENT_QUOTES, 'UTF-8') ?></strong></div><div><span>Rental duration</span><strong data-rental-days><?= $viewData['estimate'] !== null ? (int) $viewData['estimate']['rental_days'] . ' days' : 'Choose valid dates' ?></strong></div><div><span>Selected service</span><strong data-service-summary><?= $withDriverSelected ? 'With Driver' : 'Self Drive' ?></strong></div><div data-driver-summary-row><span>Selected Driver</span><strong data-driver-summary><?= $withDriverSelected ? 'Select an eligible Driver' : 'Not required' ?></strong></div><div><span>Monthly rental</span><strong><?= ($vehicle['monthly_rental_price'] ?? null) !== null ? 'LKR ' . number_format((float) $vehicle['monthly_rental_price'], 2) : 'Not specified' ?></strong></div><div><span>Refundable key money</span><strong><?= ($vehicle['refundable_key_money'] ?? null) !== null ? 'LKR ' . number_format((float) $vehicle['refundable_key_money'], 2) : 'Not specified' ?></strong></div><div><span>Estimated rental amount</span><strong data-estimated-total><?= $viewData['estimate'] !== null ? 'LKR ' . number_format((float) $viewData['estimate']['estimated_total'], 2) : 'Calculated from database price' ?></strong></div><div><span>Estimated amount due</span><strong data-estimated-due><?= $viewData['estimate'] !== null ? 'LKR ' . number_format((float) $viewData['estimate']['estimated_total'], 2) : 'Calculated from database price' ?></strong></div><p>Pricing uses the existing daily-rate calculation on the server. Refundable key money is shown separately and is not currently recorded in a booking payment total.</p></section>
    <?php require __DIR__ . '/pre-booking-checklist.php'; ?>
    <?php if ($formMode === 'create'): ?>
        <section class="booking-agreement" aria-labelledby="booking-agreement-title">
            <h2 id="booking-agreement-title">Booking Agreement</h2>
            <ol><li>The platform fee is non-refundable.</li><li>A replacement vehicle is not guaranteed.</li><li>The refundable security payment will be released after the rental is completed.</li><li>The Customer must inspect the vehicle and confirm the inspection before vehicle pickup.</li></ol>
            <label class="booking-agreement__acceptance"><input type="checkbox" name="booking_terms_accepted" value="1" <?= !empty($agreements['accepted']) ? 'checked' : '' ?> data-booking-terms> <span>I have read, understood, and agree to all the booking conditions above.</span></label>
            <p class="form-hint<?= $agreementError !== '' ? ' form-error' : '' ?>" data-agreement-incomplete-message><?= htmlspecialchars($agreementError !== '' ? $agreementError : 'Accept the Booking Agreement to continue.', ENT_QUOTES, 'UTF-8') ?></p>
        </section>
    <?php endif; ?>
    <button class="button button--primary" type="submit" name="booking_action" value="<?= $isHandoff ? 'handoff' : 'create' ?>" data-send-booking <?= $formMode === 'create' ? 'data-create-booking-request' : '' ?> <?= $canSubmit ? '' : 'disabled aria-disabled="true"' ?>><?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?></button>
</form>
