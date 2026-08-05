<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();

// Secure check
if (!AuthHelper::isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$user = AuthHelper::getCurrentUser();
if (($user['role'] ?? '') !== 'customer') {
    AuthHelper::logout();
    header("Location: ../login.php");
    exit();
}

header("Location: select_driver.php");
exit();
