<?php

namespace App\Audit;

/**
 * RequestId
 *
 * One short ID per HTTP request (e.g. SP-20260823-AB12CD), used to
 * correlate a user-facing error message with the matching audit_logs
 * row / application.log entry without exposing anything sensitive.
 */
class RequestId
{
    private static ?string $id = null;

    public static function current(): string
    {
        if (self::$id === null) {
            self::$id = 'SP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        }
        return self::$id;
    }
}
