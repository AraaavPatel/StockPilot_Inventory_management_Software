<?php

namespace App\Middleware;

/** Admin dashboard, user management. */
class AdminOnly extends RoleMiddleware
{
    protected array $allowedRoles = ['admin'];
}
