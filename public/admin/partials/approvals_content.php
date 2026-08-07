<?php
// Admin Approvals dashboard content (partial)
require_once dirname(dirname(dirname(__DIR__))) . '/app/controllers/AdminApprovalController.php';

$approvalController = new AdminApprovalController();

$success = '';
$error = '';

// Handle POST processing actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['rejection_reason'] ?? '');

        if ($action === 'approve_doc') {
            $res = $approvalController->approveDocument($id);
            if ($res['success']) $success = $res['message']; else $error = $res['error'];
        } elseif ($action === 'reject_doc') {
            $res = $approvalController->rejectDocument($id, $reason);
            if ($res['success']) $success = $res['message']; else $error = $res['error'];
        } elseif ($action === 'approve_profile') {
            $res = $approvalController->approveProfileChange($id);
            if ($res['success']) $success = $res['message']; else $error = $res['error'];
        } elseif ($action === 'reject_profile') {
            $res = $approvalController->rejectProfileChange($id, $reason);
            if ($res['success']) $success = $res['message']; else $error = $res['error'];
        } elseif ($action === 'approve_account') {
            $res = $approvalController->approveAccountChange($id);
            if ($res['success']) $success = $res['message']; else $error = $res['error'];
        } elseif ($action === 'reject_account') {
            $res = $approvalController->rejectAccountChange($id, $reason);
            if ($res['success']) $success = $res['message']; else $error = $res['error'];
        }
    }
}

// Fetch all pending lists
$queuesResult = $approvalController->getPendingQueues();
if (!$queuesResult['success']) {
    $error = "Failed to load approval queues: " . htmlspecialchars($queuesResult['error']);
    $documents = [];
    $profileChanges = [];
    $accountChanges = [];
} else {
    $documents = $queuesResult['documents'];
    $profileChanges = $queuesResult['profile_changes'];
    $accountChanges = $queuesResult['account_changes'];
}
?>
<header class="page-header">
    <div>
        <h1>Approvals Center</h1>
        <p>Review and decide on driver document replacements, profile changes, and credentials updates.</p>
    </div>
</header>

<!-- Alerts -->
<?php if (!empty($success)): ?>
    <div style="background-color: #DCFCE7; color: #16A34A; padding: 15px; border-radius: 8px; font-weight: 600; margin-bottom: 24px; border: 1px solid #BBF7D0;">
        ✓ <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div style="background-color: #FEE2E2; color: #DC2626; padding: 15px; border-radius: 8px; font-weight: 600; margin-bottom: 24px; border: 1px solid #FCA5A5;">
        ✗ <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Section 1: Pending Documents -->
<section class="panel-card wide-card" style="margin-bottom: 35px; padding: 20px; background: white; border-radius: 8px; border: 1px solid var(--border-color);">
    <h2 style="font-size: 18px; margin-bottom: 15px; color: var(--primary-blue);">1. Pending Document Replacements</h2>
    <?php if (empty($documents)): ?>
        <p style="font-style: italic; color: var(--text-muted);">No pending document replacements at this time.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left; font-weight: 700;">
                    <th style="padding: 12px 10px;">Driver</th>
                    <th style="padding: 12px 10px;">Type</th>
                    <th style="padding: 12px 10px;">No / Expiry</th>
                    <th style="padding: 12px 10px;">Version</th>
                    <th style="padding: 12px 10px;">Action / Rejection Reason Input</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 15px 10px;">
                            <strong><?php echo htmlspecialchars($doc['user_name']); ?></strong><br>
                            <small style="color: var(--text-muted);"><?php echo htmlspecialchars($doc['user_email']); ?></small>
                        </td>
                        <td style="padding: 15px 10px; text-transform: uppercase; font-weight: 600;">
                            <?php echo htmlspecialchars(str_replace('_', ' ', $doc['document_type'])); ?>
                        </td>
                        <td style="padding: 15px 10px;">
                            No: <?php echo htmlspecialchars($doc['document_number']); ?><br>
                            <small style="color: var(--text-muted);">Exp: <?php echo htmlspecialchars($doc['expiry_date']); ?></small>
                        </td>
                        <td style="padding: 15px 10px; font-weight: 700;">
                            v<?php echo htmlspecialchars($doc['version']); ?>
                        </td>
                        <td style="padding: 15px 10px;">
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <a href="../<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" style="padding: 6px 12px; background: #DBEAFE; color: #1E40AF; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 12px;">View File ↗</a>
                                    
                                    <form action="" method="POST" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                        <input type="hidden" name="action" value="approve_doc">
                                        <input type="hidden" name="id" value="<?php echo $doc['id']; ?>">
                                        <button type="submit" style="padding: 6px 12px; background: #22C55E; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px;">Approve</button>
                                    </form>
                                </div>
                                
                                <form action="" method="POST" style="margin:0; display: flex; gap: 8px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                    <input type="hidden" name="action" value="reject_doc">
                                    <input type="hidden" name="id" value="<?php echo $doc['id']; ?>">
                                    <input type="text" name="rejection_reason" placeholder="Enter reason for rejection" required style="padding: 6px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 12px; flex: 1;">
                                    <button type="submit" style="padding: 6px 12px; background: #EF4444; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px;">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<!-- Section 2: Pending Profile Changes -->
