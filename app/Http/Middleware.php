<?php

namespace App\Http;

use Illuminate\Foundation\Configuration\Middleware as MiddlewareConfig;

class Middleware
{
    /**
     * Configure the middleware for the application.
     */
    public function __invoke(MiddlewareConfig $middleware): void
    {
        // Global middleware
        $middleware->append([
            \App\Http\Middleware\CashierAccess::class,
        ]);

        // Route middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'cashier.access' => \App\Http\Middleware\CashierAccess::class,
        ]);
    }
}
