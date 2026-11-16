<?php

require_once __DIR__ . '/_bootstrap.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    header('Allow: POST');
    customer_error_response(405, 'Method not allowed', 'Please use the Customer menu to log out safely.');
}

AuthHelper::logout();
$customerBaseUrl = rtrim(customer_base_url(), '/');
$lastSlash = strrpos($customerBaseUrl, '/');
$publicBaseUrl = $lastSlash === false ? '' : substr($customerBaseUrl, 0, $lastSlash);
header('Location: ' . $publicBaseUrl . '/login.php', true, 302);
exit;
