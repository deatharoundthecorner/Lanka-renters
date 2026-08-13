<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/EmailLog.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Email Logs";

try {
    $model = new EmailLog();
    $emailLogs = $model->getAll();
} catch (Throwable $e) {
    error_log("Email Logs Error: " . $e->getMessage());
    $emailLogs = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Logs - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">System Email Notification Logs</h1>
                <p class="page-subtitle">Monitor automated system notifications, email delivery logs, and delivery audit trails.</p>
            </div>

            <div class="card">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Email Notifications Log</h3>
                    <span class="badge badge-approved"><?= count($emailLogs) ?> Total Logs</span>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Role</th>
                                <th>Subject</th>
                                <th>Booking ID</th>
                                <th>Sent Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($emailLogs)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 24px;">No email log records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($emailLogs as $log): ?>
                                    <tr>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-primary-text"><?= $escape($log['recipient_name']) ?></span>
                                                <span class="cell-secondary-text"><?= $escape($log['recipient_email']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-blue"><?= $escape(ucfirst($log['recipient_type'])) ?></span></td>
                                        <td><?= $escape($log['subject']) ?></td>
                                        <td><span class="cell-secondary-text"><?= $log['booking_id'] ? 'BKG-' . (int)$log['booking_id'] : 'N/A' ?></span></td>
                                        <td><?= $escape($log['sent_at']) ?></td>
                                        <td>
                                            <span class="badge <?= $log['status'] === 'sent' ? 'badge-approved' : 'badge-pending' ?>">
                                                <?= $escape(ucfirst($log['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
