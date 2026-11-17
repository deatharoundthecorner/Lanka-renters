<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/controllers/OwnerDriverChangeController.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$controller = new OwnerDriverChangeController();
$actionResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionResult = $controller->handleAction($_POST);
}

$dataResult = $controller->getData();
$requests = $dataResult['success'] ? $dataResult['requests'] : [];
$summary  = $dataResult['success'] ? $dataResult['summary'] : ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
$filter   = $dataResult['filter'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Change Requests - LankaRenters Owner</title>
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
    <link rel="stylesheet" href="../assets/css/lr-tokens.css">
    <link rel="stylesheet" href="../assets/css/lr-shell.css">
    <link rel="stylesheet" href="../assets/css/lr-components.css">
    <link rel="stylesheet" href="includes/assets/css/owner-canonical.css">
    <style>
        .badge-pending { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-approved { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-rejected { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-cancelled { background-color: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
        .tab-btn { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500; color: #4b5563; background: #f3f4f6; }
        .tab-btn.active { background: #1e3a8a; color: #ffffff; }
        .action-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .action-modal.open { display: flex; }
        .modal-box { background: #ffffff; border-radius: 16px; max-width: 480px; width: 90%; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="dashboard-layout dashboard-wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <main class="main-content">
            <!-- Header -->
            <section class="vehicles-header welcome-container" style="margin-bottom: 24px;">
                <div class="vehicles-title">
                    <h1>Driver Change Requests</h1>
                    <p>Review customer requests to change assigned drivers on active rentals for your vehicle fleet.</p>
                </div>
            </section>

            <!-- Feedback Alert -->
            <?php if ($actionResult): ?>
                <div style="padding: 14px 20px; border-radius: 14px; margin-bottom: 20px; <?= $actionResult['success'] ? 'background-color:#f0fdf4; border:1px solid #86efac; color:#166534;' : 'background-color:#fef2f2; border:1px solid #fca5a5; color:#991b1b;' ?>">
                    <?= htmlspecialchars($actionResult['success'] ? $actionResult['message'] : $actionResult['error']) ?>
                </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <section class="overview-stats" aria-label="Driver change request statistics" style="margin-bottom: 28px;">
                <div class="stats-grid">
                    <article class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <p class="stat-label">Total Requests</p>
                                <p class="stat-value"><?= (int)($summary['total'] ?? 0) ?></p>
                            </div>
                            <div class="stat-icon-bubble bubble-blue">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                        </div>
                        <p class="stat-meta">Fleet requests log</p>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <p class="stat-label">Pending Review</p>
                                <p class="stat-value"><?= (int)($summary['pending'] ?? 0) ?></p>
                            </div>
                            <div class="stat-icon-bubble bubble-yellow">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                        </div>
                        <p class="stat-meta">Awaiting your approval</p>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <p class="stat-label">Approved</p>
                                <p class="stat-value"><?= (int)($summary['approved'] ?? 0) ?></p>
                            </div>
                            <div class="stat-icon-bubble bubble-green">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                        </div>
                        <p class="stat-meta">Driver reassigned</p>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <p class="stat-label">Rejected</p>
                                <p class="stat-value"><?= (int)($summary['rejected'] ?? 0) ?></p>
                            </div>
                            <div class="stat-icon-bubble" style="background:#fee2e2; color:#dc2626;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </div>
                        </div>
                        <p class="stat-meta">Assignment retained</p>
                    </article>
                </div>
            </section>

            <!-- Filter Tabs -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                <a href="driver-changes.php" class="tab-btn <?= $filter === null ? 'active' : '' ?>">All (<?= (int)$summary['total'] ?>)</a>
                <a href="driver-changes.php?status=pending" class="tab-btn <?= $filter === 'pending' ? 'active' : '' ?>">Pending (<?= (int)$summary['pending'] ?>)</a>
                <a href="driver-changes.php?status=approved" class="tab-btn <?= $filter === 'approved' ? 'active' : '' ?>">Approved (<?= (int)$summary['approved'] ?>)</a>
                <a href="driver-changes.php?status=rejected" class="tab-btn <?= $filter === 'rejected' ? 'active' : '' ?>">Rejected (<?= (int)$summary['rejected'] ?>)</a>
            </div>

            <!-- Requests Table / List -->
            <div class="card" style="padding: 0; overflow: hidden; border-radius: 16px;">
                <?php if (empty($requests)): ?>
                    <div style="padding: 48px; text-align: center; color: #6b7280;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 12px; display: block; opacity: 0.5;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 4px;">No driver change requests found</h3>
                        <p style="font-size: 14px; margin: 0;">There are no driver change requests matching this view.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                            <thead>
                                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #4b5563; font-weight: 600;">
                                    <th style="padding: 14px 16px;">Request</th>
                                    <th style="padding: 14px 16px;">Vehicle</th>
                                    <th style="padding: 14px 16px;">Customer</th>
                                    <th style="padding: 14px 16px;">Current Driver</th>
                                    <th style="padding: 14px 16px;">Requested Driver</th>
                                    <th style="padding: 14px 16px;">Reason</th>
                                    <th style="padding: 14px 16px;">Status</th>
                                    <th style="padding: 14px 16px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $r): ?>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 14px 16px; vertical-align: top;">
                                            <strong>#<?= (int)$r['id'] ?></strong><br>
                                            <span style="font-size: 12px; color: #6b7280;">Booking #<?= (int)$r['booking_id'] ?></span>
                                        </td>
                                        <td style="padding: 14px 16px; vertical-align: top;">
                                            <strong><?= htmlspecialchars($r['make'] . ' ' . $r['model']) ?></strong><br>
                                            <span style="font-size: 12px; color: #6b7280;"><?= htmlspecialchars($r['license_plate']) ?></span>
                                        </td>
                                        <td style="padding: 14px 16px; vertical-align: top;">
                                            <?= htmlspecialchars($r['customer_name']) ?><br>
                                            <span style="font-size: 12px; color: #6b7280;"><?= htmlspecialchars($r['customer_phone'] ?? '') ?></span>
                                        </td>
                                        <td style="padding: 14px 16px; vertical-align: top;">
                                            <span style="color: #4b5563;"><?= htmlspecialchars($r['current_driver_name']) ?></span>
                                        </td>
                                        <td style="padding: 14px 16px; vertical-align: top;">
                                            <strong style="color: #1e3a8a;"><?= htmlspecialchars($r['requested_driver_name']) ?></strong><br>
                                            <span style="font-size: 12px; color: #6b7280;">Rating: <?= number_format((float)($r['requested_driver_rating'] ?? 0), 1) ?> &star;</span>
                                        </td>
                                        <td style="padding: 14px 16px; vertical-align: top; max-width: 220px;">
                                            <p style="margin: 0; font-size: 13px; color: #374151;"><?= nl2br(htmlspecialchars($r['reason'])) ?></p>
                                            <?php if (!empty($r['owner_remarks'])): ?>
                                                <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280;"><em>Remarks: <?= htmlspecialchars($r['owner_remarks']) ?></em></p>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 14px 16px; vertical-align: top;">
                                            <?php
                                            $badgeClass = match ($r['status']) {
                                                'approved'  => 'badge-approved',
                                                'rejected'  => 'badge-rejected',
                                                'cancelled' => 'badge-cancelled',
                                                default     => 'badge-pending',
                                            };
                                            ?>
                                            <span style="display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600;" class="<?= $badgeClass ?>">
                                                <?= ucfirst($r['status']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px; vertical-align: top; text-align: right;">
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="openReviewModal(<?= (int)$r['id'] ?>, 'approve', '<?= htmlspecialchars(addslashes($r['requested_driver_name'])) ?>')" style="padding: 6px 12px; font-size: 13px; background: #166534; border: none; border-radius: 8px; color: #fff; cursor: pointer;">
                                                        Approve
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openReviewModal(<?= (int)$r['id'] ?>, 'reject', '<?= htmlspecialchars(addslashes($r['requested_driver_name'])) ?>')" style="padding: 6px 12px; font-size: 13px; background: #991b1b; border: none; border-radius: 8px; color: #fff; cursor: pointer;">
                                                        Reject
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span style="font-size: 12px; color: #9ca3af;"><?= htmlspecialchars(date('d M Y', strtotime($r['reviewed_at'] ?? $r['updated_at']))) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Review Action Modal -->
<div id="reviewModal" class="action-modal">
    <div class="modal-box">
        <h3 id="modalTitle" style="margin-top: 0; font-size: 18px; color: #111827; margin-bottom: 8px;">Review Request</h3>
        <p id="modalDesc" style="font-size: 14px; color: #6b7280; margin-bottom: 16px;"></p>
        <form method="POST" action="driver-changes.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(AuthHelper::getCsrfToken()) ?>">
            <input type="hidden" name="request_id" id="modalRequestId" value="">
            <input type="hidden" name="action" id="modalAction" value="">

            <label for="modalRemarks" style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">Owner Remarks (Optional)</label>
            <textarea name="remarks" id="modalRemarks" rows="3" style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 10px; font-size: 14px; margin-bottom: 16px;" placeholder="Add remarks for the customer..."></textarea>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeReviewModal()" style="padding: 8px 16px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; cursor: pointer;">Cancel</button>
                <button type="submit" id="modalSubmitBtn" style="padding: 8px 16px; border: none; border-radius: 8px; font-size: 14px; color: #fff; cursor: pointer;">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(requestId, action, driverName) {
    document.getElementById('modalRequestId').value = requestId;
    document.getElementById('modalAction').value = action;
    const title = document.getElementById('modalTitle');
    const desc = document.getElementById('modalDesc');
    const submitBtn = document.getElementById('modalSubmitBtn');

    if (action === 'approve') {
        title.innerText = 'Approve Driver Change';
        desc.innerText = 'Are you sure you want to approve this request? The booking assignment will transactionally change to ' + driverName + '.';
        submitBtn.innerText = 'Approve Assignment';
        submitBtn.style.background = '#166534';
    } else {
        title.innerText = 'Reject Driver Change';
        desc.innerText = 'Are you sure you want to reject this request? The current driver assignment will remain unchanged.';
        submitBtn.innerText = 'Reject Request';
        submitBtn.style.background = '#991b1b';
    }
    document.getElementById('reviewModal').classList.add('open');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.remove('open');
}
</script>

</body>
</html>
