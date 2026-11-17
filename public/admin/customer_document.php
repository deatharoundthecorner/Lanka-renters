<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/CustomerVerification.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($documentId === false || $documentId === null) {
    http_response_code(404);
    exit('Document not found.');
}

try {
    $document = (new CustomerVerification())->findDocumentForAccess((int) $documentId, (int) (AuthHelper::getCurrentUser()['id'] ?? 0), true);
    if (!is_array($document)) {
        http_response_code(404);
        exit('Document not found.');
    }

    $base = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'customer-verification-documents');
    $file = realpath($base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $document['storage_key']));
    if ($base === false || $file === false || !is_file($file)) {
        http_response_code(404);
        exit('Document not found.');
    }
    $prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($file, $prefix)) {
        http_response_code(404);
        exit('Document not found.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
        http_response_code(404);
        exit('Document not found.');
    }

    $ext = match ($mime) {
        'application/pdf' => '.pdf',
        'image/png' => '.png',
        'image/webp' => '.webp',
        default => '.jpg',
    };

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($file));
    header('Content-Disposition: inline; filename="customer-document-' . (int) $document['id'] . $ext . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, private');
    readfile($file);
} catch (Throwable $exception) {
    error_log(sprintf('Customer document delivery failed [%s]: %s', get_class($exception), $exception->getMessage()));
    http_response_code(404);
    exit('Document not found.');
}
