<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminOwnerVerification.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vehicle_owners.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!AuthHelper::validateCsrfToken($csrfToken)) {
    $_SESSION['admin_owner_flash'] = ['message' => 'CSRF verification failed.'];
    header('Location: vehicle_owners.php');
    exit;
}

$ownerId = (int)($_POST['owner_id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($ownerId <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
    $_SESSION['admin_owner_flash'] = ['message' => 'Invalid parameters.'];
    header('Location: vehicle_owners.php');
    exit;
}

try {
    $model = new AdminOwnerVerification();
    if ($status === 'approved') {
        $model->approveOwner($ownerId);
        $msg = "Vehicle Owner #$ownerId approved successfully.";
    } else {
        $model->rejectOwner($ownerId);
        $msg = "Vehicle Owner #$ownerId rejected.";
    }
    $_SESSION['admin_owner_flash'] = ['message' => $msg];
} catch (Throwable $e) {
    $_SESSION['admin_owner_flash'] = ['message' => 'Error: ' . $e->getMessage()];
}

header('Location: vehicle_owners.php');
exit;
