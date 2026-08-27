<?php

namespace App\Middleware;

/** Any authenticated user — POS billing screen, own profile. */
class AnyRole extends RoleMiddleware
{
    protected array $allowedRoles = ['admin', 'manager', 'cashier'];
}
