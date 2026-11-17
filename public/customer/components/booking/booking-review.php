<?php
$reviewVehicleName = trim((string) ($vehicle['make'] ?? '') . ' ' . (string) ($vehicle['model'] ?? ''));
$reviewVehicleName = $reviewVehicleName !== '' ? $reviewVehicleName : 'Vehicle';
$reviewDriver = is_array($viewData['selected_driver'] ?? null) ? $viewData['selected_driver'] : null;
$reviewAgreements = is_array($viewData['agreements'] ?? null) ? $viewData['agreements'] : [];
$agreementError = (string) ($viewData['field_errors']['booking_agreements'] ?? '');
$estimate = is_array($viewData['estimate'] ?? null) ? $viewData['estimate'] : null;
$mileageCharge = ($vehicle['mileage_is_unlimited'] ?? false)
    ? 'Unlimited mileage'
    : (($vehicle['additional_mileage_charge_per_km'] ?? null) !== null
        ? 'LKR ' . number_format((float) $vehicle['additional_mileage_charge_per_km'], 2) . ' / km over limit'
        : 'Not specified');
?>
<form class="card booking-review" method="post" action="<?= htmlspecialchars(customer_url('bookings/create.php?vehicle_id=' . (int) $vehicle['id']), ENT_QUOTES, 'UTF-8') ?>" data-booking-agreement-form data-server-can-create="<?= !empty($viewData['can_handoff']) ? '1' : '0' ?>" data-submit-once>
    <?= CustomerCsrf::field() ?>
    <input type="hidden" name="booking_type" value="<?= htmlspecialchars((string) $viewData['form']['booking_type'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="start_date" value="<?= htmlspecialchars((string) $viewData['form']['start_date'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="end_date" value="<?= htmlspecialchars((string) $viewData['form']['end_date'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="driver_id" value="<?= htmlspecialchars((string) $viewData['form']['driver_id'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="delivery_address" value="<?= htmlspecialchars((string) $viewData['form']['delivery_address'], ENT_QUOTES, 'UTF-8') ?>">

    <div class="card__header"><div><p class="eyebrow">Booking management</p><h2>Review booking request</h2></div><span class="card__header-icon" aria-hidden="true"><?= customer_icon('calendar') ?></span></div>
    <?php if (($viewData['field_errors']['form'] ?? '') !== ''): ?><div class="alert alert--error" role="alert"><?= customer_icon('alert') ?><p><?= htmlspecialchars((string) $viewData['field_errors']['form'], ENT_QUOTES, 'UTF-8') ?></p></div><?php endif; ?>
    <?php if (($viewData['eligibility_message'] ?? '') !== ''): ?><div class="alert alert--warning" role="alert"><?= customer_icon('shield') ?><p><?= htmlspecialchars((string) $viewData['eligibility_message'], ENT_QUOTES, 'UTF-8') ?> <a href="<?= htmlspecialchars(customer_url('verification/index.php'), ENT_QUOTES, 'UTF-8') ?>">Complete verification</a>.</p></div><?php endif; ?>

    <section class="booking-review__summary" aria-labelledby="booking-review-summary-title">
        <div class="booking-review__vehicle">
            <?php if (is_string($vehicle['image_url'] ?? null) && trim($vehicle['image_url']) !== ''): ?><img src="<?= htmlspecialchars(customer_url('../' . ltrim((string) $vehicle['image_url'], '/')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($reviewVehicleName, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
            <div><p class="eyebrow">Selected vehicle</p><h3 id="booking-review-summary-title"><?= htmlspecialchars($reviewVehicleName, ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars((string) ($vehicle['location'] ?? 'Location provided during booking'), ENT_QUOTES, 'UTF-8') ?></p></div>
        </div>
        <dl class="booking-review__details">
            <div><dt>Pickup date</dt><dd><?= htmlspecialchars($viewData['form']['start_date'] !== '' ? date('d M Y', strtotime((string) $viewData['form']['start_date'])) : 'Not selected', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Return date</dt><dd><?= htmlspecialchars($viewData['form']['end_date'] !== '' ? date('d M Y', strtotime((string) $viewData['form']['end_date'])) : 'Not selected', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Rental duration</dt><dd><?= $estimate !== null ? (int) $estimate['rental_days'] . ' days' : 'Not valid' ?></dd></div>
            <div><dt>Service type</dt><dd><?= htmlspecialchars($viewData['form']['booking_type'] === 'with_driver' ? 'With Driver' : 'Self Drive', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Selected Driver</dt><dd><?= htmlspecialchars($viewData['form']['booking_type'] === 'with_driver' ? (is_array($reviewDriver) ? (string) $reviewDriver['name'] : 'No eligible Driver selected') : 'Not required', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Monthly rental</dt><dd><?= ($vehicle['monthly_rental_price'] ?? null) !== null ? 'LKR ' . number_format((float) $vehicle['monthly_rental_price'], 2) : 'Not specified' ?></dd></div>
            <div><dt>Refundable key money</dt><dd><?= ($vehicle['refundable_key_money'] ?? null) !== null ? 'LKR ' . number_format((float) $vehicle['refundable_key_money'], 2) : 'Not specified' ?></dd></div>
            <div><dt>Estimated rental amount</dt><dd><?= $estimate !== null ? 'LKR ' . number_format((float) $estimate['estimated_total'], 2) : 'Calculated after validation' ?></dd></div>
            <div class="booking-review__wide"><dt>Other existing charges</dt><dd><?= htmlspecialchars($mileageCharge, ENT_QUOTES, 'UTF-8') ?></dd></div>
        </dl>
    </section>

    <section class="booking-agreement" aria-labelledby="booking-agreement-title">
        <h2 id="booking-agreement-title">Booking Agreement</h2>
        <p>Please read and accept all conditions before creating your booking request.</p>
        <ol><li>The platform fee is non-refundable.</li><li>A replacement vehicle is not guaranteed.</li><li>The refundable security payment will be released after the rental is completed.</li><li>The Customer must inspect the vehicle and confirm the inspection before vehicle pickup.</li></ol>
        <label class="booking-agreement__acceptance"><input type="checkbox" name="booking_terms_accepted" value="1" <?= !empty($reviewAgreements['accepted']) ? 'checked' : '' ?> data-booking-terms> <span>I have read, understood, and agree to all the booking conditions above.</span></label>
        <p class="form-hint<?= $agreementError !== '' ? ' form-error' : '' ?>" data-agreement-incomplete-message><?= htmlspecialchars($agreementError !== '' ? $agreementError : 'You must accept the Booking Agreement before creating your request.', ENT_QUOTES, 'UTF-8') ?></p>
    </section>

    <button class="button button--primary" type="submit" name="booking_action" value="create" data-create-booking-request disabled aria-disabled="true">Create Booking Request</button>
</form>