<section class="panel-card wide-card" style="margin-bottom: 35px; padding: 20px; background: white; border-radius: 8px; border: 1px solid var(--border-color);">
    <h2 style="font-size: 18px; margin-bottom: 15px; color: var(--primary-blue);">2. Pending Profile Details Changes</h2>
    <?php if (empty($profileChanges)): ?>
        <p style="font-style: italic; color: var(--text-muted);">No pending profile details changes at this time.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left; font-weight: 700;">
                    <th style="padding: 12px 10px;">Driver</th>
                    <th style="padding: 12px 10px;">Field</th>
                    <th style="padding: 12px 10px;">Current Value</th>
                    <th style="padding: 12px 10px;">Requested Value</th>
                    <th style="padding: 12px 10px;">Actions / Rejection reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profileChanges as $req): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 15px 10px;">
                            <strong><?php echo htmlspecialchars($req['user_name']); ?></strong><br>
                            <small style="color: var(--text-muted);"><?php echo htmlspecialchars($req['user_email']); ?></small>
                        </td>
                        <td style="padding: 15px 10px; text-transform: capitalize; font-weight: 600; color: #475569;">
                            <?php echo htmlspecialchars(str_replace('_', ' ', $req['field_name'])); ?>
                        </td>
                        <td style="padding: 15px 10px; color: var(--text-muted);">
                            <?php echo htmlspecialchars($req['old_value'] !== '' ? $req['old_value'] : '(Empty)'); ?>
                        </td>
                        <td style="padding: 15px 10px; font-weight: 600; color: #1E3A8A;">
                            <?php echo htmlspecialchars($req['requested_value'] !== '' ? $req['requested_value'] : '(Empty)'); ?>
                        </td>
                        <td style="padding: 15px 10px;">
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <form action="" method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                    <input type="hidden" name="action" value="approve_profile">
                                    <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                    <button type="submit" style="padding: 6px 12px; background: #22C55E; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px; width: 100%;">Approve</button>
                                </form>
                                
                                <form action="" method="POST" style="margin:0; display: flex; gap: 8px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                    <input type="hidden" name="action" value="reject_profile">
                                    <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                    <input type="text" name="rejection_reason" placeholder="Enter reason for rejection" required style="padding: 6px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 12px; flex: 1;">
                                    <button type="submit" style="padding: 6px 12px; background: #EF4444; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px;">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<!-- Section 3: Pending Credentials Changes -->
<section class="panel-card wide-card" style="margin-bottom: 35px; padding: 20px; background: white; border-radius: 8px; border: 1px solid var(--border-color);">
    <h2 style="font-size: 18px; margin-bottom: 15px; color: var(--primary-blue);">3. Pending Username & Email Changes</h2>
    <?php if (empty($accountChanges)): ?>
        <p style="font-style: italic; color: var(--text-muted);">No pending username or email changes at this time.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left; font-weight: 700;">
                    <th style="padding: 12px 10px;">Driver</th>
                    <th style="padding: 12px 10px;">Credential</th>
                    <th style="padding: 12px 10px;">Current Value</th>
                    <th style="padding: 12px 10px;">Requested Value</th>
                    <th style="padding: 12px 10px;">Actions / Rejection reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accountChanges as $req): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 15px 10px;">
                            <strong><?php echo htmlspecialchars($req['user_name']); ?></strong><br>
                            <small style="color: var(--text-muted);"><?php echo htmlspecialchars($req['user_email']); ?></small>
                        </td>
                        <td style="padding: 15px 10px; text-transform: uppercase; font-weight: 600; color: #475569;">
                            <?php echo htmlspecialchars($req['change_type']); ?>
                        </td>
                        <td style="padding: 15px 10px; color: var(--text-muted);">
                            <?php echo htmlspecialchars($req['old_value']); ?>
                        </td>
                        <td style="padding: 15px 10px; font-weight: 600; color: #1E3A8A;">
                            <?php echo htmlspecialchars($req['requested_value']); ?>
                        </td>
                        <td style="padding: 15px 10px;">
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <form action="" method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                    <input type="hidden" name="action" value="approve_account">
                                    <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                    <button type="submit" style="padding: 6px 12px; background: #22C55E; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px; width: 100%;">Approve</button>
                                </form>
                                
                                <form action="" method="POST" style="margin:0; display: flex; gap: 8px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                    <input type="hidden" name="action" value="reject_account">
                                    <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                    <input type="text" name="rejection_reason" placeholder="Enter reason for rejection" required style="padding: 6px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 12px; flex: 1;">
                                    <button type="submit" style="padding: 6px 12px; background: #EF4444; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px;">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
