<?php
require_once __DIR__ . '/_bootstrap.php';
header('Location: ' . customer_url('payments/index.php'), true, 302);
exit;
