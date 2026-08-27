<?php

namespace App\Core;

/**
 * Auth
 *
 * Thin static helper over $_SESSION for the logged-in user.
 * Passwords are never stored in session — only id/name/role.
 */
class Auth
{
    public static function login(array $user): void
    {
        // Always rotate the session ID on privilege change (login is the
        // classic session-fixation target: an attacker who planted a
        // pre-auth session ID must not be able to ride it into an
        // authenticated session).
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
        $_SESSION['_last_activity'] = time();
        $_SESSION['_started_at'] = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }
}
