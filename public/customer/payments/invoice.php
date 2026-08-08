<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
$bookingId = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$target = $bookingId ? 'payments/summary.php?booking_id=' . (int) $bookingId : 'payments/index.php';
header('Location: ' . customer_url($target), true, 302);
exit;
