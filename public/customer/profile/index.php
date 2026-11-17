<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';
$viewData = (new CustomerPortalController())->profilePage($_POST, strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), true, $_FILES);
if (isset($viewData['redirect'])) { header('Location: ' . customer_url($viewData['redirect']), true, 303); exit; }
$profile = $viewData['profile'] ?? null;
$documents = $viewData['documents'] ?? [];
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<?php if ($viewData['database_error'] || !is_array($profile)): ?>
    <section class="empty-state"><h2>Profile unavailable</h2><p>Please try again later.</p></section>
<?php else: ?>
    <div class="feature-detail-grid">
        <form class="card feature-form" method="post" action="<?= htmlspecialchars(customer_url('profile/index.php'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" data-submit-once>
            <?= CustomerCsrf::field() ?>
            <span class="demo-label demo-label--database">Database profile</span>
            <h2>Permitted account details</h2>
            <label for="profile-name">Full name</label>
            <input id="profile-name" name="name" maxlength="100" value="<?= htmlspecialchars($viewData['form']['name'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($viewData['errors']['name'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <label for="profile-email">Email address</label>
            <input id="profile-email" value="<?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8') ?>" readonly>
            <p class="form-hint">Email changes require coordinated authentication work.</p>
            <label for="profile-phone">Phone number</label>
            <input id="profile-phone" name="phone" maxlength="20" value="<?= htmlspecialchars($viewData['form']['phone'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($viewData['errors']['phone'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['phone'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <label for="profile-district">District <span class="form-hint">Optional</span></label>
            <input id="profile-district" name="district" maxlength="100" value="<?= htmlspecialchars($viewData['form']['district'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($viewData['errors']['district'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['district'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <label for="profile-address">Address <span class="form-hint">Optional</span></label>
            <textarea id="profile-address" name="address" maxlength="255" rows="3"><?= htmlspecialchars($viewData['form']['address'], ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (isset($viewData['errors']['address'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['address'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

            <label for="profile-nic">NIC number</label>
            <input id="profile-nic" name="nic_number" maxlength="20" value="<?= htmlspecialchars($viewData['form']['nic_number'], ENT_QUOTES, 'UTF-8') ?>">
            <?php $nicDocument = $documents['nic'] ?? null; ?>
            <div class="document-upload"><input class="document-upload__input" id="profile-nic-document" name="nic_document" type="file" accept="application/pdf,image/jpeg,image/png"><label class="button button--secondary button--small" for="profile-nic-document">Add NIC document</label><p class="form-hint"><?php if (is_array($nicDocument)): ?>Uploaded: <?= htmlspecialchars((string) $nicDocument['original_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars(ucfirst((string) $nicDocument['verification_status']), ENT_QUOTES, 'UTF-8') ?>).<?php else: ?>PDF, JPEG, or PNG — maximum 5 MB.<?php endif; ?></p></div>
            <?php if (isset($viewData['errors']['nic_document'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['nic_document'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

            <label for="profile-license">Driving licence number</label>
            <input id="profile-license" name="driving_license_number" maxlength="30" value="<?= htmlspecialchars($viewData['form']['driving_license_number'], ENT_QUOTES, 'UTF-8') ?>">
            <?php $licenceDocument = $documents['driving_license'] ?? null; ?>
            <div class="document-upload"><input class="document-upload__input" id="profile-license-document" name="driving_license_document" type="file" accept="application/pdf,image/jpeg,image/png"><label class="button button--secondary button--small" for="profile-license-document">Add driving licence document</label><p class="form-hint"><?php if (is_array($licenceDocument)): ?>Uploaded: <?= htmlspecialchars((string) $licenceDocument['original_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars(ucfirst((string) $licenceDocument['verification_status']), ENT_QUOTES, 'UTF-8') ?>).<?php else: ?>PDF, JPEG, or PNG — maximum 5 MB.<?php endif; ?></p></div>
            <?php if (isset($viewData['errors']['driving_license_document'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['driving_license_document'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

            <fieldset class="profile-guarantor" id="guarantor-details">
                <legend>Guarantor Details</legend>
                <?php if (!($viewData['has_guarantor'] ?? false)): ?><p class="form-hint">No guarantor has been added yet. Add a trusted contact below.</p><?php endif; ?>
                <label for="guarantor-full-name">Full Name</label><input id="guarantor-full-name" name="guarantor_full_name" maxlength="100" value="<?= htmlspecialchars($viewData['form']['guarantor_full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                <?php if (isset($viewData['errors']['guarantor_full_name'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['guarantor_full_name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <label for="guarantor-nic">Guarantor NIC</label><input id="guarantor-nic" name="guarantor_nic" maxlength="20" value="<?= htmlspecialchars($viewData['form']['guarantor_nic'], ENT_QUOTES, 'UTF-8') ?>" required>
                <?php $guarantorNicDocument = $documents['guarantor_nic'] ?? null; ?>
                <div class="document-upload"><input class="document-upload__input" id="guarantor-nic-document" name="guarantor_nic_document" type="file" accept="application/pdf,image/jpeg,image/png"><label class="button button--secondary button--small" for="guarantor-nic-document">Add guarantor NIC document</label><p class="form-hint"><?php if (is_array($guarantorNicDocument)): ?>Uploaded: <?= htmlspecialchars((string) $guarantorNicDocument['original_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars(ucfirst((string) $guarantorNicDocument['verification_status']), ENT_QUOTES, 'UTF-8') ?>).<?php else: ?>PDF, JPEG, or PNG — maximum 5 MB.<?php endif; ?></p></div>
                <?php if (isset($viewData['errors']['guarantor_nic_document'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['guarantor_nic_document'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <label for="guarantor-phone">Guarantor Phone Number</label><input id="guarantor-phone" name="guarantor_phone" inputmode="numeric" maxlength="20" value="<?= htmlspecialchars($viewData['form']['guarantor_phone'], ENT_QUOTES, 'UTF-8') ?>" required>
                <?php if (isset($viewData['errors']['guarantor_phone'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['guarantor_phone'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <label for="guarantor-relationship">Relationship to Customer</label><select id="guarantor-relationship" name="guarantor_relationship" required><option value="">Select relationship</option><?php foreach (['Parent', 'Spouse', 'Sibling', 'Relative', 'Friend', 'Employer', 'Other'] as $relationship): ?><option value="<?= htmlspecialchars($relationship, ENT_QUOTES, 'UTF-8') ?>" <?= $viewData['form']['guarantor_relationship'] === $relationship ? 'selected' : '' ?>><?= htmlspecialchars($relationship, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
                <?php if (isset($viewData['errors']['guarantor_relationship'])): ?><p class="field-error"><?= htmlspecialchars($viewData['errors']['guarantor_relationship'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            </fieldset>
            <button class="button button--primary" type="submit">Save profile and submit documents</button>
        </form>
        <aside class="card"><h2>Account controls</h2><dl class="feature-detail-list"><div><dt>Account</dt><dd><?= htmlspecialchars(ucfirst($profile['user_status']), ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>Verification</dt><dd><?= htmlspecialchars(ucfirst($profile['verification_status']), ENT_QUOTES, 'UTF-8') ?></dd></div></dl><div class="alert alert--info"><p>Role, account status, verification decision, email, and password are not Customer-editable here. No insecure prototype password form is provided.</p></div><a class="button button--secondary" href="<?= htmlspecialchars(customer_url('verification/index.php'), ENT_QUOTES, 'UTF-8') ?>">Open Verification</a></aside>
    </div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
