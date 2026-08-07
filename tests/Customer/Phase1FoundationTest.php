<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

ini_set('session.save_path', sys_get_temp_dir());
ini_set('session.use_cookies', '0');

require_once $root . '/app/helpers/AuthHelper.php';
require_once $root . '/app/helpers/CustomerCsrf.php';
require_once $root . '/app/controllers/CustomerController.php';

$passes = [];
$failures = [];
$blockers = [];

function phaseOneCheck(bool $condition, string $message): void
{
    global $passes, $failures;

    if ($condition) {
        $passes[] = $message;
        return;
    }

    $failures[] = $message;
}

AuthHelper::startSession();
$_SESSION = [
    'user' => [
        'id' => 42,
        'name' => 'Phase One Customer',
        'email' => 'customer@example.test',
        'role' => 'customer',
    ],
];

$token = CustomerCsrf::token();
phaseOneCheck(strlen($token) === 64 && ctype_xdigit($token), 'CSRF token is a 256-bit hexadecimal value.');
phaseOneCheck(CustomerCsrf::token() === $token, 'CSRF token remains stable within the session.');
phaseOneCheck(CustomerCsrf::validate($token), 'Valid CSRF token is accepted.');
phaseOneCheck(!CustomerCsrf::validate('invalid-token'), 'Invalid CSRF token is rejected.');
phaseOneCheck(!CustomerCsrf::validate(null), 'Missing CSRF token is rejected.');
phaseOneCheck(str_contains(CustomerCsrf::field(), 'type="hidden"'), 'Reusable CSRF form field is available.');

$controller = new CustomerController();
$dashboard = $controller->dashboard();
phaseOneCheck(($dashboard['customer']['user_id'] ?? null) === 42, 'Controller derives Customer identity from the authenticated session.');
phaseOneCheck(($dashboard['customer']['name'] ?? null) === 'Phase One Customer', 'Controller passes Customer model data to the view payload.');
phaseOneCheck(($dashboard['csrf_token'] ?? null) === $token, 'Controller exposes the session CSRF token only to its view payload.');

$customerModel = new Customer();
$roleRejected = false;
try {
    $customerModel->identityFromAuthenticatedUser(['id' => 7, 'role' => 'driver']);
} catch (InvalidArgumentException $exception) {
    $roleRejected = true;
}
phaseOneCheck($roleRejected, 'Customer model rejects a non-customer session identity.');

$databaseReflection = new ReflectionClass('Database');
$databaseFile = realpath((string) $databaseReflection->getFileName());
$expectedDatabaseFile = realpath($root . '/app/helpers/Database.php');
phaseOneCheck($databaseFile === $expectedDatabaseFile, 'Authoritative helper Database class is the only active Database class.');

$entryPoints = [
    'public/customer/dashboard.php',
    'public/customer/dashboard/index.php',
    'public/customer/bookings/index.php',
    'public/customer/bookings/create.php',
    'public/customer/bookings/details.php',
    'public/customer/bookings/history.php',
    'public/customer/vehicles/index.php',
    'public/customer/vehicles/details.php',
    'public/customer/payments/index.php',
    'public/customer/payments/invoice.php',
    'public/customer/incidents/index.php',
    'public/customer/reviews/index.php',
    'public/customer/notifications.php',
    'public/customer/chat/index.php',
    'public/customer/profile/index.php',
];

foreach ($entryPoints as $entryPoint) {
    phaseOneCheck(is_file($root . '/' . $entryPoint), 'Entry point exists: ' . $entryPoint);
}

$configSource = file_get_contents($root . '/app/config/database.php');
if (
    !is_string($configSource)
    || !str_contains($configSource, 'port=3308')
    || str_contains($configSource, "../core/Database.php")
) {
    $blockers[] = 'Shared app/config/database.php is not yet the authoritative origin/develop port-3308 configuration.';
}

CustomerCsrf::rotate();
phaseOneCheck(!CustomerCsrf::validate($token), 'Rotating the CSRF token invalidates the previous token.');

session_write_close();

foreach ($passes as $message) {
    echo '[PASS] ' . $message . PHP_EOL;
}
foreach ($blockers as $message) {
    echo '[BLOCKED] ' . $message . PHP_EOL;
}
foreach ($failures as $message) {
    echo '[FAIL] ' . $message . PHP_EOL;
}

exit($failures === [] ? 0 : 1);
