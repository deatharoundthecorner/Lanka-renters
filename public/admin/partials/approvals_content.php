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
<header class="page-header" style="margin-bottom: 25px;">
    <div>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--primary-hover);">Approvals Center</h1>
        <p style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">Review and decide on driver document replacements, profile changes, and credentials updates.</p>
    </div>
</header>

<!-- Alerts -->
<?php if (!empty($success)): ?>
    <div style="background-color: #DCFCE7; color: #16A34A; padding: 15px; border-radius: 8px; font-weight: 600; margin-bottom: 24px; border: 1px solid #BBF7D0; font-size: 13.5px;">
        ✓ <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div style="background-color: #FEE2E2; color: #DC2626; padding: 15px; border-radius: 8px; font-weight: 600; margin-bottom: 24px; border: 1px solid #FCA5A5; font-size: 13.5px;">
        ✗ <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Section 1: Pending Documents -->
<section class="panel-card wide-card" style="margin-bottom: 35px; padding: 25px; background: white; border-radius: 8px; border: 1px solid var(--border);">
    <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: var(--primary); display: flex; align-items: center; gap: 8px;">
        📄 1. Driver Document Approvals
    </h2>
    <?php if (empty($documents)): ?>
        <p style="font-style: italic; color: var(--text-muted); font-size: 13px; margin-top: 10px;">No pending driver document replacements at this time.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 600px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border); text-align: left; font-weight: 700; color: var(--text-muted);">
                        <th style="padding: 12px 10px;">Driver Details</th>
                        <th style="padding: 12px 10px;">Document Type</th>
                        <th style="padding: 12px 10px;">No / Expiry</th>
                        <th style="padding: 12px 10px;">Version</th>
                        <th style="padding: 12px 10px; text-align: right;">Actions & Decisions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 15px 10px;">
                                <strong style="color: var(--text-main);"><?php echo htmlspecialchars($doc['user_name']); ?></strong><br>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($doc['user_email']); ?></small>
                            </td>
                            <td style="padding: 15px 10px; text-transform: uppercase; font-weight: 700; color: var(--primary);">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $doc['document_type'])); ?>
                            </td>
                            <td style="padding: 15px 10px;">
                                <div>No: <?php echo htmlspecialchars($doc['document_number']); ?></div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Exp: <?php echo htmlspecialchars($doc['expiry_date']); ?></div>
                            </td>
                            <td style="padding: 15px 10px; font-weight: 700; color: var(--text-main);">
                                v<?php echo htmlspecialchars($doc['version']); ?>
                            </td>
                            <td style="padding: 15px 10px; text-align: right;">
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <?php
                                        $physicalPath = dirname(dirname(dirname(__DIR__))) . '/public/' . $doc['file_path'];
                                        if (file_exists($physicalPath) && is_file($physicalPath)):
                                        ?>
                                            <a href="../<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" style="padding: 5px 10px; background: #DBEAFE; color: #1E40AF; border-radius: 4px; text-decoration: none; font-weight: 700; font-size: 11.5px;">View File ↗</a>
                                        <?php else: ?>
                                            <span style="padding: 5px 10px; background: #F1F5F9; color: var(--text-muted); border-radius: 4px; font-weight: 700; font-size: 11.5px; cursor: not-allowed; opacity: 0.7;" title="Physical file is missing from server">File Missing</span>
                                        <?php endif; ?>
                                        
                                        <form action="" method="POST" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                            <input type="hidden" name="action" value="approve_doc">
                                            <input type="hidden" name="id" value="<?php echo $doc['id']; ?>">
                                            <button type="submit" style="padding: 5px 10px; background: var(--success); color: white; border: none; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 11.5px;">Approve</button>
                                        </form>
                                    </div>
                                    
                                    <form action="" method="POST" style="margin:0; display: flex; gap: 6px; justify-content: flex-end; width: 100%; max-width: 320px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                        <input type="hidden" name="action" value="reject_doc">
                                        <input type="hidden" name="id" value="<?php echo $doc['id']; ?>">
                                        <input type="text" name="rejection_reason" placeholder="Rejection reason is required" required style="padding: 5px 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 11.5px; flex: 1;">
                                        <button type="submit" style="padding: 5px 10px; background: var(--danger); color: white; border: none; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 11.5px;">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- Section 2: Pending Profile Changes -->
