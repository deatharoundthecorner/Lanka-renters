<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/ReplacementRequest.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: replacement_requests.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!AuthHelper::validateCsrfToken($csrfToken)) {
    $_SESSION['admin_rep_flash'] = ['message' => 'CSRF verification failed.'];
    header('Location: replacement_requests.php');
    exit;
}

$requestId = (int)($_POST['request_id'] ?? 0);
$replacementVehicleId = (int)($_POST['replacement_vehicle_id'] ?? 0);
$remarks = trim($_POST['admin_remarks'] ?? '');

if ($requestId <= 0 || $replacementVehicleId <= 0) {
    $_SESSION['admin_rep_flash'] = ['message' => 'Please select a valid replacement vehicle.'];
    header('Location: replacement_requests.php');
    exit;
}

try {
    $model = new ReplacementRequest();
    $model->assignReplacement($requestId, $replacementVehicleId, $remarks, 'approved');
    $_SESSION['admin_rep_flash'] = ['message' => "Replacement request #$requestId approved and vehicle assigned."];
} catch (Throwable $e) {
    $_SESSION['admin_rep_flash'] = ['message' => 'Error: ' . $e->getMessage()];
}

header('Location: replacement_requests.php');
exit;
