<?php

declare(strict_types=1);

// Local HTTP/browser test router. Ports map to isolated role fixtures:
// 8765 = guest, 8766 = customer, 8767 = driver.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$requestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
if (str_contains($requestPath, '/customer/')) {
    require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
    AuthHelper::startSession();

    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $port = (int) ($_SERVER['SERVER_PORT'] ?? 0);
    if (($port === 8766 || str_ends_with($host, ':8766')) && !str_ends_with($requestPath, '/logout.php')) {
        $_SESSION['user'] = [
            'id' => 93,
            'name' => 'Kasun <Test> Customer',
            'email' => 'kasun.customer@example.test',
            'role' => 'customer',
        ];
    } elseif ($port === 8767 || str_ends_with($host, ':8767')) {
        $_SESSION['user'] = [
            'id' => 94,
            'name' => 'Driver Test User',
            'email' => 'driver@example.test',
            'role' => 'driver',
        ];
    }

}

$publicRoot = realpath(dirname(__DIR__, 2) . '/public');
$requestedFile = $publicRoot !== false ? realpath($publicRoot . $requestPath) : false;

if (
    $publicRoot !== false
    && $requestedFile !== false
    && str_starts_with($requestedFile, $publicRoot)
    && is_file($requestedFile)
    && strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION)) === 'php'
) {
    require $requestedFile;
    return true;
}

return false;
