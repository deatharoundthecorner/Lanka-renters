<?php

declare(strict_types=1);

// Local HTTP/browser test router. Ports map to isolated role fixtures:
// 8765 = guest, 8766 = first customer, 8767 = driver, 8768 = second customer.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

ini_set('session.save_path', sys_get_temp_dir());
ini_set('session.use_cookies', '0');

$requestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
if (str_contains($requestPath, '/customer/')) {
    require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
    AuthHelper::startSession();

    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $port = (int) ($_SERVER['SERVER_PORT'] ?? 0);
    if (($port === 8766 || $port === 8768 || str_ends_with($host, ':8766') || str_ends_with($host, ':8768')) && !str_ends_with($requestPath, '/logout.php')) {
        require_once dirname(__DIR__, 2) . '/app/helpers/Database.php';
        $offset = $port === 8768 || str_ends_with($host, ':8768') ? 1 : 0;
        $statement = Database::getInstance()->getConnection()->prepare(
            "SELECT u.id, u.name, u.email, u.role
             FROM users u INNER JOIN customers c ON c.user_id = u.id
             WHERE u.role = 'customer' AND u.status = 'active'
             ORDER BY u.id LIMIT 1 OFFSET :offset"
        );
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $customer = $statement->fetch();
        if (is_array($customer)) {
            $_SESSION['user'] = $customer;
        }
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
