<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/Settlement.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settlements.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!AuthHelper::validateCsrfToken($csrfToken)) {
    $_SESSION['admin_set_flash'] = ['message' => 'CSRF verification failed.'];
    header('Location: settlements.php');
    exit;
}

$ownerId = (int)($_POST['owner_id'] ?? 0);
$bookingId = (int)($_POST['booking_id'] ?? 0);
$grossAmount = (float)($_POST['gross_amount'] ?? 0.0);

if ($ownerId <= 0 || $bookingId <= 0 || $grossAmount <= 0) {
    $_SESSION['admin_set_flash'] = ['message' => 'Invalid settlement parameters.'];
    header('Location: settlements.php');
    exit;
}

try {
    $model = new Settlement();
    $model->processSettlement($ownerId, $bookingId, $grossAmount, 'completed');
    $_SESSION['admin_set_flash'] = ['message' => "Settlement for Booking #$bookingId marked as completed."];
} catch (Throwable $e) {
    $_SESSION['admin_set_flash'] = ['message' => 'Error: ' . $e->getMessage()];
}

header('Location: settlements.php');
exit;
