<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminVehicleVerification.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vehicles.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!AuthHelper::validateCsrfToken($csrfToken)) {
    $_SESSION['admin_vehicle_flash'] = ['message' => 'CSRF verification failed.'];
    header('Location: vehicles.php');
    exit;
}

$vehicleId = (int)($_POST['vehicle_id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($vehicleId <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
    $_SESSION['admin_vehicle_flash'] = ['message' => 'Invalid parameters.'];
    header('Location: vehicles.php');
    exit;
}

try {
    $model = new AdminVehicleVerification();
    if ($status === 'approved') {
        $model->approveVehicle($vehicleId);
        $msg = "Vehicle #$vehicleId approved.";
    } else {
        $model->rejectVehicle($vehicleId);
        $msg = "Vehicle #$vehicleId rejected.";
    }
    $_SESSION['admin_vehicle_flash'] = ['message' => $msg];
} catch (Throwable $e) {
    $_SESSION['admin_vehicle_flash'] = ['message' => 'Error: ' . $e->getMessage()];
}

header('Location: vehicles.php');
exit;
