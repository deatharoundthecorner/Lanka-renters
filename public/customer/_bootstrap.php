<?php

if (defined('CUSTOMER_MODULE_BOOTSTRAPPED')) {
    return;
}

define('CUSTOMER_MODULE_BOOTSTRAPPED', true);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/helpers/CustomerCsrf.php';
require_once dirname(__DIR__, 2) . '/app/controllers/CustomerController.php';

function customer_base_url(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $marker = '/customer/';
    $position = strpos($scriptName, $marker);

    if ($position === false) {
        return '/customer';
    }

    return substr($scriptName, 0, $position + strlen('/customer'));
}

function customer_url(string $path = ''): string
{
    $base = rtrim(customer_base_url(), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function customer_error_response(int $status, string $heading, string $message): never
{
    http_response_code($status);

    $safeHeading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . $safeHeading . '</title></head><body>';
    echo '<main><h1>' . $safeHeading . '</h1><p>' . $safeMessage . '</p></main>';
    echo '</body></html>';
    exit;
}

set_exception_handler(static function (Throwable $exception): void {
    error_log(sprintf(
        'Customer module exception [%s]: %s',
        get_class($exception),
        $exception->getMessage()
    ));

    customer_error_response(
        500,
        'Customer page unavailable',
        'The page could not be loaded. Please try again later.'
    );
});

AuthHelper::requireRole('customer');

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store, private');
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    $fieldName = CustomerCsrf::fieldName();
    $submittedToken = $_POST[$fieldName] ?? null;

    if (!is_string($submittedToken) || !CustomerCsrf::validate($submittedToken)) {
        error_log('Customer CSRF validation rejected a state-changing request.');
        customer_error_response(403, 'Request rejected', 'Your session token is invalid or expired.');
    }
}
