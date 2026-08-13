<?php
$formTitle = $formMode === 'edit' ? 'Update rental details' : 'Rental details';
$submitLabel = $formMode === 'edit' ? 'Save booking changes' : 'Create booking';
$fieldError = static fn (string $field): string => (string) ($viewData['field_errors'][$field] ?? '');
$selfDrivePrice = (float) ($vehicle['price_per_day'] ?? 0);
$withDriverPrice = ($vehicle['price_with_driver_per_day'] ?? null) !== null
    ? (float) $vehicle['price_with_driver_per_day']
    : null;
?>
<form class="card booking-form" method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>"
      data-booking-form
      data-self-drive-rate="<?= htmlspecialchars((string) $selfDrivePrice, ENT_QUOTES, 'UTF-8') ?>"
      data-with-driver-rate="<?= $withDriverPrice !== null ? htmlspecialchars((string) $withDriverPrice, ENT_QUOTES, 'UTF-8') : '' ?>">
    <?= CustomerCsrf::field() ?>
    <div class="card__header">
        <h2><?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></h2>
        <span class="card__header-icon" aria-hidden="true"><?= customer_icon('calendar') ?></span>
    </div>

    <?php if ($fieldError('form') !== ''): ?>
        <div class="alert alert--error" role="alert"><?= customer_icon('alert') ?><p><?= htmlspecialchars($fieldError('form'), ENT_QUOTES, 'UTF-8') ?></p></div>
    <?php endif; ?>

    <?php if ($viewData['eligibility_message'] !== ''): ?>
        <div class="alert alert--warning" role="alert">
            <?= customer_icon('shield') ?>
            <p><?= htmlspecialchars($viewData['eligibility_message'], ENT_QUOTES, 'UTF-8') ?> <a href="<?= htmlspecialchars(customer_url('verification/index.php'), ENT_QUOTES, 'UTF-8') ?>">View verification</a>.</p>
        </div>
    <?php endif; ?>

    <fieldset <?= $formDisabled ? 'disabled' : '' ?>>
        <legend>Booking service</legend>
        <div class="booking-type-options">
            <label class="booking-type-option">
                <input type="radio" name="booking_type" value="self_drive" <?= $viewData['form']['booking_type'] === 'self_drive' ? 'checked' : '' ?>>
                <span><strong>Self-drive</strong><small>Rs. <?= number_format($selfDrivePrice, 2) ?> per day</small></span>
            </label>
            <label class="booking-type-option<?= $withDriverPrice === null ? ' is-disabled' : '' ?>">
                <input type="radio" name="booking_type" value="with_driver" <?= $viewData['form']['booking_type'] === 'with_driver' ? 'checked' : '' ?> <?= $withDriverPrice === null ? 'disabled' : '' ?>>
                <span><strong>With driver</strong><small><?= $withDriverPrice !== null ? 'Rs. ' . number_format($withDriverPrice, 2) . ' per day' : 'Not offered for this vehicle' ?></small></span>
            </label>
        </div>
        <?php if ($fieldError('booking_type') !== ''): ?><p class="form-error"><?= htmlspecialchars($fieldError('booking_type'), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

        <div class="booking-form__grid">
            <div class="form-group">
                <label for="booking-start-date">Start date</label>
                <input id="booking-start-date" name="start_date" type="date" min="<?= htmlspecialchars($viewData['minimum_date'], ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($viewData['form']['start_date'], ENT_QUOTES, 'UTF-8') ?>" required data-booking-start>
                <?php if ($fieldError('start_date') !== ''): ?><p class="form-error"><?= htmlspecialchars($fieldError('start_date'), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="booking-end-date">End date</label>
                <input id="booking-end-date" name="end_date" type="date" min="<?= htmlspecialchars($viewData['minimum_date'], ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($viewData['form']['end_date'], ENT_QUOTES, 'UTF-8') ?>" required data-booking-end>
                <?php if ($fieldError('end_date') !== ''): ?><p class="form-error"><?= htmlspecialchars($fieldError('end_date'), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-group" data-driver-field>
            <label for="booking-driver">Eligible Driver</label>
            <select id="booking-driver" name="driver_id" data-driver-select>
                <option value="">Select a Driver</option>
                <?php foreach ($viewData['drivers'] as $driver): ?>
                    <option value="<?= (int) $driver['id'] ?>" <?= (string) $viewData['form']['driver_id'] === (string) $driver['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $driver['name'] . ' — rating ' . number_format((float) $driver['rating_avg'], 2), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Drivers must be active, available, linked to this Owner, document-approved, off leave, and free for the selected period.</small>
            <?php if ($viewData['drivers'] === []): ?><p class="form-hint">No eligible Drivers are currently available.</p><?php endif; ?>
            <?php if ($fieldError('driver_id') !== ''): ?><p class="form-error"><?= htmlspecialchars($fieldError('driver_id'), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="booking-delivery-address">Delivery address <span>(optional)</span></label>
            <textarea id="booking-delivery-address" name="delivery_address" maxlength="255" placeholder="Enter a delivery or pickup instruction when needed"><?= htmlspecialchars($viewData['form']['delivery_address'], ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if ($fieldError('delivery_address') !== ''): ?><p class="form-error"><?= htmlspecialchars($fieldError('delivery_address'), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>

        <section class="booking-price-summary" aria-live="polite">
            <div><span>Rental duration</span><strong data-rental-days><?= $viewData['estimate'] !== null ? (int) $viewData['estimate']['rental_days'] . ' days' : 'Choose valid dates' ?></strong></div>
            <div><span>Estimated total</span><strong data-estimated-total><?= $viewData['estimate'] !== null ? 'Rs. ' . number_format((float) $viewData['estimate']['estimated_total'], 2) : 'Calculated from database price' ?></strong></div>
            <p>The server rechecks availability and calculates the authoritative total when you submit.</p>
        </section>

        <button class="button button--primary" type="submit"><?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?></button>
    </fieldset>
</form>
