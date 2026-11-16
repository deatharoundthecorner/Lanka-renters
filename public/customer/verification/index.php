<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';
$viewData = (new CustomerPortalController())->verificationPage($_POST, strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), $_FILES);
if (isset($viewData['redirect'])) { header('Location: ' . customer_url($viewData['redirect']), true, 303); exit; }
$profile = $viewData['profile'] ?? null;
$verification = $viewData['verification'] ?? null;
$documents = $viewData['documents'] ?? [];
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<?php if ($viewData['database_error'] || !is_array($profile) || !is_array($verification)): ?>
    <section class="empty-state"><h2>Verification details unavailable</h2><p>Please try again later.</p></section>
<?php else: ?>
    <div class="feature-detail-grid">
        <form class="card feature-form" id="identity-details" method="post" action="<?= htmlspecialchars(customer_url('verification/index.php'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" data-submit-once>
            <?= CustomerCsrf::field() ?>
            <h2>Identity details</h2>
            <p>These database fields support Admin review. Saving them does not approve your account.</p>
            <input type="hidden" name="name" value="<?= htmlspecialchars($viewData['form']['name'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="phone" value="<?= htmlspecialchars($viewData['form']['phone'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="district" value="<?= htmlspecialchars($viewData['form']['district'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="address" value="<?= htmlspecialchars($viewData['form']['address'], ENT_QUOTES, 'UTF-8') ?>">
            <label for="verification-nic">NIC number</label>
            <input id="verification-nic" name="nic_number" maxlength="20" value="<?= htmlspecialchars($viewData['form']['nic_number'], ENT_QUOTES, 'UTF-8') ?>">
            <?php $nicDocument = $documents['nic'] ?? null; ?>
            <div class="document-upload"><input class="document-upload__input" id="verification-nic-document" name="nic_document" type="file" accept="application/pdf,image/jpeg,image/png"><label class="button button--secondary button--small" for="verification-nic-document">Add NIC document</label><p class="form-hint"><?php if (is_array($nicDocument)): ?>Uploaded: <?= htmlspecialchars((string) $nicDocument['original_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars(ucfirst((string) $nicDocument['verification_status']), ENT_QUOTES, 'UTF-8') ?>).<?php else: ?>PDF, JPEG, or PNG — maximum 5 MB.<?php endif; ?></p></div>
            <?php if (isset($viewData['errors']['nic_document'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['nic_document'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <label for="verification-license">Driving licence number</label>
            <input id="verification-license" name="driving_license_number" maxlength="30" value="<?= htmlspecialchars($viewData['form']['driving_license_number'], ENT_QUOTES, 'UTF-8') ?>">
            <?php $licenceDocument = $documents['driving_license'] ?? null; ?>
            <div class="document-upload"><input class="document-upload__input" id="verification-license-document" name="driving_license_document" type="file" accept="application/pdf,image/jpeg,image/png"><label class="button button--secondary button--small" for="verification-license-document">Add driving licence document</label><p class="form-hint"><?php if (is_array($licenceDocument)): ?>Uploaded: <?= htmlspecialchars((string) $licenceDocument['original_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars(ucfirst((string) $licenceDocument['verification_status']), ENT_QUOTES, 'UTF-8') ?>).<?php else: ?>PDF, JPEG, or PNG — maximum 5 MB.<?php endif; ?></p></div>
            <?php if (isset($viewData['errors']['driving_license_document'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['driving_license_document'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <button class="button button--primary" type="submit">Save and submit documents</button>
        </form>

        <aside class="card verification-card">
            <div class="verification-card__header">
                <div><p class="eyebrow">Admin-owned decision</p><h2>Customer verification</h2></div>
                <span class="status-badge status-badge--<?= htmlspecialchars($verification['overall_status'], ENT_QUOTES, 'UTF-8') === 'approved' ? 'success' : ($verification['overall_status'] === 'rejected' ? 'error' : 'warning') ?>"><?= htmlspecialchars(ucfirst($verification['overall_status']), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <p class="verification-card__summary"><?= htmlspecialchars($verification['overall_message'], ENT_QUOTES, 'UTF-8') ?></p>
            <div class="verification-card__rows">
                <?php foreach ($verification['rows'] as $row): ?>
                    <div class="verification-row">
                        <span class="verification-row__icon verification-row__icon--<?= htmlspecialchars($row['tone'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"><?= customer_icon($row['icon']) ?></span>
                        <div class="verification-row__content"><h3><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') ?></p></div>
                        <div class="verification-row__actions"><span class="status-badge status-badge--<?= htmlspecialchars($row['tone'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($row['status']), ENT_QUOTES, 'UTF-8') ?></span><a class="button button--secondary button--small" href="<?= htmlspecialchars(customer_url($row['action_path']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['action_label'], ENT_QUOTES, 'UTF-8') ?></a></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
