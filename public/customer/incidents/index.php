<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

// The former incident landing page contained disabled placeholder actions.
// Send Customers directly to the functional report form instead.
header('Location: ' . customer_url('incidents/create.php'), true, 302);
exit;
