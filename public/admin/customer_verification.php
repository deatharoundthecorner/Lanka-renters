<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/CustomerVerification.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$csrfToken = AuthHelper::getCsrfToken();
$flash = $_SESSION['admin_customer_verification_flash'] ?? null;
unset($_SESSION['admin_customer_verification_flash']);
$verification = new CustomerVerification();
$customerId = (int) ($_GET['customer_id'] ?? 0);
$customer = null;
$customers = [];
$databaseError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['admin_customer_verification_flash'] = ['message' => 'CSRF verification failed.'];
        header('Location: customer_verification.php');
        exit;
    }

    $customerId = (int) ($_POST['customer_id'] ?? 0);
    $reviewType = (string) ($_POST['review_type'] ?? '');
    $status = (string) ($_POST['verification_status'] ?? '');
    $reason = trim((string) ($_POST['review_reason'] ?? ''));
    $admin = AuthHelper::getCurrentUser();
    try {
        $verification->review($customerId, $reviewType, $status, $reason, (int) ($admin['id'] ?? 0));
        $_SESSION['admin_customer_verification_flash'] = ['message' => 'Customer ' . $reviewType . ' review updated successfully.'];
    } catch (Throwable $exception) {
        error_log(sprintf('Admin Customer verification failed [%s]: %s', get_class($exception), $exception->getMessage()));
        $_SESSION['admin_customer_verification_flash'] = ['message' => $exception instanceof DomainException ? $exception->getMessage() : 'The review could not be saved.'];
    }
    header('Location: customer_verification.php?customer_id=' . max(0, $customerId));
    exit;
}

