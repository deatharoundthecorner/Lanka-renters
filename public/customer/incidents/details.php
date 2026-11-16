<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';

$viewData = (new CustomerPortalController())->incidentDetailsPage(
    $_GET,
    $_POST,
    strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))
);
if (isset($viewData['redirect'])) {
    header('Location: ' . customer_url($viewData['redirect']), true, 303);
    exit;
}
$incident = $viewData['incident'];
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>

<?php if (!is_array($incident)): ?>
    <section class="empty-state"><h2>Incident not found</h2><p>This incident does not exist or does not belong to your account.</p></section>
<?php else: ?>
    <div class="incident-dashboard">
        <section class="card">
            <div class="card__header"><div><p class="eyebrow">Incident #<?= (int) $incident['id'] ?></p><h2>Incident report</h2></div><span class="status-badge <?= $incident['status'] === 'resolved' ? 'status-badge--success' : 'status-badge--warning' ?>"><?= htmlspecialchars($incident['status'] === 'resolved' ? 'Approved' : ucwords(str_replace('_', ' ', $incident['status'])), ENT_QUOTES, 'UTF-8') ?></span></div>
            <dl class="feature-detail-list"><div><dt>Booking</dt><dd>BKG-<?= (int) $incident['booking_id'] ?> &ndash; <?= htmlspecialchars($incident['make'] . ' ' . $incident['model'], ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>Booking status</dt><dd><?= htmlspecialchars(ucwords(str_replace('_', ' ', $incident['booking_status'])), ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>Type</dt><dd><?= htmlspecialchars(ucwords(str_replace('_', ' ', $incident['incident_type'])), ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>Incident date</dt><dd><?= htmlspecialchars(date('d M Y, h:i A', strtotime($incident['incident_date'])), ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>District</dt><dd><?= htmlspecialchars($incident['district'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></dd></div><div class="feature-detail-list__wide"><dt>What happened?</dt><dd><?= nl2br(htmlspecialchars($incident['description'], ENT_QUOTES, 'UTF-8')) ?></dd></div></dl>
            <?php foreach ($viewData['errors'] as $error): ?><p class="field-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>
            <?php if (in_array($incident['status'], ['reported', 'investigating'], true)): ?>
                <?php if (in_array($incident['resolution_preference'], ['refund', 'replacement'], true)): ?>
                    <p><strong>Recorded resolution preference:</strong> <?= $incident['resolution_preference'] === 'refund' ? 'Request Refund' : 'Request Replacement' ?></p>
                <?php endif; ?>
                <form class="feature-form feature-form--compact" method="post" action="<?= htmlspecialchars(customer_url('incidents/details.php?id=' . (int) $incident['id']), ENT_QUOTES, 'UTF-8') ?>" data-submit-once>
                    <?= CustomerCsrf::field() ?>
                    <input type="hidden" name="action" value="set_resolution_preference">
                    <fieldset>
                        <legend>Resolution preference</legend>
                        <label><input type="radio" name="resolution_preference" value="refund" required <?= $incident['resolution_preference'] === 'refund' ? 'checked' : '' ?>> Request Refund</label>
                        <label><input type="radio" name="resolution_preference" value="replacement" required <?= $incident['resolution_preference'] === 'replacement' ? 'checked' : '' ?>> Request Replacement</label>
                    </fieldset>
                    <p class="form-hint">This records your preference for review only. It does not automatically issue a refund or create a replacement request.</p>
                    <button class="button button--primary" type="submit">Save Preference</button>
                </form>
            <?php elseif (in_array($incident['resolution_preference'], ['refund', 'replacement'], true)): ?>
                <p><strong>Recorded resolution preference:</strong> <?= $incident['resolution_preference'] === 'refund' ? 'Request Refund' : 'Request Replacement' ?></p>
            <?php endif; ?>
            <?php if ($incident['booking_status'] === 'disputed'): ?><p class="form-hint">This booking is temporarily on hold while the team processes a refund or assigns another vehicle.</p><?php endif; ?>
        </section>

        <section class="card incident-vehicle-registry" aria-labelledby="vehicle-approval-registry-title">
            <h2 id="vehicle-approval-registry-title">Vehicle Approval Registry</h2>
            <div class="incident-vehicle-registry__table-wrap">
                <table>
                    <thead><tr><th>Vehicle Model</th><th>Fleet Owner</th><th>Reg No.</th><th>Status</th><th>Approvals Action</th></tr></thead>
                    <tbody>
                        <?php foreach ([
                            ['Toyota Aqua', 'Perera Rentals', 'CBA-4587', 'approved'],
                            ['Toyota KDH Van', 'Perera Rentals', 'NC-7821', 'approved'],
                            ['Suzuki Alto', 'Perera Rentals', 'CAD-2291', 'approved'],
                            ['Honda Vezel', 'Perera Rentals', 'CAX-9084', 'approved'],
                            ['Suzuki Wagon R', 'Perera Rentals', 'CBH-3344', 'pending'],
                            ['Nissan Caravan', 'Perera Rentals', 'WP NB-6142', 'approved'],
                        ] as $vehicleRegistry): ?>
                            <tr>
                                <td><?= htmlspecialchars($vehicleRegistry[0], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($vehicleRegistry[1], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($vehicleRegistry[2], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="incident-vehicle-registry__status incident-vehicle-registry__status--<?= $vehicleRegistry[3] ?>"><?= htmlspecialchars(ucfirst($vehicleRegistry[3]), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?php if ($vehicleRegistry[3] === 'approved'): ?><span class="incident-vehicle-registry__verified">Verified</span><?php else: ?><span class="incident-vehicle-registry__restricted">Pending staff approval</span><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card incident-replacement-card">
            <?php if (is_array($incident['replacement'])): ?>
                <?php $replacement = $incident['replacement']; ?>
                <div class="card__header"><div><p class="eyebrow">Replacement request #<?= (int) $replacement['id'] ?></p><h2>Another vehicle requested</h2></div><span class="status-badge status-badge--info"><?= htmlspecialchars(ucfirst($replacement['status']), ENT_QUOTES, 'UTF-8') ?></span></div>
                <?php if (!empty($replacement['replacement_make'])): ?><p><strong>Assigned vehicle:</strong> <?= htmlspecialchars($replacement['replacement_make'] . ' ' . $replacement['replacement_model'] . ' (' . $replacement['replacement_license_plate'] . ')', ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <?php if (!empty($replacement['admin_remarks'])): ?><p class="form-hint"><strong>Team note:</strong> <?= htmlspecialchars($replacement['admin_remarks'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <?php if ($replacement['status'] === 'approved' && !empty($replacement['replacement_vehicle_id'])): ?>
                    <p class="form-hint">A replacement vehicle has been assigned and approved for your rental. Please review and confirm your decision below.</p>
                    <form class="feature-form feature-form--compact" method="post" action="<?= htmlspecialchars(customer_url('incidents/details.php?id=' . (int) $incident['id']), ENT_QUOTES, 'UTF-8') ?>" data-submit-once>
                        <?= CustomerCsrf::field() ?>
                        <input type="hidden" name="incident_id" value="<?= (int) $incident['id'] ?>">
                        <label for="replacement-decision">Your decision</label>
                        <select id="replacement-decision" name="decision" required><option value="accept">Accept replacement</option><option value="reject">Reject replacement</option></select>
                        <button class="button button--primary" type="submit">Confirm decision</button>
                    </form>
                <?php elseif (in_array($replacement['status'], ['dispatched', 'delivered'], true)): ?>
                    <p class="form-hint">You have accepted this replacement vehicle. It is now active on your rental booking.</p>
                <?php elseif (in_array($replacement['status'], ['rejected', 'cancelled'], true)): ?>
                    <p class="form-hint">This replacement offer has been <?= htmlspecialchars($replacement['status'], ENT_QUOTES, 'UTF-8') ?>.</p>
                <?php else: ?>
                    <p>Your request is with the team. We will contact you when an eligible replacement vehicle is available.</p>
                <?php endif; ?>
            <?php elseif ($viewData['can_request_replacement']): ?>
                <p class="eyebrow">Incident approved</p>
                <h2>Request another vehicle</h2>
                <p>Your incident has been approved. Submit a replacement request and our team will check matching available vehicles.</p>
                <?php foreach ($viewData['errors'] as $error): ?><p class="field-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>
                <form method="post" class="feature-form incident-replacement-form" data-submit-once>
                    <?= CustomerCsrf::field() ?>
                    <input type="hidden" name="action" value="request_replacement">
                    <label for="replacement-reason">Reason for another vehicle</label>
                    <textarea id="replacement-reason" name="replacement_reason" minlength="10" maxlength="500" required placeholder="For example, the vehicle cannot be used safely after the incident."></textarea>
                    <p class="form-hint">A replacement is subject to matching vehicle availability and team approval.</p>
                    <button class="button button--primary" type="submit">Request another vehicle</button>
                </form>
            <?php else: ?>
                <p class="eyebrow">Awaiting incident approval</p>
                <h2>Replacement request unavailable</h2>
                <p>Once an authorized team member approves this incident, you can request another vehicle from this page.</p>
            <?php endif; ?>
        </section>

        <section class="card"><h2>Incident workflow</h2><ol><li><?= $incident['status'] === 'resolved' ? '✓' : '●' ?> Incident reported</li><li><?= $incident['status'] === 'resolved' ? '✓' : '●' ?> Team review</li><li><?= is_array($incident['replacement']) ? '✓' : '○' ?> Replacement request</li><li>○ Vehicle assignment</li></ol></section>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
