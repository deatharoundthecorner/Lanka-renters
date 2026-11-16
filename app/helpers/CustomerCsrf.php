<?php

require_once __DIR__ . '/AuthHelper.php';

/**
 * Customer-module CSRF protection.
 *
 * Tokens are stored only in the active session and are never accepted from a
 * query string. State-changing Customer requests are validated centrally by
 * public/customer/_bootstrap.php.
 */
final class CustomerCsrf
{
    private const SESSION_KEY = '_customer_csrf_token';
    private const FIELD_NAME = '_csrf_token';

    public static function token(): string
    {
        AuthHelper::startSession();

        $token = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    public static function fieldName(): string
    {
        return self::FIELD_NAME;
    }

    public static function field(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars(self::FIELD_NAME, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
        );
    }

    public static function validate(?string $submittedToken): bool
    {
        AuthHelper::startSession();

        $storedToken = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($storedToken) || !is_string($submittedToken)) {
            return false;
        }

        return hash_equals($storedToken, $submittedToken);
    }

    public static function rotate(): void
    {
        AuthHelper::startSession();
        unset($_SESSION[self::SESSION_KEY]);
        self::token();
    }
}
