<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';

$viewData = (new CustomerPortalController())->returnCreatePage(
    $_GET,
    $_POST,
    strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))
);
if (isset($viewData['redirect'])) {
    header('Location: ' . customer_url($viewData['redirect']), true, 303);
    exit;
}
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>

<p><a class="text-link" href="<?= htmlspecialchars(customer_url('returns/index.php'), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to returns</a></p>
<div class="alert alert--info"><p>Submitting a request does not complete the return or change the booking. The owner must record the post-rental inspection and complete check-in. Photo controls on this page are previews only.</p></div>

<?php if ($viewData['database_error']): ?>
    <section class="empty-state"><h2>Eligibility unavailable</h2><p>Please try again later.</p></section>
<?php elseif ($viewData['eligible_bookings'] === []): ?>
    <section class="empty-state"><h2>No ongoing booking</h2><p>A real ongoing Customer-owned rental is required.</p></section>
<?php else: ?>
    <form class="card feature-form" method="post" action="<?= htmlspecialchars(customer_url('returns/create.php'), ENT_QUOTES, 'UTF-8') ?>" data-submit-once>
        <?= CustomerCsrf::field() ?>
        <?php if (isset($viewData['errors']['form'])): ?><div class="alert alert--error"><p><?= htmlspecialchars($viewData['errors']['form'], ENT_QUOTES, 'UTF-8') ?></p></div><?php endif; ?>

        <label for="return-booking">Ongoing booking</label>
        <select id="return-booking" name="booking_id" required>
            <option value="">Choose booking</option>
            <?php foreach ($viewData['eligible_bookings'] as $booking): ?>
                <option value="<?= (int) $booking['id'] ?>" <?= (string) $booking['id'] === $viewData['form']['booking_id'] ? 'selected' : '' ?>>#<?= (int) $booking['id'] ?> &mdash; <?= htmlspecialchars($booking['make'] . ' ' . $booking['model'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <label for="return-date">Proposed return date and time</label>
        <input id="return-date" type="datetime-local" name="proposed_return_at" value="<?= htmlspecialchars($viewData['form']['proposed_return_at'], ENT_QUOTES, 'UTF-8') ?>" required>
        <?php if (isset($viewData['errors']['proposed_return_at'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['proposed_return_at'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

        <div class="return-condition-grid">
            <div>
                <label for="return-fuel-level">Final fuel level</label>
                <select id="return-fuel-level" name="final_fuel_level">
                    <option value="full" selected>Full</option>
                    <option value="three_quarters">Three quarters</option>
                    <option value="half">Half</option>
                    <option value="quarter">Quarter</option>
                    <option value="empty">Empty</option>
                </select>
            </div>
            <div>
                <label for="return-note">Notes</label>
                <input id="return-note" name="customer_note" maxlength="500" value="<?= htmlspecialchars($viewData['form']['customer_note'] !== '' ? $viewData['form']['customer_note'] : 'Returned to Colombo pickup point.', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <section class="return-photo-section" aria-label="Vehicle condition photos">
            <div class="return-photo-grid" data-return-photo-grid>
                <?php foreach (['front' => 'Front photo upload placeholder', 'interior' => 'Interior photo upload placeholder', 'dashboard' => 'Dashboard photo upload placeholder'] as $key => $label): ?>
                    <label class="return-photo-upload" data-return-photo-upload>
                        <input class="visually-hidden" type="file" accept="image/jpeg,image/png,image/webp" data-return-photo-input>
                        <span class="return-photo-upload__placeholder" data-return-photo-placeholder><?= customer_icon('camera') ?><strong><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <img class="return-photo-upload__preview" data-return-photo-preview alt="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> preview" hidden>
                        <span class="return-photo-upload__change" data-return-photo-change hidden>Change photo</span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <input type="hidden" name="confirm_return" value="yes">
        <button class="button button--primary" type="submit">Submit return request</button>
    </form>

    <script>
    (function () {
        document.querySelectorAll('[data-return-photo-upload]').forEach(function (upload) {
            var input = upload.querySelector('[data-return-photo-input]');
            var preview = upload.querySelector('[data-return-photo-preview]');
            var placeholder = upload.querySelector('[data-return-photo-placeholder]');
            var change = upload.querySelector('[data-return-photo-change]');
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file || !file.type.match(/^image\/(jpeg|png|webp)$/)) return;
                var reader = new FileReader();
                reader.addEventListener('load', function () {
                    preview.src = reader.result;
                    preview.hidden = false;
                    placeholder.hidden = true;
                    change.hidden = false;
                });
                reader.readAsDataURL(file);
            });
        });
    }());
    </script>
<?php endif; ?>

<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
