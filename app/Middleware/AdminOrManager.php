<?php

namespace App\Middleware;

/** Product/category/supplier/purchase management, reports. */
class AdminOrManager extends RoleMiddleware
{
    protected array $allowedRoles = ['admin', 'manager'];
}
