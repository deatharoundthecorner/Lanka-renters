<?php

require_once dirname(__DIR__) . '/_bootstrap.php';

$query = http_build_query($_GET);
header('Location: ' . customer_url('bookings/index.php' . ($query !== '' ? '?' . $query : '')), true, 302);
exit;
