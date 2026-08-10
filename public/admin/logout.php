<?php
/**
 * Lanka Renters - Admin Logout
 * Validates CSRF token, destroys session, redirects to login page.
 */
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();

// Only accept POST requests with a valid CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    AuthHelper::logout();
}

// Redirect to login in all cases (safe fallback)
header("Location: ../login.php");
exit();
