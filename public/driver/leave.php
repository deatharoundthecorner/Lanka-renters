<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();

// Localized driver auth check
if (!AuthHelper::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user = AuthHelper::getCurrentUser();
if (($user['role'] ?? '') !== 'driver') {
    AuthHelper::logout();
    header("Location: login.php");
    exit();
}

$driverController = new DriverController();

$error = '';
$success = '';

// Handle leave request submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_leave') {
    $result = $driverController->requestLeave($_POST);
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['error'];
    }
}

// Fetch driver details and leave history
$dashboardResult = $driverController->dashboard();
$driverName = $dashboardResult['success'] ? $dashboardResult['profile']['name'] : 'Driver';

$leaveHistoryResult = $driverController->viewLeaveHistory();
$leaves = $leaveHistoryResult['success'] ? $leaveHistoryResult['leaves'] : [];

// Page config
$pageTitle = "Request Leave - Lanka Renters";
$activePage = "leave";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Leave Requests</h2>
            <p class="welcome-subtitle">Submit and monitor your vacation or time-off status.</p>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.2fr 2fr; gap: 30px; align-items: flex-start;">
        <!-- Leave Request Form -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title">Submit Leave Request</h2>
            <form action="" method="POST">
                <input type="hidden" name="action" value="request_leave">
                
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="reason" class="form-label">Reason / Comments</label>
                    <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="Brief explanation for your leave..." required></textarea>
                </div>

                <button type="submit" class="btn-blue" style="width: 100%; margin-top: 10px;">Submit Request</button>
            </form>
        </div>

        <!-- Leave History -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title">Leave History Log</h2>
            <?php if (empty($leaves)): ?>
                <p style="font-style: italic; color: var(--text-muted);">No leave requests found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Requested On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($leave['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($leave['end_date']); ?></td>
                                <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                <td>
                                    <span class="status-pill status-<?php echo htmlspecialchars($leave['status']); ?>">
                                        <?php echo htmlspecialchars($leave['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($leave['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
