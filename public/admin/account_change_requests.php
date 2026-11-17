<?php
require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/controllers/AdminApprovalController.php';
require_once dirname(__DIR__, 2) . '/app/models/AccountChangeRequest.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Account Change Requests";

$controller = new AdminApprovalController();
$model = new AccountChangeRequest();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security token verification failed.";
    } else {
        $action = $_POST['action'] ?? '';
        $requestId = (int)($_POST['request_id'] ?? 0);

        if ($action === 'approve') {
            $result = $controller->approveAccountChange($requestId);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? 'Declined by administrative review.');
            $result = $controller->rejectAccountChange($requestId, $reason);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
    }
}

try {
    $pendingRequests = $model->getPendingRequests();
} catch (Throwable $e) {
    error_log("AccountChangeRequest error: " . $e->getMessage());
    $pendingRequests = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Change Requests - Lanka Renters Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-canonical.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h1 class="page-title">Account Change Requests</h1>
                        <p class="page-subtitle">Review and verify security credential changes requested by registered drivers and users.</p>
                    </div>
                    <div>
                        <span class="badge badge-pending" style="font-size: 13px; padding: 6px 14px;">
                            <?= count($pendingRequests) ?> Pending Request<?= count($pendingRequests) === 1 ? '' : 's' ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 20px; background: #d1fae5; border-color: #6ee7b7; color: #065f46;">
                    <?= $escape($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 20px; background: #fee2e2; border-color: #fca5a5; color: #991b1b;">
                    <?= $escape($error) ?>
                </div>
            <?php endif; ?>

            <div class="card" style="padding: 0; overflow: hidden; background: #ffffff;">
                <?php if (empty($pendingRequests)): ?>
                    <div style="padding: 48px 24px; text-align: center;">
                        <div style="width: 54px; height: 54px; margin: 0 auto 16px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748b;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>
                            </svg>
                        </div>
                        <h2 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin: 0 0 6px;">No Pending Requests</h2>
                        <p style="color: var(--text-muted); font-size: 13px; max-width: 440px; margin: 0 auto 20px;">
                            All user account and credential change submissions have been verified and processed.
                        </p>
                        <a href="users.php" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                            <span>Manage All Users</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border); text-align: left; font-size: 12px; color: var(--text-muted); background: var(--bg-main);">
                                    <th style="padding: 12px 16px;">User</th>
                                    <th style="padding: 12px 16px;">Field</th>
                                    <th style="padding: 12px 16px;">Current Value</th>
                                    <th style="padding: 12px 16px;">Requested New Value</th>
                                    <th style="padding: 12px 16px;">Submitted</th>
                                    <th style="padding: 12px 16px; text-align: right;">Review Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRequests as $req): ?>
                                    <tr style="border-bottom: 1px solid var(--border-light); font-size: 13px;">
                                        <td style="padding: 12px 16px;">
                                            <div style="font-weight: 600; color: var(--text-main);"><?= $escape($req['user_name'] ?? 'User #' . $req['user_id']) ?></div>
                                            <div style="font-size: 11px; color: var(--text-muted);"><?= $escape($req['user_email'] ?? '') ?></div>
                                        </td>
                                        <td style="padding: 12px 16px;">
                                            <span class="badge badge-blue" style="text-transform: capitalize;">
                                                <?= $escape(str_replace('_', ' ', (string)($req['change_type'] ?? 'Credential'))) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 16px; color: var(--text-secondary); font-family: monospace; font-size: 12px;">
                                            <?= $escape($req['old_value'] ?: '(None)') ?>
                                        </td>
                                        <td style="padding: 12px 16px; font-weight: 600; color: var(--primary); font-family: monospace; font-size: 12px;">
                                            <?= $escape($req['requested_value'] ?? '') ?>
                                        </td>
                                        <td style="padding: 12px 16px; color: var(--text-muted); font-size: 12px;">
                                            <?= $escape(date('M d, Y h:i A', strtotime($req['created_at'] ?? 'now'))) ?>
                                        </td>
                                        <td style="padding: 12px 16px; text-align: right;">
                                            <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                                <form method="POST" action="account_change_requests.php" style="margin: 0;">
                                                    <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Approve this credential change? The user account will be updated immediately.');">
                                                        Approve
                                                    </button>
                                                </form>

                                                <form method="POST" action="account_change_requests.php" style="margin: 0;" onsubmit="var r = prompt('Reason for rejection (optional):'); if(r===null) return false; this.rejection_reason.value = r; return true;">
                                                    <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                                    <input type="hidden" name="rejection_reason" value="">
                                                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--danger); border-color: var(--border);">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?php include __DIR__ . '/partials/footer.php'; ?>
    </div>
</div>

</body>
</html>
