<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$passes = [];
$failures = [];

function phaseThreeCheck(bool $condition, string $message): void
{
    global $passes, $failures;

    if ($condition) {
        $passes[] = $message;
        return;
    }

    $failures[] = $message;
}

ini_set('session.save_path', sys_get_temp_dir());
ini_set('session.use_cookies', '0');

require_once $root . '/app/helpers/AuthHelper.php';
require_once $root . '/app/helpers/CustomerCsrf.php';
require_once $root . '/app/controllers/CustomerController.php';

AuthHelper::startSession();
$_SESSION['user'] = [
    'id' => 93,
    'name' => 'Kasun Test Customer',
    'email' => 'kasun.customer@example.test',
    'role' => 'customer',
];

$dashboard = (new CustomerController())->dashboard();
phaseThreeCheck(($dashboard['active_nav'] ?? null) === 'dashboard', 'Dashboard identifies the active navigation item.');
phaseThreeCheck(count($dashboard['dashboard']['stats'] ?? []) === 4, 'Dashboard provides four display-only summary cards.');
phaseThreeCheck(array_key_exists('recent_booking', $dashboard['dashboard'] ?? []), 'Dashboard supports the recent-booking empty state.');
phaseThreeCheck(($dashboard['customer']['name'] ?? null) === 'Kasun Test Customer', 'Dashboard identity comes from the authenticated session.');

$requiredRoutes = [
    'public/customer/dashboard.php',
    'public/customer/verification/index.php',
    'public/customer/vehicles/index.php',
    'public/customer/bookings/index.php',
    'public/customer/inspection/index.php',
    'public/customer/payments/index.php',
    'public/customer/chat/index.php',
    'public/customer/incidents/index.php',
    'public/customer/driver-change/index.php',
    'public/customer/return/index.php',
    'public/customer/reviews/index.php',
    'public/customer/profile/index.php',
    'public/customer/notifications.php',
    'public/customer/logout.php',
];

foreach ($requiredRoutes as $route) {
    phaseThreeCheck(is_file($root . '/' . $route), 'Navigation route exists: ' . $route);
}

$headerSource = file_get_contents($root . '/public/customer/components/layout/header.php') ?: '';
$sidebarSource = file_get_contents($root . '/public/customer/components/layout/sidebar.php') ?: '';
$cssSource = file_get_contents($root . '/public/customer/assets/css/customer-foundation.css') ?: '';
$jsSource = file_get_contents($root . '/public/customer/assets/js/customer-ui.js') ?: '';

phaseThreeCheck(str_contains($headerSource, "customer_url('assets/css/customer-foundation.css')"), 'Customer stylesheet uses the canonical URL helper.');
phaseThreeCheck(str_contains($headerSource, "customer_url('assets/js/customer-ui.js')"), 'Customer JavaScript uses the canonical URL helper.');
phaseThreeCheck(str_contains($sidebarSource, 'aria-current="page"'), 'Sidebar exposes the active page to assistive technology.');
phaseThreeCheck(str_contains($sidebarSource, "customer_url('logout.php')"), 'Logout uses the guarded Customer endpoint.');

$requiredCssValues = [
    '--color-primary-dark: #0B3A82',
    '--color-primary: #1357C8',
    '--color-primary-light: #DBEAFE',
    '--color-background: #F8FAFC',
    '--color-card: #FFFFFF',
    '--color-text: #0F172A',
    '--color-text-secondary: #64748B',
    'font-family: Inter, Arial, sans-serif',
    '--radius-card: 16px',
];

foreach ($requiredCssValues as $cssValue) {
    phaseThreeCheck(str_contains($cssSource, $cssValue), 'Approved design value exists: ' . $cssValue);
}

phaseThreeCheck(str_contains($jsSource, "event.key !== 'Escape'"), 'Escape-key closing is implemented.');
phaseThreeCheck(str_contains($jsSource, "setAttribute('aria-expanded'"), 'Disclosure controls update aria-expanded.');
phaseThreeCheck(!preg_match('/React|Next\.js|Tailwind|Bootstrap|jQuery/i', $cssSource . $jsSource), 'Customer assets contain no forbidden framework code.');

CustomerCsrf::rotate();
session_write_close();

foreach ($passes as $message) {
    echo '[PASS] ' . $message . PHP_EOL;
}
foreach ($failures as $message) {
    echo '[FAIL] ' . $message . PHP_EOL;
}

exit($failures === [] ? 0 : 1);
