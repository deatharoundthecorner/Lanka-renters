<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminCustomerVerification.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!AuthHelper::validateCsrfToken($csrfToken)) {
    $_SESSION['admin_customer_verification_flash'] = [
        'message' => 'CSRF verification failed. Please try again.'
    ];
    header('Location: users.php');
    exit;
}

$customerId = (int)($_POST['customer_id'] ?? 0);
$status = $_POST['verification_status'] ?? '';

if ($customerId <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
    $_SESSION['admin_customer_verification_flash'] = [
        'message' => 'Invalid parameters submitted.'
    ];
    header('Location: users.php');
    exit;
}

try {
    $verification = new AdminCustomerVerification();
    $admin = AuthHelper::getCurrentUser();
    $adminId = $admin['id'] ?? null;

    if ($status === 'approved') {
        $verification->approveCustomer($customerId, $adminId);
        $msg = "Customer #$customerId approved successfully.";
    } else {
        $reason = trim($_POST['rejection_reason'] ?? 'Documentation incomplete');
        $verification->rejectCustomer($customerId, $adminId, $reason);
        $msg = "Customer #$customerId rejected.";
    }

    $_SESSION['admin_customer_verification_flash'] = ['message' => $msg];
} catch (Throwable $e) {
    $_SESSION['admin_customer_verification_flash'] = ['message' => 'Error: ' . $e->getMessage()];
}

header('Location: users.php');
exit;
