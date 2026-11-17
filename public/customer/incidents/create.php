<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';

$viewData = (new CustomerPortalController())->incidentCreatePage(
    $_GET,
    $_POST,
    strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    $_FILES
);
if (isset($viewData['redirect'])) {
    header('Location: ' . customer_url($viewData['redirect']), true, 303);
    exit;
}

$form = $viewData['form'];
$districts = ['Ampara','Anuradhapura','Badulla','Batticaloa','Colombo','Galle','Gampaha','Hambantota','Jaffna','Kalutara','Kandy','Kegalle','Kilinochchi','Kurunegala','Mannar','Matale','Matara','Monaragala','Mullaitivu','Nuwara Eliya','Polonnaruwa','Puttalam','Ratnapura','Trincomalee','Vavuniya'];
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>

<section class="card incident-history" aria-labelledby="incident-history-title">
    <h2 id="incident-history-title">Reported Incident Records</h2>

    <?php if ($viewData['incidents'] === []): ?>
        <p class="incident-history__empty">No incident records have been reported for your bookings yet.</p>
    <?php else: ?>
        <div class="incident-history__list">
            <?php foreach ($viewData['incidents'] as $incident): ?>
                <?php
                $title = ucwords(str_replace('_', ' ', (string) ($incident['incident_type'] ?? 'Incident reported')));
                $status = (string) $incident['status'];
                ?>
                <article class="incident-history__record">
                    <div class="incident-history__record-head">
                        <h3><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="incident-history__status incident-history__status--<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><i aria-hidden="true"></i><?= htmlspecialchars($status === 'resolved' ? 'approved' : $status, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <p>Customer reported <?= htmlspecialchars((string) $incident['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="incident-history__record-meta">
                        <span>District: <?= htmlspecialchars((string) ($incident['district'] ?: 'Not provided'), ENT_QUOTES, 'UTF-8') ?></span>
                        <b aria-hidden="true">&bull;</b>
                        <span>Body: <?= htmlspecialchars((string) ($incident['vehicle_body_category'] ?: 'Not provided'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($incident['google_maps_url'])): ?>
                            <b aria-hidden="true">&bull;</b>
                            <a href="<?= htmlspecialchars((string) $incident['google_maps_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">View Google Maps Location</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($viewData['eligible_bookings'] === []): ?>
    <section class="empty-state"><h2>No eligible booking</h2><p>Incidents can be reported for your ongoing rentals.</p></section>
<?php else: ?>
    <form class="card feature-form incident-form" method="post" enctype="multipart/form-data" data-submit-once>
        <?= CustomerCsrf::field() ?>
        <h2>Incident Details Form</h2>
        <p>Provide details to initiate support, towing, or replacement services.</p>
        <?php foreach ($viewData['errors'] as $error): ?><p class="field-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>
        <div class="incident-form__grid">
            <div><label>Select Booking</label><select name="booking_id" required><option value="">Choose booking</option><?php foreach ($viewData['eligible_bookings'] as $booking): ?><option value="<?= (int) $booking['id'] ?>" <?= $form['booking_id'] == $booking['id'] ? 'selected' : '' ?>>BKG-<?= (int) $booking['id'] ?> &ndash; <?= htmlspecialchars($booking['make'] . ' ' . $booking['model'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
            <div><label>Incident Type</label><select id="incident-type" name="incident_type" required><?php foreach (['breakdown'=>'Breakdown / Mechanical Issue','accident'=>'Accident / Collision','damage'=>'Vehicle Damage','tyre'=>'Tyre Issue','battery'=>'Battery Issue','theft'=>'Theft','vandalism'=>'Vandalism','other'=>'Other'] as $key => $label): ?><option value="<?= $key ?>" <?= $form['incident_type'] === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
            <div><label>Date &amp; Time</label><input type="datetime-local" name="incident_date" value="<?= htmlspecialchars($form['incident_date'], ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div><label>District (Sri Lanka Location)</label><select name="district" required><option value="">Choose district</option><?php foreach ($districts as $district): ?><option <?= $form['district'] === $district ? 'selected' : '' ?>><?= htmlspecialchars($district, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
        </div>
        <label>Google Maps Location Link <span class="form-hint">Optional</span></label><input name="maps_url" placeholder="https://maps.google.com/?q=..." value="<?= htmlspecialchars($form['maps_url'], ENT_QUOTES, 'UTF-8') ?>">
        <label>Describe what happened</label><textarea name="description" minlength="20" maxlength="2000" required placeholder="Please specify vehicle behaviour, warning lights, dashboard alerts, damage, injuries, or other important details."><?= htmlspecialchars($form['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
        <div class="incident-form__grid"><div class="incident-upload"><label>Upload Incident Photos</label><p>Provide clear images of damage, warning lights, leaks, or the surrounding area.</p><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></div><div class="incident-upload"><label>Police Report</label><p id="police-help">Optional for this incident type.</p><input id="police-report" type="file" name="police_report" accept="application/pdf,image/jpeg,image/png"></div></div>
        <button class="button button--primary" type="submit">Submit Incident Report</button>
    </form>
    <script>document.getElementById('incident-type').onchange=function(){var off=this.value==='breakdown',input=document.getElementById('police-report');input.disabled=off;document.getElementById('police-help').textContent=off?'Police report not required for breakdowns.':'Optional: upload a police report if available.'};document.getElementById('incident-type').onchange();</script>
<?php endif; ?>

<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
