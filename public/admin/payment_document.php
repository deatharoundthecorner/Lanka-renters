<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/helpers/Database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$paymentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($paymentId === false || $paymentId === null) {
    http_response_code(404);
    exit('Payment evidence not found.');
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT `id`, `payment_slip_path` FROM `payments` WHERE `id` = :id LIMIT 1");
    $stmt->execute(['id' => $paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment || empty($payment['payment_slip_path'])) {
        http_response_code(404);
        exit('Payment evidence not uploaded.');
    }

    $base = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage');
    $file = realpath($base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$payment['payment_slip_path']));

    if ($base === false || $file === false || !is_file($file)) {
        http_response_code(404);
        exit('Payment evidence file not found on disk.');
    }

    $prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($file, $prefix)) {
        http_response_code(403);
        exit('Access denied.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
        http_response_code(403);
        exit('Unsupported file type.');
    }

    $ext = match ($mime) {
        'application/pdf' => '.pdf',
        'image/png' => '.png',
        'image/webp' => '.webp',
        default => '.jpg',
    };

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($file));
    header('Content-Disposition: inline; filename="payment-evidence-' . (int)$payment['id'] . $ext . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, private');
    readfile($file);
    exit;
} catch (Throwable $exception) {
    error_log(sprintf('Admin payment document delivery failed [%s]: %s', get_class($exception), $exception->getMessage()));
    http_response_code(404);
    exit('Payment evidence unavailable.');
}
