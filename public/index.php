<?php
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();

if (AuthHelper::isLoggedIn()) {
    $user = AuthHelper::getCurrentUser();
    switch ($user['role'] ?? '') {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'owner':
            header("Location: owner/dashboard.php");
            break;
        case 'driver':
            header("Location: driver/dashboard.php");
            break;
        case 'customer':
            header("Location: customer/dashboard.php");
            break;
        default:
            header("Location: login.php");
            break;
    }
} else {
    header("Location: login.php");
}
exit();
