<?php

require_once __DIR__ . '/_bootstrap.php';

$controller = new CustomerController();
$viewData = $controller->dashboard();

define('CUSTOMER_DASHBOARD_VIEW', true);
require __DIR__ . '/dashboard/index.php';
