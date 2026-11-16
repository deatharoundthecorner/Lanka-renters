<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminDriverVerification.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: drivers.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!AuthHelper::validateCsrfToken($csrfToken)) {
    $_SESSION['admin_driver_flash'] = ['message' => 'CSRF verification failed.'];
    header('Location: drivers.php');
    exit;
}

$driverId = (int)($_POST['driver_id'] ?? 0);
$status = $_POST['status'] ?? '';
$reason = trim($_POST['rejection_reason'] ?? '');

if ($driverId <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
    $_SESSION['admin_driver_flash'] = ['message' => 'Invalid parameters.'];
    header('Location: drivers.php');
    exit;
}

try {
    $model = new AdminDriverVerification();
    if ($status === 'approved') {
        $model->approveDriver($driverId);
        $msg = "Driver #$driverId verification approved.";
    } else {
        $model->rejectDriver($driverId, $reason);
        $msg = "Driver #$driverId verification rejected.";
    }
    $_SESSION['admin_driver_flash'] = ['message' => $msg];
} catch (Throwable $e) {
    $_SESSION['admin_driver_flash'] = ['message' => 'Error: ' . $e->getMessage()];
}

header('Location: drivers.php');
exit;