<section class="panel-card wide-card" style="margin-bottom: 35px; padding: 25px; background: white; border-radius: 8px; border: 1px solid var(--border);">
    <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: var(--primary); display: flex; align-items: center; gap: 8px;">
        👤 2. Driver Profile Change Approvals
    </h2>
    <?php if (empty($profileChanges)): ?>
        <p style="font-style: italic; color: var(--text-muted); font-size: 13px; margin-top: 10px;">No pending driver profile details modifications at this time.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 600px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border); text-align: left; font-weight: 700; color: var(--text-muted);">
                        <th style="padding: 12px 10px;">Driver Details</th>
                        <th style="padding: 12px 10px;">Field Name</th>
                        <th style="padding: 12px 10px;">Current Value</th>
                        <th style="padding: 12px 10px;">Requested Value</th>
                        <th style="padding: 12px 10px; text-align: right;">Actions & Decisions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profileChanges as $req): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 15px 10px;">
                                <strong style="color: var(--text-main);"><?php echo htmlspecialchars($req['user_name']); ?></strong><br>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($req['user_email']); ?></small>
                            </td>
                            <td style="padding: 15px 10px; text-transform: capitalize; font-weight: 700; color: #475569;">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $req['field_name'])); ?>
                            </td>
                            <td style="padding: 15px 10px; color: var(--text-muted);">
                                <?php echo htmlspecialchars($req['old_value'] !== '' && $req['old_value'] !== null ? $req['old_value'] : '(Empty)'); ?>
                            </td>
                            <td style="padding: 15px 10px; font-weight: 700; color: #1E3A8A;">
                                <?php echo htmlspecialchars($req['requested_value'] !== '' && $req['requested_value'] !== null ? $req['requested_value'] : '(Empty)'); ?>
                            </td>
                            <td style="padding: 15px 10px; text-align: right;">
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                    <form action="" method="POST" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                        <input type="hidden" name="action" value="approve_profile">
                                        <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                        <button type="submit" style="padding: 5px 12px; background: var(--success); color: white; border: none; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 11.5px; width: 100px;">Approve</button>
                                    </form>
                                    
                                    <form action="" method="POST" style="margin:0; display: flex; gap: 6px; justify-content: flex-end; width: 100%; max-width: 320px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                        <input type="hidden" name="action" value="reject_profile">
                                        <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                        <input type="text" name="rejection_reason" placeholder="Rejection reason is required" required style="padding: 5px 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 11.5px; flex: 1;">
                                        <button type="submit" style="padding: 5px 10px; background: var(--danger); color: white; border: none; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 11.5px;">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- Section 3: Pending Credentials Changes -->
<section class="panel-card wide-card" style="margin-bottom: 25px; padding: 25px; background: white; border-radius: 8px; border: 1px solid var(--border);">
    <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: var(--primary); display: flex; align-items: center; gap: 8px;">
        🔑 3. Driver Account Credentials Approvals
    </h2>
    <?php if (empty($accountChanges)): ?>
        <p style="font-style: italic; color: var(--text-muted); font-size: 13px; margin-top: 10px;">No pending driver credentials modifications at this time.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 600px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border); text-align: left; font-weight: 700; color: var(--text-muted);">
                        <th style="padding: 12px 10px;">Driver Details</th>
                        <th style="padding: 12px 10px;">Credential Field</th>
                        <th style="padding: 12px 10px;">Current Value</th>
                        <th style="padding: 12px 10px;">Requested Value</th>
                        <th style="padding: 12px 10px; text-align: right;">Actions & Decisions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accountChanges as $req): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 15px 10px;">
                                <strong style="color: var(--text-main);"><?php echo htmlspecialchars($req['user_name']); ?></strong><br>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($req['user_email']); ?></small>
                            </td>
                            <td style="padding: 15px 10px; text-transform: uppercase; font-weight: 700; color: #475569;">
                                <?php 
                                    $labelMap = ['display_name' => 'Display Name', 'username' => 'Username', 'email' => 'Email'];
                                    echo htmlspecialchars($labelMap[$req['change_type']] ?? $req['change_type']); 
                                ?>
                            </td>
                            <td style="padding: 15px 10px; color: var(--text-muted);">
                                <?php echo htmlspecialchars($req['old_value'] !== '' && $req['old_value'] !== null ? $req['old_value'] : '(Empty)'); ?>
                            </td>
                            <td style="padding: 15px 10px; font-weight: 700; color: #1E3A8A;">
                                <?php echo htmlspecialchars($req['requested_value']); ?>
                            </td>
                            <td style="padding: 15px 10px; text-align: right;">
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                    <form action="" method="POST" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                        <input type="hidden" name="action" value="approve_account">
                                        <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                        <button type="submit" style="padding: 5px 12px; background: var(--success); color: white; border: none; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 11.5px; width: 100px;">Approve</button>
                                    </form>
                                    
                                    <form action="" method="POST" style="margin:0; display: flex; gap: 6px; justify-content: flex-end; width: 100%; max-width: 320px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                        <input type="hidden" name="action" value="reject_account">
                                        <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                        <input type="text" name="rejection_reason" placeholder="Rejection reason is required" required style="padding: 5px 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 11.5px; flex: 1;">
                                        <button type="submit" style="padding: 5px 10px; background: var(--danger); color: white; border: none; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 11.5px;">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