try {
    $customers = $verification->listForAdmin();
    if ($customerId > 0) {
        $customer = $verification->findForAdmin($customerId);
    }
} catch (Throwable $exception) {
    error_log(sprintf('Admin Customer verification load failed [%s]: %s', get_class($exception), $exception->getMessage()));
    $databaseError = true;
}

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statusLabel = static fn (mixed $value): string => ucfirst(str_replace('_', ' ', (string) $value));
$badgeForStatus = static fn (mixed $value): string => match ((string) $value) {
    'approved' => 'badge-approved',
    'rejected' => 'badge-rejected',
    'resubmission_required' => 'badge-resubmission_required',
    default => 'badge-pending',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Verification - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>
        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Customer Verification</h1>
                <p class="page-subtitle">Review identity and driving-licence submissions as separate decisions.</p>
            </div>
            <?php if (is_array($flash)): ?><div class="alert-box" role="alert" style="margin-bottom:16px;"><?= $escape($flash['message'] ?? '') ?></div><?php endif; ?>
            <?php if ($databaseError): ?>
                <div class="card"><p>Customer verification records are temporarily unavailable.</p></div>
            <?php else: ?>
                <div class="card" style="margin-bottom:24px;">
                    <div class="card-header-clean"><h3 class="card-title-text">Customer submissions</h3><span class="badge badge-pending"><?= count($customers) ?> Records</span></div>
                    <div class="lr-table-wrap table-responsive">
                        <table class="custom-table">
                            <thead><tr><th>Customer</th><th>Identity</th><th>Licence</th><th>Terms accepted</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php if ($customers === []): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="lr-empty-state">
                                            <div class="lr-empty-icon">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                                            </div>
                                            <h3>No verification records found</h3>
                                            <p>No customer identity or driving licence submissions require attention.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: foreach ($customers as $row): ?>
                                <tr>
                                    <td><span class="cell-primary-text"><?= $escape($row['name']) ?></span><span class="cell-secondary-text"><?= $escape($row['email']) ?></span></td>
                                    <td><span class="badge <?= $badgeForStatus($row['identity_verification_status']) ?>"><?= $escape($statusLabel($row['identity_verification_status'])) ?></span></td>
                                    <td><span class="badge <?= $row['has_driving_license'] ? $badgeForStatus($row['driving_license_status']) : 'badge-blue' ?>"><?= $row['has_driving_license'] ? $escape($statusLabel($row['driving_license_status'])) : 'No licence' ?></span></td>
                                    <td><?= $row['terms_accepted_at'] ? $escape($row['terms_accepted_at']) : 'Not accepted' ?></td>
                                    <td><a class="btn btn-primary btn-sm" href="customer_verification.php?customer_id=<?= (int) $row['customer_id'] ?>">Review</a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (is_array($customer)): ?>
                    <?php $documents = is_array($customer['documents'] ?? null) ? $customer['documents'] : []; ?>
                    <div class="card" style="margin-bottom:24px;">
                        <div class="card-header-clean">
                            <div>
                                <h3 class="card-title-text" style="font-size:18px;">Reviewing: <?= $escape($customer['name']) ?></h3>
                                <span class="cell-secondary-text">Customer Account ID #<?= (int)$customer['id'] ?></span>
                            </div>
                            <a class="btn btn-secondary btn-sm" href="customer_verification.php">
                                ← Back to Submissions
                            </a>
                        </div>
                        
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; padding:16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; margin-bottom:20px;">
                            <div>
                                <span class="cell-secondary-text" style="font-size:11.5px; text-transform:uppercase; font-weight:600; letter-spacing:0.04em;">Email Address</span>
                                <div class="cell-primary-text" style="font-weight:600; margin-top:2px;"><?= $escape($customer['email']) ?></div>
                            </div>
                            <div>
                                <span class="cell-secondary-text" style="font-size:11.5px; text-transform:uppercase; font-weight:600; letter-spacing:0.04em;">Phone Number</span>
                                <div class="cell-primary-text" style="font-weight:600; margin-top:2px;"><?= $escape($customer['phone']) ?></div>
                            </div>
                            <div>
                                <span class="cell-secondary-text" style="font-size:11.5px; text-transform:uppercase; font-weight:600; letter-spacing:0.04em;">Terms & Conditions</span>
                                <div style="margin-top:2px;">
                                    <?php if ($customer['terms_accepted_at']): ?>
                                        <span class="badge badge-approved" style="font-size:11px;">Accepted (<?= $escape($customer['terms_version'] ?? 'v1') ?>)</span>
                                    <?php else: ?>
                                        <span class="badge badge-suspended" style="font-size:11px;">Not accepted</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:20px;">
                            <?php foreach (['identity' => ['label' => 'National Identity Card (NIC)', 'type' => 'nic', 'status' => $customer['identity_verification_status'], 'reason' => $customer['identity_rejection_reason'], 'reviewed_by' => $customer['identity_reviewed_by'], 'reviewed_at' => $customer['identity_reviewed_at']], 'driving_license' => ['label' => 'Driving Licence', 'type' => 'driving_license', 'status' => $customer['driving_license_status'], 'reason' => $customer['license_rejection_reason'], 'reviewed_by' => $customer['license_reviewed_by'], 'reviewed_at' => $customer['license_reviewed_at']]] as $reviewType => $review): ?>
                                <section class="doc-review-card">
                                    <div class="doc-review-header">
                                        <h4 class="doc-review-title"><?= $escape($review['label']) ?></h4>
                                        <span class="badge <?= $badgeForStatus($review['status']) ?>">
                                            <?= $escape($statusLabel($review['status'])) ?>
                                        </span>
                                    </div>
                                    <?php if ($reviewType === 'driving_license' && !(int) $customer['has_driving_license']): ?>
                                        <div class="doc-meta-box">
                                            <p style="color:var(--text-muted); font-style:italic; margin:0; font-size:13px;">No licence submitted — Customer selected Driver required for bookings.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php $document = $documents[$review['type']] ?? null; ?>
                                        <div class="doc-meta-box">
                                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                                <span style="font-size:12px; color:var(--text-secondary); font-weight:600;">
                                                    <?= $reviewType === 'driving_license' ? 'Licence Number:' : 'NIC Number:' ?>
                                                </span>
                                                <span style="font-family:monospace; font-weight:700; font-size:13px; color:var(--text-main);">
                                                    <?= $escape(($reviewType === 'driving_license' ? ($customer['driving_license_number'] ?? '') : ($customer['nic_number'] ?? '')) ?: 'Not provided') ?>
                                                </span>
                                            </div>

                                            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; padding-top:8px; margin-top:8px;">
                                                <span style="font-size:12px; color:var(--text-secondary);">Uploaded File:</span>
                                                <?php if (is_array($document)): ?>
                                                    <a class="btn btn-secondary btn-sm" target="_blank" href="customer_document.php?id=<?= (int) $document['id'] ?>" style="display:inline-flex; align-items:center; gap:4px; font-weight:600;">
                                                        View Document ↗
                                                    </a>
                                                <?php else: ?>
                                                    <span class="cell-secondary-text" style="font-style:italic; color:var(--text-muted); font-size:12px;">Not uploaded</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if ($review['reason']): ?>
                                            <div class="alert-box" style="margin-bottom:12px; font-size:12.5px;">
                                                <strong>Current Reason:</strong> <?= $escape($review['reason']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($review['reviewed_by'] || $review['reviewed_at']): ?>
                                            <p class="cell-secondary-text" style="margin-bottom:12px; font-size:11.5px;">
                                                Reviewed by User #<?= (int) $review['reviewed_by'] ?> on <?= $escape($review['reviewed_at']) ?>
                                            </p>
                                        <?php endif; ?>

                                        <form method="post" action="customer_verification.php?customer_id=<?= (int) $customer['id'] ?>" style="margin-top:auto;">
                                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>">
                                            <input type="hidden" name="review_type" value="<?= $escape($reviewType) ?>">
                                            
                                            <label for="reason-<?= $escape($reviewType) ?>" style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:4px;">
                                                Reason (for Rejection or Resubmission)
                                            </label>
                                            <textarea id="reason-<?= $escape($reviewType) ?>" name="review_reason" rows="2" maxlength="2000" placeholder="Specify requirements or defect details..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:8px; font-size:13px; font-family:inherit;"></textarea>
                                            
                                            <div class="btn-group" style="margin-top:12px; gap:8px; flex-wrap:wrap;">
                                                <button class="btn btn-approve btn-sm" type="submit" name="verification_status" value="approved">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                    Approve
                                                </button>
                                                <button class="btn btn-reject btn-sm" type="submit" name="verification_status" value="rejected">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                    Reject
                                                </button>
                                                <button class="btn btn-warning btn-sm" type="submit" name="verification_status" value="resubmission_required">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                                    Request Resubmission
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
