<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/Guarantor.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$adminUser = AuthHelper::getCurrentUser();
$csrfToken = AuthHelper::getCsrfToken();
$flash = $_SESSION['admin_guarantor_flash'] ?? null;
unset($_SESSION['admin_guarantor_flash']);

$guarantorModel = new Guarantor();
$statusFilter = $_GET['status'] ?? null;
if (!in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
    $statusFilter = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['admin_guarantor_flash'] = ['type' => 'error', 'message' => 'CSRF verification failed.'];
        header('Location: guarantors.php');
        exit;
    }

    $guarantorId = (int)($_POST['guarantor_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $remarks = (string)($_POST['remarks'] ?? '');

    if ($guarantorId <= 0 || !in_array($action, ['approved', 'rejected'], true)) {
        $_SESSION['admin_guarantor_flash'] = ['type' => 'error', 'message' => 'Invalid review submission parameters.'];
        header('Location: guarantors.php');
        exit;
    }

    try {
        $guarantorModel->reviewGuarantor($guarantorId, (int)$adminUser['id'], $action, $remarks);
        $_SESSION['admin_guarantor_flash'] = [
            'type' => 'success',
            'message' => "Guarantor #$guarantorId was marked as " . strtoupper($action) . " successfully."
        ];
    } catch (Throwable $e) {
        $_SESSION['admin_guarantor_flash'] = [
            'type' => 'error',
            'message' => 'Error updating guarantor status: ' . $e->getMessage()
        ];
    }

    header('Location: guarantors.php' . ($statusFilter ? '?status=' . urlencode($statusFilter) : ''));
    exit;
}

$guarantors = $guarantorModel->getAllForAdmin($statusFilter);
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Guarantors - Lanka Renters Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .badge-pending { background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-approved { background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-rejected { background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .guarantor-table th, .guarantor-table td { padding: 12px; border-bottom: 1px solid #e9ecef; }
        .modal-bg { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 999; }
        .modal-card { background: #fff; padding: 24px; border-radius: 8px; max-width: 500px; width: 100%; box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <h1>Customer Guarantor Verification</h1>
            <div class="user-info">
                <span>Welcome, <?= $escape($adminUser['name'] ?? 'Admin') ?></span>
            </div>
        </header>

        <div class="content-wrapper" style="padding: 24px;">
            <?php if ($flash): ?>
                <div class="alert <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-danger' ?>" style="padding: 12px 16px; margin-bottom: 20px; border-radius: 4px; background: <?= $flash['type'] === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $flash['type'] === 'success' ? '#155724' : '#721c24' ?>;">
                    <?= $escape($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="card" style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h2 style="margin: 0; font-size: 1.25rem;">Submitted Guarantors</h2>
                        <p style="margin: 4px 0 0; color: #6c757d; font-size: 0.9rem;">Review customer guarantors responsible for vehicle rental agreements.</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="guarantors.php" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-outline' ?>" style="padding: 6px 12px; border: 1px solid #007bff; border-radius: 4px; text-decoration: none; <?= !$statusFilter ? 'background: #007bff; color: #fff;' : 'color: #007bff;' ?>">All</a>
                        <a href="guarantors.php?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-outline' ?>" style="padding: 6px 12px; border: 1px solid #007bff; border-radius: 4px; text-decoration: none; <?= $statusFilter === 'pending' ? 'background: #007bff; color: #fff;' : 'color: #007bff;' ?>">Pending</a>
                        <a href="guarantors.php?status=approved" class="btn btn-sm <?= $statusFilter === 'approved' ? 'btn-primary' : 'btn-outline' ?>" style="padding: 6px 12px; border: 1px solid #007bff; border-radius: 4px; text-decoration: none; <?= $statusFilter === 'approved' ? 'background: #007bff; color: #fff;' : 'color: #007bff;' ?>">Approved</a>
                        <a href="guarantors.php?status=rejected" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-primary' : 'btn-outline' ?>" style="padding: 6px 12px; border: 1px solid #007bff; border-radius: 4px; text-decoration: none; <?= $statusFilter === 'rejected' ? 'background: #007bff; color: #fff;' : 'color: #007bff;' ?>">Rejected</a>
                    </div>
                </div>

                <?php if (empty($guarantors)): ?>
                    <p style="text-align: center; color: #6c757d; padding: 40px 0;">No guarantor records found<?= $statusFilter ? " with status '$statusFilter'" : '' ?>.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="guarantor-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th>Customer</th>
                                    <th>Guarantor Name</th>
                                    <th>NIC / Passport</th>
                                    <th>Relationship</th>
                                    <th>Contact</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guarantors as $g): ?>
                                    <tr>
                                        <td>
                                            <strong><?= $escape($g['customer_name']) ?></strong><br>
                                            <small style="color: #6c757d;"><?= $escape($g['customer_email']) ?></small>
                                        </td>
                                        <td><strong><?= $escape($g['full_name']) ?></strong></td>
                                        <td><code><?= $escape($g['nic_passport']) ?></code></td>
                                        <td><?= $escape($g['relationship']) ?></td>
                                        <td>
                                            <?= $escape($g['phone']) ?><br>
                                            <?php if (!empty($g['email'])): ?><small style="color: #6c757d;"><?= $escape($g['email']) ?></small><?php endif; ?>
                                        </td>
                                        <td style="max-width: 200px; font-size: 0.85rem;"><?= nl2br($escape($g['address'])) ?></td>
                                        <td>
                                            <span class="badge-<?= $escape($g['verification_status']) ?>">
                                                <?= strtoupper($escape($g['verification_status'])) ?>
                                            </span>
                                            <?php if (!empty($g['admin_remarks'])): ?>
                                                <div style="font-size: 0.75rem; color: #6c757d; margin-top: 4px;">
                                                    <em><?= $escape($g['admin_remarks']) ?></em>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 6px; flex-direction: column;">
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Approve this guarantor?');">
                                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                                    <input type="hidden" name="guarantor_id" value="<?= (int)$g['id'] ?>">
                                                    <input type="hidden" name="action" value="approved">
                                                    <button type="submit" style="background: #28a745; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; width: 100%;">Approve</button>
                                                </form>

                                                <button type="button" onclick="openRejectModal(<?= (int)$g['id'] ?>, '<?= $escape(addslashes($g['full_name'])) ?>')" style="background: #dc3545; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; width: 100%;">Reject...</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal-bg" style="display: none;">
    <div class="modal-card">
        <h3 style="margin-top: 0;">Reject Guarantor</h3>
        <p id="rejectModalDesc" style="color: #6c757d;"></p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" id="modalGuarantorId" name="guarantor_id" value="">
            <input type="hidden" name="action" value="rejected">
            
            <label style="display: block; margin-bottom: 6px; font-weight: 600;">Rejection Remarks / Reason:</label>
            <textarea name="remarks" rows="3" required placeholder="Specify why this guarantor cannot be approved (e.g. invalid NIC, unreachable phone)..." style="width: 100%; box-sizing: border-box; padding: 8px; margin-bottom: 16px; border: 1px solid #ced4da; border-radius: 4px;"></textarea>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" onclick="closeRejectModal()" style="background: #6c757d; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button type="submit" style="background: #dc3545; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id, name) {
    document.getElementById('modalGuarantorId').value = id;
    document.getElementById('rejectModalDesc').innerText = 'State the reason for rejecting ' + name + ':';
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>
</body>
</html>
