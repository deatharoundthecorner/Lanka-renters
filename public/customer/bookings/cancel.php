<?php

require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerBookingController.php';

$controller = new CustomerBookingController();
$result = $controller->cancel($_POST, strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')));

if ($result['method_not_allowed']) {
    header('Allow: POST');
    customer_error_response(405, 'Method not allowed', 'Booking cancellation requires a confirmed POST request.');
}

header('Location: ' . customer_url($result['redirect']), true, 303);
exit;
