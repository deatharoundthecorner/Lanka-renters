<?php

require_once dirname(__DIR__) . '/models/EmailLog.php';

final class MailHelper
{
    public static function isAvailable(): bool
    {
        return function_exists('mail') && trim((string)ini_get('sendmail_path')) !== '';
    }

    public static function send(array $data): bool
    {
        $to = trim((string)($data['to'] ?? ''));
        $subject = trim((string)($data['subject'] ?? ''));
        $body = (string)($data['body'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || $subject === '' || $body === '') return false;
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $sent = self::isAvailable() && @mail($to, $subject, $body, $headers);
        try {
            (new EmailLog())->logEmail([
                'recipient_type' => $data['recipient_type'] ?? 'customer',
                'recipient_name' => $data['recipient_name'] ?? $to,
                'recipient_email' => $to,
                'subject' => $subject,
                'booking_id' => $data['booking_id'] ?? null,
                'status' => $sent ? 'sent' : 'failed',
                'failure_reason' => $sent ? null : 'Native PHP mail transport unavailable or rejected the message.',
            ]);
        } catch (Throwable $ignored) {
            error_log('Email log write failed.');
        }
        return $sent;
    }
}
