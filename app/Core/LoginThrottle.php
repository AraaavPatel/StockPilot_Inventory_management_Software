<?php

namespace App\Core;

/**
 * LoginThrottle
 *
 * Simple DB-backed brute-force guard, keyed on email+IP so one bad
 * actor can't lock out a legitimate user working from a different
 * network. Deliberately does not use exponential backoff or CAPTCHA —
 * on shared hosting the honest goal is "make scripted guessing slow
 * enough to be useless," not perfect bot detection.
 */
class LoginThrottle
{
    private static function maxAttempts(): int
    {
        return (int) config('LOGIN_MAX_ATTEMPTS', 5);
    }

    private static function windowSeconds(): int
    {
        return (int) config('LOGIN_LOCKOUT_WINDOW_SECONDS', 900);
    }

    public static function isLocked(string $email, string $ip): bool
    {
        return self::recentFailures($email, $ip) >= self::maxAttempts();
    }

    public static function recordFailure(string $email, string $ip): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO login_attempts (email, ip_address, succeeded) VALUES (:email, :ip, 0)'
        );
        $stmt->execute(['email' => $email, 'ip' => $ip]);
    }

    public static function recordSuccess(string $email, string $ip): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO login_attempts (email, ip_address, succeeded) VALUES (:email, :ip, 1)'
        );
        $stmt->execute(['email' => $email, 'ip' => $ip]);
    }

    private static function recentFailures(string $email, string $ip): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS cnt FROM login_attempts
             WHERE email = :email AND ip_address = :ip
               AND succeeded = 0
               AND attempted_at > (NOW() - INTERVAL :window SECOND)'
        );
        $stmt->execute(['email' => $email, 'ip' => $ip, 'window' => self::windowSeconds()]);
        return (int) $stmt->fetch()['cnt'];
    }
}
