<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminPaymentVerification.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: payments.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!AuthHelper::validateCsrfToken($csrfToken)) {
    $_SESSION['admin_payment_flash'] = ['message' => 'CSRF verification failed.'];
    header('Location: payments.php');
    exit;
}

$paymentId = (int)($_POST['payment_id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($paymentId <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
    $_SESSION['admin_payment_flash'] = ['message' => 'Invalid parameters.'];
    header('Location: payments.php');
    exit;
}

try {
    $model = new AdminPaymentVerification();
    if ($status === 'approved') {
        $model->approvePayment($paymentId);
        $msg = "Payment #$paymentId approved and booking confirmed.";
    } else {
        $model->rejectPayment($paymentId);
        $msg = "Payment #$paymentId rejected.";
    }
    $_SESSION['admin_payment_flash'] = ['message' => $msg];
} catch (Throwable $e) {
    $_SESSION['admin_payment_flash'] = ['message' => 'Error: ' . $e->getMessage()];
}

header('Location: payments.php');
exit;
