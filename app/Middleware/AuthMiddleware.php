<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;

/**
 * Blocks any route from guests. Redirects to login and remembers
 * the originally requested URL so we can bounce back after auth.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        if (!Auth::check()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? null;
            header('Location: ' . base_url('/login'));
            exit;
        }
    }
}
