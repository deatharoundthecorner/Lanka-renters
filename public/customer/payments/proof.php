<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPaymentController.php';
$download = (new CustomerPaymentController())->proofDownload($_GET);
if (!is_array($download)) { customer_error_response(404, 'Evidence not found', 'The requested payment evidence is unavailable.'); }
header('Content-Type: ' . $download['mime']);
header('Content-Length: ' . (int) $download['size']);
header('Content-Disposition: attachment; filename="' . $download['filename'] . '"');
header('X-Content-Type-Options: nosniff');
readfile($download['path']);
exit;

