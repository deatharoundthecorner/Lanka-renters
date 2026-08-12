<?php

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/controllers/CustomerPortalController.php';

$viewData = (new CustomerPortalController())->dashboardPage();

define('CUSTOMER_DASHBOARD_VIEW', true);
require __DIR__ . '/dashboard/index.php';
