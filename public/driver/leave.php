<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/DriverLeave.php';

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

// Retrieve secure driver profile
$dashboardResult = $driverController->dashboard();
if (!$dashboardResult['success']) {
    $error = "Failed to load driver profile.";
    $driverId = 0;
} else {
    $driverId = $dashboardResult['profile']['id'];
}

// 1. Handle leave request submissions (Create)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_leave') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $result = $driverController->requestLeave($_POST);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

// 2. Handle leave cancel submissions (Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_leave') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $leaveId = (int)($_POST['leave_id'] ?? 0);
        $result = $driverController->cancelLeave($leaveId);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

// 3. Handle leave edit submissions (Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_leave') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $leaveId = (int)($_POST['leave_id'] ?? 0);
        $result = $driverController->editLeave($leaveId, $_POST);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

// 4. Resolve edit leave request context (GET param)
$editLeave = null;
if (isset($_GET['edit_id']) && $driverId > 0) {
    $editId = (int)$_GET['edit_id'];
    $leaveModel = new DriverLeave();
    $fetchedLeave = $leaveModel->getById($editId);
    if ($fetchedLeave && $fetchedLeave['driver_id'] === $driverId && $fetchedLeave['status'] === 'pending') {
        $editLeave = $fetchedLeave;
    }
}

// Fetch driver details and leave history (Read)
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
        <!-- Leave Form Card (Create or Update state) -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title"><?php echo $editLeave ? 'Edit Leave Request' : 'Submit Leave Request'; ?></h2>
            <form action="leave.php<?php echo $editLeave ? '?edit_id=' . $editLeave['id'] : ''; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                <input type="hidden" name="action" value="<?php echo $editLeave ? 'edit_leave' : 'request_leave'; ?>">
                <?php if ($editLeave): ?>
                    <input type="hidden" name="leave_id" value="<?php echo htmlspecialchars($editLeave['id']); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $editLeave ? htmlspecialchars($editLeave['start_date']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $editLeave ? htmlspecialchars($editLeave['end_date']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="reason" class="form-label">Reason / Comments</label>
                    <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="Brief explanation for your leave..." required><?php echo $editLeave ? htmlspecialchars($editLeave['reason']) : ''; ?></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-blue" style="flex: 1.5;"><?php echo $editLeave ? 'Save Changes' : 'Submit Request'; ?></button>
                    <?php if ($editLeave): ?>
                        <a href="leave.php" class="btn-secondary" style="flex: 1; text-align: center; text-decoration: none; padding: 10px 0;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Leave History (Read & Delete state) -->
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
                            <th>Actions</th>
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
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <!-- Edit Action (Only available if status is pending) -->
                                        <?php if ($leave['status'] === 'pending'): ?>
                                            <a href="leave.php?edit_id=<?php echo $leave['id']; ?>" class="btn-secondary" style="padding: 4px 8px; font-size: 11px; text-decoration: none;">Edit</a>
                                            
                                            <!-- Cancel/Delete Action -->
                                            <form action="" method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to cancel this leave request?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                                <input type="hidden" name="action" value="cancel_leave">
                                                <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                                <button type="submit" style="padding: 4px 8px; font-size: 11px; background-color: var(--danger); color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 11px;">Locked</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
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
