<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/helpers/CustomerCsrf.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/CustomerPayment.php';

/** Secure Customer bank-transfer submission and owned payment views. */
final class CustomerPaymentController
{
    private const MAX_PROOF_SIZE = 5242880;
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    private CustomerPayment $model;
    private Customer $customerModel;

    public function __construct(?CustomerPayment $model = null, ?Customer $customerModel = null)
    {
        $this->model = $model ?? new CustomerPayment();
        $this->customerModel = $customerModel ?? new Customer();
    }

    public function createPage(array $query, array $post, array $files, string $method): array
    {
        $data = $this->pageData('Submit Payment', 'Submit bank-transfer evidence for Admin verification.', 'payments');
        $data += ['booking' => null, 'errors' => [], 'database_error' => false, 'form' => ['transaction_reference' => '', 'payment_method' => 'bank_transfer']];
        $bookingId = $this->positiveInt($query['booking_id'] ?? $post['booking_id'] ?? null);
        if ($bookingId === null) {
            http_response_code(404);
            return $data;
        }

        try {
            $booking = $this->model->getPayableBooking($this->userId(), $bookingId);
            if ($booking === null) {
                http_response_code(404);
                return $data;
            }
            $data['booking'] = $booking;
            if ($method !== 'POST') {
                return $data;
            }

            $data['form']['transaction_reference'] = $this->text($post['transaction_reference'] ?? '', 100);
            $methodValue = is_string($post['payment_method'] ?? null) ? $post['payment_method'] : '';
            if ($methodValue !== 'bank_transfer') {
                $data['errors']['payment_method'] = 'Only bank transfer is currently available.';
            }
            if (is_array($booking['blocking_payment'])) {
                $data['errors']['form'] = 'A pending or completed payment already exists for this booking.';
            }
            if (is_array($booking['latest_payment']) && $booking['latest_payment']['payment_status'] === 'refunded') {
                $data['errors']['form'] = 'A refunded payment requires coordinated support before resubmission.';
            }

            $uploadedPath = null;
            if ($data['errors'] === []) {
                try {
                    $uploadedPath = $this->storeProof($files['payment_proof'] ?? null);
                } catch (DomainException $exception) {
                    $data['errors']['payment_proof'] = $exception->getMessage();
                }
            }
            if ($data['errors'] !== []) {
                return $data;
            }

            try {
                $paymentId = $this->model->createCustomerPayment(
                    $this->userId(),
                    $bookingId,
                    'bank_transfer',
                    $uploadedPath,
                    $data['form']['transaction_reference']
                );
            } catch (Throwable $exception) {
                $this->deleteStoredProof($uploadedPath);
                throw $exception;
            }
            $this->setFlash('success', 'Payment submitted for verification. It has not been marked completed.');
            $data['redirect'] = 'payments/details.php?id=' . $paymentId;
        } catch (DomainException $exception) {
            $data['errors']['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->log('create', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function historyPage(array $query): array
    {
        $data = $this->pageData('Payments', 'View only payments connected to your bookings.', 'payments');
        $status = $this->allowed($query['status'] ?? '', ['', 'pending', 'completed', 'failed', 'refunded']);
        $page = $this->positiveInt($query['page'] ?? null) ?? 1;
        $data += ['payments' => [], 'status' => $status, 'total' => 0, 'current_page' => 1, 'total_pages' => 1, 'database_error' => false, 'flash' => $this->pullFlash()];
        try {
            $size = 8;
            $total = $this->model->countCustomerPayments($this->userId(), $status);
            $pages = max(1, (int) ceil($total / $size));
            $page = min($page, $pages);
            $data['payments'] = $this->model->getCustomerPayments($this->userId(), $status, $size, ($page - 1) * $size);
            $data['total'] = $total;
            $data['current_page'] = $page;
            $data['total_pages'] = $pages;
        } catch (Throwable $exception) {
            $this->log('history', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function detailsPage(array $query): array
    {
        $data = $this->pageData('Payment Details', 'Review a payment connected to your booking.', 'payments');
        $data += ['payment' => null, 'database_error' => false, 'flash' => $this->pullFlash()];
        $id = $this->positiveInt($query['id'] ?? null);
        if ($id === null) {
            http_response_code(404);
            return $data;
        }
        try {
            $data['payment'] = $this->model->findCustomerPayment($this->userId(), $id);
            if ($data['payment'] === null) {
                http_response_code(404);
            }
        } catch (Throwable $exception) {
            $this->log('details', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function summaryPage(array $query): array
    {
        $data = $this->pageData('Payment Summary', 'Booking totals and existing payment information; this is not an official invoice.', 'payments');
        $data += ['summary' => null, 'database_error' => false];
        $bookingId = $this->positiveInt($query['booking_id'] ?? null);
        if ($bookingId === null) {
            http_response_code(404);
            return $data;
        }
        try {
            $data['summary'] = $this->model->getBookingPaymentSummary($this->userId(), $bookingId);
            if ($data['summary'] === null) {
                http_response_code(404);
            }
        } catch (Throwable $exception) {
            $this->log('summary', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function proofDownload(array $query): ?array
    {
        $id = $this->positiveInt($query['id'] ?? null);
        if ($id === null) {
            return null;
        }
        try {
            $proof = $this->model->findCustomerPaymentProof($this->userId(), $id);
            if ($proof === null || !is_string($proof['payment_slip_path'])) {
                return null;
            }
            $storageBase = realpath($this->storageBase());
            $file = realpath($this->storageBase() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $proof['payment_slip_path']));
            $evidenceRoot = realpath($this->evidenceRoot());
            if ($storageBase === false || $evidenceRoot === false || $file === false || !is_file($file)) {
                return null;
            }
            $prefix = rtrim($evidenceRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (!str_starts_with($file, $prefix)) {
                return null;
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
            if (!is_string($mime) || !isset(self::MIME_EXTENSIONS[$mime])) {
                return null;
            }
            return [
                'path' => $file,
                'mime' => $mime,
                'size' => filesize($file),
                'filename' => 'payment-proof-' . $id . '.' . self::MIME_EXTENSIONS[$mime],
            ];
        } catch (Throwable $exception) {
            $this->log('proof', $exception);
            return null;
        }
    }

    private function storeProof(mixed $file): string
    {
        if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['size'])) {
            throw new DomainException('Choose a JPEG, PNG, or PDF payment-evidence file.');
        }
        $error = (int) $file['error'];
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new DomainException('Payment evidence is required for bank transfer.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new DomainException('The payment-evidence upload could not be completed.');
        }
        if ((int) $file['size'] < 1 || (int) $file['size'] > self::MAX_PROOF_SIZE) {
            throw new DomainException('Payment evidence must be no larger than 5 MB.');
        }
        $temporaryPath = (string) $file['tmp_name'];
        if (!is_uploaded_file($temporaryPath)) {
            throw new DomainException('The uploaded payment evidence was not accepted.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        if (!is_string($mime) || !isset(self::MIME_EXTENSIONS[$mime])) {
            throw new DomainException('Only genuine JPEG, PNG, and PDF files are allowed.');
        }
        $root = $this->evidenceRoot();
        if (!is_dir($root) && !mkdir($root, 0750, true) && !is_dir($root)) {
            throw new RuntimeException('Payment evidence storage is unavailable.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . self::MIME_EXTENSIONS[$mime];
        $target = $root . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporaryPath, $target)) {
            throw new RuntimeException('Payment evidence could not be stored.');
        }
        return 'customer-payment-evidence/' . $filename;
    }

    private function deleteStoredProof(?string $relativePath): void
    {
        if (!is_string($relativePath) || !str_starts_with($relativePath, 'customer-payment-evidence/')) {
            return;
        }
        $candidate = $this->storageBase() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (is_file($candidate)) {
            @unlink($candidate);
        }
    }

    private function storageBase(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage';
    }

    private function evidenceRoot(): string
    {
        return $this->storageBase() . DIRECTORY_SEPARATOR . 'customer-payment-evidence';
    }

    private function pageData(string $title, string $description, string $activeNav): array
    {
        $user = AuthHelper::getCurrentUser();
        if (!is_array($user) || ($user['role'] ?? '') !== 'customer') {
            throw new LogicException('Customer session was not established.');
        }
        return [
            'page_title' => $title,
            'page_description' => $description,
            'active_nav' => $activeNav,
            'customer' => $this->customerModel->identityFromAuthenticatedUser($user),
            'csrf_token' => CustomerCsrf::token(),
            'csrf_field_name' => CustomerCsrf::fieldName(),
        ];
    }

    private function userId(): int
    {
        $user = AuthHelper::getCurrentUser();
        return (int) ($user['id'] ?? 0);
    }

    private function positiveInt(mixed $value): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $integer === false ? null : (int) $integer;
    }

    private function allowed(mixed $value, array $allowed): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : '';
    }

    private function text(mixed $value, int $length): string
    {
        if (!is_string($value)) {
            return '';
        }
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
    }

    private function setFlash(string $tone, string $message): void
    {
        $_SESSION['_customer_payment_flash'] = ['tone' => $tone, 'message' => $message];
    }

    private function pullFlash(): ?array
    {
        $flash = $_SESSION['_customer_payment_flash'] ?? null;
        unset($_SESSION['_customer_payment_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function log(string $area, Throwable $exception): void
    {
        error_log(sprintf('Customer payment %s error [%s]: %s', $area, get_class($exception), $exception->getMessage()));
    }
}
