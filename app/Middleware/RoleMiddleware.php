<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;

/**
 * Base class for role-restricted routes. Concrete subclasses
 * (AdminOnly, AdminOrManager, ...) just declare $allowedRoles,
 * so route definitions stay readable:
 *
 *   $router->get('/users', 'UserController@index', [AdminOnly::class]);
 */
abstract class RoleMiddleware implements MiddlewareInterface
{
    protected array $allowedRoles = [];

    public function handle(): void
    {
        if (!Auth::check()) {
            header('Location: ' . base_url('/login'));
            exit;
        }

        if (!Auth::hasRole(...$this->allowedRoles)) {
            http_response_code(403);
            require __DIR__ . '/../Views/errors/403.php';
            exit;
        }
    }
}
